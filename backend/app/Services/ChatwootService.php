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
        if (!config('chatwoot.send_responses', true)) {
            Log::info('Chatwoot API responses are disabled. Skipping sending message via HTTP.', [
                'conversation_id' => $conversationId,
                'content' => $text
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
                throw new \Exception("Failed to send Chatwoot message: " . $response->body());
            }

            return $response->json() ?? [];
        } catch (\Throwable $e) {
            Log::error('Chatwoot API sendMessage exception', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
