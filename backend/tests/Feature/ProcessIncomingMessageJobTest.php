<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\AI\Media\IncomingMediaResolver;
use App\AI\Media\ResolvedMedia;
use App\AI\Orchestrators\ConversationOrchestrator;
use App\DTO\ChatwootMessageDTO;
use App\Jobs\ProcessIncomingMessageJob;
use App\Services\ChatwootService;
use Mockery\MockInterface;
use Tests\TestCase;

class ProcessIncomingMessageJobTest extends TestCase
{
    public function test_incoming_message_job_delegates_to_orchestrator(): void
    {
        $payload = [
            'conversation' => [
                'id' => 123,
                'contact_inbox' => [
                    'source_id' => '59170012345',
                ],
            ],
            'sender' => [
                'name' => 'Juan Pérez',
            ],
            'content' => 'Quiero una torta',
            'message_type' => 'incoming',
        ];

        $mockOrchestrator = $this->mock(ConversationOrchestrator::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with(\Mockery::on(function ($arg) {
                    return $arg instanceof ChatwootMessageDTO
                        && $arg->conversationId === 123
                        && $arg->phone === '59170012345'
                        && $arg->text === 'Quiero una torta';
                }));
        });

        $job = new ProcessIncomingMessageJob($payload);
        $job->handle(
            $mockOrchestrator,
            $this->app->make(IncomingMediaResolver::class),
            $this->mock(ChatwootService::class)
        );
    }

    public function test_incoming_audio_message_is_transcribed_before_reaching_orchestrator(): void
    {
        $payload = [
            'conversation' => [
                'id' => 555,
                'contact_inbox' => ['source_id' => '59170099999'],
            ],
            'sender' => ['name' => 'Ana'],
            'content' => '',
            'message_type' => 'incoming',
            'attachments' => [
                ['file_type' => 'audio', 'data_url' => 'https://chatwoot.test/audio/1.ogg'],
            ],
        ];

        $mediaResolver = $this->mock(IncomingMediaResolver::class, function (MockInterface $mock) {
            $mock->shouldReceive('resolve')
                ->once()
                ->andReturn(new ResolvedMedia(
                    promptSegments: ['El cliente envió una nota de voz. Transcripción textual de lo que dijo: "Quiero una torta de chocolate mediana".'],
                    privateNotes: ['🎙️ [IA Encantito - Transcripción de audio]: "Quiero una torta de chocolate mediana"'],
                ));
        });

        $chatwoot = $this->mock(ChatwootService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendPrivateNote')->once();
        });

        $mockOrchestrator = $this->mock(ConversationOrchestrator::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with(\Mockery::on(function ($arg) {
                    return $arg instanceof ChatwootMessageDTO
                        && $arg->conversationId === 555
                        && str_contains($arg->text, 'Transcripción textual de lo que dijo');
                }));
        });

        $job = new ProcessIncomingMessageJob($payload);
        $job->handle($mockOrchestrator, $mediaResolver, $chatwoot);
    }
}
