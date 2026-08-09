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
        protected array $payload,
        protected float $dispatchedAt = 0.0
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ConversationOrchestrator $orchestrator): void
    {
        $message = ChatwootMessageDTO::fromWebhook($this->payload);
        $phone = $message->phone;

        Log::info('ProcessIncomingMessageJob executing', [
            'conversation_id' => $message->conversationId,
            'phone' => $phone,
            'dispatched_at' => $this->dispatchedAt,
        ]);

        // 1. Ignore non-incoming messages
        if ($message->messageType !== 'incoming') {
            Log::info('Job ignored non-incoming Chatwoot message', ['type' => $message->messageType]);
            return;
        }

        // 2. Debounce Check: If a newer message arrived for this phone after this job was dispatched, skip execution
        if ($this->dispatchedAt > 0 && !app()->environment('testing')) {
            $latestTime = (float) (\Illuminate\Support\Facades\Redis::get("chatwoot_last_time:{$phone}") ?: 0);
            if ($latestTime > ($this->dispatchedAt + 0.0001)) {
                Log::info("ProcessIncomingMessageJob debounced for {$phone}. A newer message arrived, skipping redundant AI call.", [
                    'job_dispatched_at' => $this->dispatchedAt,
                    'latest_message_time' => $latestTime,
                ]);
                return;
            }
        }

        // 3. Consolidate buffered messages into a single combined prompt
        $combinedText = $message->text;
        if (!app()->environment('testing')) {
            $bufferKey = "chatwoot_buffer:{$phone}";
            $bufferedMsgs = \Illuminate\Support\Facades\Redis::lrange($bufferKey, 0, -1);
            \Illuminate\Support\Facades\Redis::del($bufferKey);

            if (!empty($bufferedMsgs)) {
                $combinedText = implode("\n", array_values(array_unique(array_filter($bufferedMsgs))));
            }
        }

        $combinedMessage = new ChatwootMessageDTO(
            conversationId: $message->conversationId,
            phone: $message->phone,
            senderName: $message->senderName,
            text: trim($combinedText),
            messageType: $message->messageType,
            rawPayload: $message->rawPayload
        );

        // 4. Delegate processing to the ConversationOrchestrator
        $orchestrator->handle($combinedMessage);
    }
}
