<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Orchestrators\ConversationOrchestrator;
use App\Services\ChatwootService;
use Mockery\MockInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatwootWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['chatwoot.url' => 'http://localhost:3000']);
        config(['chatwoot.api_token' => 'test_api_token']);
        config(['chatwoot.account_id' => '1']);
        config(['chatwoot.inbox_id' => '1']);
    }

    public function test_webhook_rejects_non_json_request(): void
    {
        $response = $this->post('/api/webhooks/chatwoot', ['event' => 'message_created'], [
            'Content-Type' => 'application/x-www-form-urlencoded'
        ]);

        $response->assertStatus(400);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_webhook_ignores_non_message_events(): void
    {
        $payload = [
            'event' => 'conversation_created',
            'conversation' => ['id' => 123]
        ];

        $response = $this->postJson('/api/webhooks/chatwoot', $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Event ignored']);
    }

    public function test_webhook_valid_message_delegates_to_orchestrator(): void
    {
        $payload = [
            'event' => 'message_created',
            'id' => 999,
            'content' => 'Hola, precio',
            'message_type' => 'incoming',
            'conversation' => [
                'id' => 123,
                'contact_inbox' => [
                    'source_id' => '59170012345'
                ]
            ],
            'sender' => [
                'name' => 'Juan Pérez'
            ]
        ];

        $this->mock(ConversationOrchestrator::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->andReturn('Respuesta de la IA');
        });

        $response = $this->postJson('/api/webhooks/chatwoot', $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'Webhook message processed successfully'
        ]);
    }

    public function test_webhook_rejects_empty_message_content(): void
    {
        $payload = [
            'event' => 'message_created',
            'id' => 999,
            'content' => '',
            'message_type' => 'incoming',
            'conversation' => [
                'id' => 123,
                'contact_inbox' => [
                    'source_id' => '59170012345'
                ]
            ],
            'sender' => [
                'name' => 'Juan Pérez'
            ]
        ];

        $mockOrchestrator = $this->mock(ConversationOrchestrator::class);
        $mockOrchestrator->shouldNotReceive('handle');

        $response = $this->postJson('/api/webhooks/chatwoot', $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'Missing message content'
        ]);
    }

    public function test_webhook_rejects_missing_phone_number(): void
    {
        $payload = [
            'event' => 'message_created',
            'id' => 999,
            'content' => 'Hola',
            'message_type' => 'incoming',
            'conversation' => [
                'id' => 123,
                'contact_inbox' => []
            ],
            'sender' => []
        ];

        $mockOrchestrator = $this->mock(ConversationOrchestrator::class);
        $mockOrchestrator->shouldNotReceive('handle');

        $response = $this->postJson('/api/webhooks/chatwoot', $payload);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'Missing phone number'
        ]);
    }

    public function test_webhook_orchestrator_exception_returns_200(): void
    {
        $payload = [
            'event' => 'message_created',
            'id' => 999,
            'content' => 'Hola',
            'message_type' => 'incoming',
            'conversation' => [
                'id' => 123,
                'contact_inbox' => [
                    'source_id' => '59170012345'
                ]
            ],
            'sender' => [
                'name' => 'Juan Pérez'
            ]
        ];

        $this->mock(ConversationOrchestrator::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->andThrow(new \Exception('Test exception inside orchestrator'));
        });

        $response = $this->postJson('/api/webhooks/chatwoot', $payload);

        // Should return 200 to prevent retry loops in Chatwoot
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'success' => true,
            'message' => 'Error handled successfully'
        ]);
    }

    public function test_chatwoot_service_sends_outgoing_message_successfully(): void
    {
        Http::fake([
            'http://localhost:3000/api/v1/accounts/1/conversations/123/messages' => Http::response([
                'id' => 999,
                'content' => 'Respuesta saliente'
            ], 200)
        ]);

        // Explicitly enable responses configuration key for this specific test
        config(['chatwoot.send_responses' => true]);

        $service = new ChatwootService();
        $result = $service->sendMessage(123, 'Respuesta saliente');

        $this->assertEquals(999, $result['id']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('api_access_token', 'test_api_token')
                && $request['content'] === 'Respuesta saliente'
                && $request['message_type'] === 'outgoing';
        });
    }
}
