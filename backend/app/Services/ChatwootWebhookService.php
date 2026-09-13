<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChatwootMessageDTO;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Customer;
use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ChatwootWebhookService
{
    /**
     * Process the webhook payload from Chatwoot and delegate to Orchestrator.
     *
     * @return array{status: int, message: string}
     */
    public function processPayload(array $payload): array
    {
        $event = $payload['event'] ?? null;

        // Verify if it is a new message event
        if ($event !== 'message_created') {
            Log::info('Ignored Chatwoot webhook event: not message_created', [
                'event' => $event,
            ]);

            return [
                'status' => 200,
                'message' => 'Event ignored',
            ];
        }

        // Only process incoming messages from the customer
        $messageType = $payload['message_type'] ?? 'incoming';
        if ($messageType !== 'incoming') {
            Log::debug('Ignored non-incoming Chatwoot message event');

            return [
                'status' => 200,
                'message' => 'Ignored non-incoming message',
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
                'message' => 'Missing phone number',
            ];
        }

        $attachments = $payload['attachments'] ?? [];

        if (empty($content) && empty($attachments)) {
            Log::warning('Rejected webhook message: message content and attachments are empty');

            return [
                'status' => 200,
                'message' => 'Missing message content',
            ];
        }

        // Idempotency filter using Redis (disabled in testing environment)
        $messageId = $payload['id'] ?? null;
        if ($messageId && ! app()->environment('testing')) {
            $lockKey = "chatwoot_msg_processed:{$messageId}";
            $isNew = Redis::setnx($lockKey, '1');
            if (! $isNew) {
                Log::info('Ignored duplicate Chatwoot message webhook', [
                    'message_id' => $messageId,
                ]);

                return [
                    'status' => 200,
                    'message' => 'Duplicate message ignored',
                ];
            }
            Redis::expire($lockKey, 3600);
        }

        try {
            // 3. Construct ChatwootMessageDTO
            $messageDto = ChatwootMessageDTO::fromWebhook($payload);
            Log::info('DEBUG: conversation_id after DTO extraction', ['id' => $messageDto->conversationId]);

            // Persist the conversation ID to the Customer record (fast DB update)
            $normalizedPhone = PhoneHelper::normalize($messageDto->phone);
            $customer = Customer::where('phone', $normalizedPhone)->first();

            if ($customer) {
                $customer->chatwoot_conversation_id = $messageDto->conversationId;
                $customer->save();
            } else {
                Customer::create([
                    'full_name' => $messageDto->senderName ?: 'Cliente WhatsApp',
                    'phone' => $normalizedPhone,
                    'chatwoot_conversation_id' => $messageDto->conversationId,
                ]);
            }

            // 4. Debounce buffer in Redis to combine rapid consecutive messages
            $phone = $messageDto->phone;
            $bufferKey = "chatwoot_buffer:{$phone}";
            $timeKey = "chatwoot_last_time:{$phone}";
            $now = microtime(true);

            if (! app()->environment('testing')) {
                Redis::rpush($bufferKey, $messageDto->text);
                Redis::expire($bufferKey, 60);
                Redis::set($timeKey, (string) $now, 'EX', 60);

                // Buffer paralelo de adjuntos: solo referencias ligeras (URLs / coordenadas),
                // NO se descarga nada aquí para no bloquear la respuesta del webhook.
                if (! empty($messageDto->attachmentRefs)) {
                    $mediaKey = "chatwoot_media_buffer:{$phone}";
                    foreach ($messageDto->attachmentRefs as $ref) {
                        Redis::rpush($mediaKey, json_encode($ref));
                    }
                    Redis::expire($mediaKey, 90);
                }
            }

            // Dispatch delayed job with 3-second grace window to buffer rapid messages
            ProcessIncomingMessageJob::dispatch($payload, $now)->delay(now()->addSeconds(3));

            Log::info("Webhook processed & ProcessIncomingMessageJob dispatched (3s grace time) for conversation #{$messageDto->conversationId}");

            return [
                'status' => 200,
                'message' => 'Webhook message processed successfully',
            ];
        } catch (\Throwable $e) {
            Log::error('Error occurred inside ChatwootWebhookService execution', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 200,
                'message' => 'Error handled successfully',
            ];
        }
    }
}
