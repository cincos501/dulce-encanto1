<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatwootService
{
    protected string $url;

    protected string $apiToken;

    protected string $accountId;

    protected string $inboxId;

    public function __construct()
    {
        $this->url = rtrim((string) config('chatwoot.url'), '/');
        $this->apiToken = (string) config('chatwoot.api_token');
        $this->accountId = (string) config('chatwoot.account_id');
        $this->inboxId = (string) config('chatwoot.inbox_id');
    }

    /**
     * Send an outgoing message to a Chatwoot conversation.
     */
    public function sendMessage(int $conversationId, string $text): array
    {
        if (! config('chatwoot.send_responses', true)) {
            Log::info('Chatwoot API responses are disabled. Skipping sending message via HTTP.', [
                'conversation_id' => $conversationId,
                'content' => $text,
            ]);

            return ['id' => 0, 'content' => $text];
        }

        try {
            $endpoint = "{$this->url}/api/v1/accounts/{$this->accountId}/conversations/{$conversationId}/messages";

            Log::info('DEBUG CHATWOOT OUTGOING REQUEST DETAILS', [
                'url' => $endpoint,
                'account_id' => $this->accountId,
                'inbox_id' => $this->inboxId,
                'conversation_id' => $conversationId,
                'token_prefix' => substr($this->apiToken, 0, 5),
            ]);

            $response = Http::withHeaders([
                'api_access_token' => $this->apiToken,
            ])
                ->acceptJson()
                ->post($endpoint, [
                    'content' => $text,
                    'message_type' => 'outgoing',
                    'private' => false,
                ]);

            if ($response->failed()) {
                Log::error('Chatwoot API sendMessage failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'conversation_id' => $conversationId,
                ]);
                throw new \Exception('Failed to send Chatwoot message: '.$response->body());
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('Chatwoot API sendMessage exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send an internal private note to a Chatwoot conversation (visible to agents only).
     */
    public function sendPrivateNote(int $conversationId, string $text): array
    {
        if (! config('chatwoot.send_responses', true)) {
            return ['id' => 0, 'content' => $text];
        }

        try {
            $endpoint = "{$this->url}/api/v1/accounts/{$this->accountId}/conversations/{$conversationId}/messages";

            $response = Http::withHeaders([
                'api_access_token' => $this->apiToken,
            ])
                ->acceptJson()
                ->post($endpoint, [
                    'content' => $text,
                    'message_type' => 'outgoing',
                    'private' => true,
                ]);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::warning('Chatwoot API sendPrivateNote exception', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Envía un mensaje de UBICACIÓN NATIVO por WhatsApp Cloud API (pin en el mapa).
     *
     * Opcional: requiere META_ACCESS_TOKEN y WHATSAPP_PHONE_NUMBER_ID. Si no están
     * configurados, devuelve false y se debe recurrir al enlace de Google Maps en texto.
     */
    public function sendWhatsAppLocation(
        string $recipientWaId,
        float $latitude,
        float $longitude,
        string $name,
        string $address
    ): bool {
        $token = (string) config('chatwoot.meta_access_token');
        $phoneNumberId = (string) config('chatwoot.whatsapp_phone_number_id');

        if ($token === '' || $phoneNumberId === '') {
            Log::info('sendWhatsAppLocation omitido: faltan META_ACCESS_TOKEN / WHATSAPP_PHONE_NUMBER_ID');

            return false;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->post("https://graph.facebook.com/v21.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => ltrim($recipientWaId, '+'),
                    'type' => 'location',
                    'location' => [
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                        'name' => $name,
                        'address' => $address,
                    ],
                ]);

            if ($response->failed()) {
                Log::warning('sendWhatsAppLocation: la petición a Meta Cloud API falló', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('sendWhatsAppLocation exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Toggle typing indicator status on a Chatwoot conversation.
     */
    public function toggleTypingStatus(int $conversationId, bool $isTyping = true): array
    {
        if (! config('chatwoot.send_responses', true)) {
            return [];
        }

        try {
            $endpoint = "{$this->url}/api/v1/accounts/{$this->accountId}/conversations/{$conversationId}/toggle_typing_status";

            $response = Http::withHeaders([
                'api_access_token' => $this->apiToken,
            ])
                ->acceptJson()
                ->post($endpoint, [
                    'typing_status' => $isTyping ? 'on' : 'off',
                ]);

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::warning('Chatwoot API toggleTypingStatus exception', ['error' => $e->getMessage()]);

            return [];
        }
    }
}
