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

        if (empty($content)) {
            Log::warning('Rejected webhook message: message content is empty');
            return [
                'status' => 200,
                'message' => 'Missing message content'
            ];
        }

        // 2. Programmatically disable sending replies back via Chatwoot API during this sprint
        //config(['chatwoot.send_responses' => false]);

        try {
            // 3. Construct ChatwootMessageDTO
            $messageDto = ChatwootMessageDTO::fromWebhook($payload);
            Log::info('DEBUG: conversation_id after DTO extraction', ['id' => $messageDto->conversationId]);

            // Persist the conversation ID to the Customer record
            $phone = $messageDto->phone;
            $customer = \App\Models\Customer::where('phone', $phone)->first();
            if (!$customer && str_starts_with($phone, '+')) {
                $customer = \App\Models\Customer::where('phone', ltrim($phone, '+'))->first();
            }
            if (!$customer && !str_starts_with($phone, '+')) {
                $customer = \App\Models\Customer::where('phone', '+' . $phone)->first();
            }

            if ($customer) {
                $customer->chatwoot_conversation_id = $messageDto->conversationId;
                $customer->save();
            } else {
                \App\Models\Customer::create([
                    'full_name' => $messageDto->senderName ?: 'Cliente WhatsApp',
                    'phone' => $phone,
                    'chatwoot_conversation_id' => $messageDto->conversationId
                ]);
            }

            // 4. Resolve and invoke ConversationOrchestrator
            $orchestrator = app(ConversationOrchestrator::class);
            $assistantResponse = $orchestrator->handle($messageDto);

            // 5. Log structured details as required
            Log::info("Incoming message:\n" . $phoneNumber . "\n\n" . $content);
            Log::info("Assistant response:\n" . $assistantResponse);

            return [
                'status' => 200,
                'message' => 'Webhook message processed successfully'
            ];
        } catch (\Throwable $e) {
            // 6. Handle exception and return HTTP 200 to prevent infinite retries
            Log::error('Error occurred inside ConversationOrchestrator execution', [
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
