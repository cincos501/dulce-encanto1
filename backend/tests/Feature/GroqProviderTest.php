<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Providers\GroqProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GroqProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['ai.providers.groq.key' => 'test_groq_key']);
        config(['ai.providers.groq.model' => 'llama-3.3-70b-versatile']);
        config(['ai.providers.groq.url' => 'https://api.groq.com/openai/v1/chat/completions']);
    }

    public function test_groq_provider_sends_chat_request_successfully(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Hola, bienvenido a Dulce Encanto',
                            'tool_calls' => null
                        ]
                    ]
                ]
            ], 200)
        ]);

        $provider = new GroqProvider();
        $response = $provider->chat([
            ['role' => 'user', 'content' => 'Hola']
        ]);

        $this->assertEquals('Hola, bienvenido a Dulce Encanto', $response['reply']);
        $this->assertNull($response['tool_calls']);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer test_groq_key')
                && $request['model'] === 'llama-3.3-70b-versatile'
                && $request['messages'][0]['role'] === 'user';
        });
    }

    public function test_groq_provider_returns_tool_calls_successfully(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => null,
                            'tool_calls' => [
                                [
                                    'id' => 'call_abc123',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => 'search_products',
                                        'arguments' => '{"query":"torta"}'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $provider = new GroqProvider();
        $response = $provider->chat([
            ['role' => 'user', 'content' => '¿Qué tortas tienen?']
        ], [['type' => 'function']]);

        $this->assertNull($response['reply']);
        $this->assertCount(1, $response['tool_calls']);
        $this->assertEquals('search_products', $response['tool_calls'][0]['function']['name']);
    }

    public function test_groq_provider_throws_exception_on_api_failure(): void
    {
        Http::fake([
            'https://api.groq.com/openai/v1/chat/completions' => Http::response('Internal Server Error', 500)
        ]);

        $this->expectException(\Exception::class);

        $provider = new GroqProvider();
        $provider->chat([
            ['role' => 'user', 'content' => 'Hola']
        ]);
    }
}
