<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\ChatwootMessageDTO;
use App\AI\Orchestrators\ConversationOrchestrator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly ChatwootMessageDTO $message
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ConversationOrchestrator $orchestrator): void
    {
        Log::info('ProcessIncomingMessageJob executing', [
            'conversation_id' => $this->message->conversationId,
            'phone' => $this->message->phone,
        ]);

        // 1. Ignore non-incoming messages
        if ($this->message->messageType !== 'incoming') {
            Log::info('Job ignored non-incoming Chatwoot message', ['type' => $this->message->messageType]);
            return;
        }

        // 2. Delegate processing to the ConversationOrchestrator
        $orchestrator->handle($this->message);
    }
}
