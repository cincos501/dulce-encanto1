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
        $dto = new ChatwootMessageDTO(
            conversationId: 123,
            phone: '59170012345',
            senderName: 'Juan Pérez',
            text: 'Quiero una torta',
            messageType: 'incoming',
            rawPayload: []
        );

        $mockOrchestrator = $this->mock(ConversationOrchestrator::class, function (MockInterface $mock) use ($dto) {
            $mock->shouldReceive('handle')
                ->once()
                ->with($dto);
        });

        $job = new ProcessIncomingMessageJob($dto);
        $job->handle($mockOrchestrator);
    }
}
