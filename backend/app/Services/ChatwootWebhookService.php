<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\Orchestrators\ConversationOrchestrator;
use App\DTO\ChatwootMessageDTO;
use Illuminate\Support\Facades\Log;

class ChatwootWebhookService
{
    /**
     * Process the webhook payload from Chatwoot and delegate to Orchestrator.
     *
     * @param array $payload
     * @return array{status: int, message: string}
     */
    public function processPayload(array $payload): array
    {
        $event = $payload['event'] ?? null;

        // Verify if it is a new message event
        if ($event !== 'message_created') {
            Log::info('Ignored Chatwoot webhook event: not message_created', [
                'event' => $event
            ]);
            return [
                'status' => 200,
                'message' => 'Event ignored'
            ];
        }

        // Only process incoming messages from the customer
        $messageType = $payload['message_type'] ?? 'incoming';
        if ($messageType !== 'incoming') {
            Log::debug('Ignored non-incoming Chatwoot message event');
            return [
                'status' => 200,
                'message' => 'Ignored non-incoming message'
            ];
        }

        // 1. Extract and validate required information (phone and content)
        $phoneNumber = $payload['conversation']['contact_inbox']['source_id'] ?? 
                       $payload['sender']['phone_number'] ?? 
                       null;
                       
        $content = $payload['content'] ?? null;

        $conversationIdBefore = $payload['conversation']['id'] ?? $payload['conversation_id'] ?? null;
        Log::info('DEBUG: conversation_id before DTO extraction', ['id' => $conversationIdBefore]);
        Log::info('DEBUG: phone extracted', ['phone' => $phoneNumber]);

        if (empty($phoneNumber)) {
            Log::warning('Rejected webhook message: phone number is missing');
            return [
                'status' => 200,
                'message' => 'Missing phone number'
            ];
        }

        $attachments = $payload['attachments'] ?? [];

        if (empty($content) && empty($attachments)) {
            Log::warning('Rejected webhook message: message content and attachments are empty');
            return [
                'status' => 200,
                'message' => 'Missing message content'
            ];
        }

        // Idempotency filter using Redis (disabled in testing environment)
        $messageId = $payload['id'] ?? null;
        if ($messageId && !app()->environment('testing')) {
            $lockKey = "chatwoot_msg_processed:{$messageId}";
            $isNew = \Illuminate\Support\Facades\Redis::setnx($lockKey, "1");
            if (!$isNew) {
                Log::info('Ignored duplicate Chatwoot message webhook', [
                    'message_id' => $messageId
                ]);
                return [
                    'status' => 200,
                    'message' => 'Duplicate message ignored'
                ];
            }
            \Illuminate\Support\Facades\Redis::expire($lockKey, 3600);
        }

        try {
            // 3. Construct ChatwootMessageDTO
            $messageDto = ChatwootMessageDTO::fromWebhook($payload);
            Log::info('DEBUG: conversation_id after DTO extraction', ['id' => $messageDto->conversationId]);

            // Persist the conversation ID to the Customer record (fast DB update)
            $normalizedPhone = \App\Support\PhoneHelper::normalize($messageDto->phone);
            $customer = \App\Models\Customer::where('phone', $normalizedPhone)->first();

            if ($customer) {
                $customer->chatwoot_conversation_id = $messageDto->conversationId;
                $customer->save();
            } else {
                \App\Models\Customer::create([
                    'full_name' => $messageDto->senderName ?: 'Cliente WhatsApp',
                    'phone' => $normalizedPhone,
                    'chatwoot_conversation_id' => $messageDto->conversationId
                ]);
            }

            // 4. Debounce buffer in Redis to combine rapid consecutive messages
            $phone = $messageDto->phone;
            $bufferKey = "chatwoot_buffer:{$phone}";
            $timeKey = "chatwoot_last_time:{$phone}";
            $now = microtime(true);

            if (!app()->environment('testing')) {
                \Illuminate\Support\Facades\Redis::rpush($bufferKey, $messageDto->text);
                \Illuminate\Support\Facades\Redis::expire($bufferKey, 60);
                \Illuminate\Support\Facades\Redis::set($timeKey, (string) $now, 'EX', 60);
            }

            // Dispatch delayed job with 3-second grace window to buffer rapid messages
            \App\Jobs\ProcessIncomingMessageJob::dispatch($payload, $now)->delay(now()->addSeconds(3));

            Log::info("Webhook processed & ProcessIncomingMessageJob dispatched (3s grace time) for conversation #{$messageDto->conversationId}");

            return [
                'status' => 200,
                'message' => 'Webhook message processed successfully'
            ];
        } catch (\Throwable $e) {
            Log::error('Error occurred inside ChatwootWebhookService execution', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 200,
                'message' => 'Error handled successfully'
            ];
        }
    }
}
