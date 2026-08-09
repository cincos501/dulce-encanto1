<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\ChatwootMessageDTO;
use App\Jobs\ProcessIncomingMessageJob;
use App\AI\Orchestrators\ConversationOrchestrator;
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
                    'source_id' => '59170012345'
                ]
            ],
            'sender' => [
                'name' => 'Juan Pérez'
            ],
            'content' => 'Quiero una torta',
            'message_type' => 'incoming'
        ];

        $mockOrchestrator = $this->mock(ConversationOrchestrator::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')
                ->once()
                ->with(\Mockery::on(function ($arg) {
                    return $arg instanceof ChatwootMessageDTO && $arg->conversationId === 123 && $arg->phone === '59170012345';
                }));
        });

        $job = new ProcessIncomingMessageJob($payload);
        $job->handle($mockOrchestrator);
    }
}
