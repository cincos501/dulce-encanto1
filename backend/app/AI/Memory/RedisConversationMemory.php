<?php

declare(strict_types=1);

namespace App\AI\Memory;

use App\AI\Contracts\ConversationMemoryInterface;
use App\Models\WhatsAppSession;
use App\Repositories\WhatsAppSessionRepositoryInterface;

class RedisConversationMemory implements ConversationMemoryInterface
{
    public function __construct(
        protected WhatsAppSessionRepositoryInterface $sessionRepository
    ) {}

    /**
     * Load the WhatsAppSession context from Redis, or create a new one.
     */
    public function loadSession(string $phone, string $senderName): WhatsAppSession
    {
        $sessionData = $this->sessionRepository->get($phone);

        return $sessionData 
            ? new WhatsAppSession($sessionData) 
            : new WhatsAppSession([
                'phone' => $phone,
                'name' => $senderName,
                'step' => 'idle',
                'order_data' => [],
                'history' => [],
            ]);
    }

    /**
     * Add a message to the session's conversational history.
     */
    public function addMessage(WhatsAppSession $session, string $role, string $content): void
    {
        $history = $session->history;
        $history[] = [
            'role' => $role,
            'content' => $content,
        ];

        // Keep history bounded to the last 12 messages
        if (count($history) > 12) {
            $history = array_slice($history, -12);
        }

        $session->history = $history;
    }

    /**
     * Add a raw message structure (e.g. tool calls or responses) to history.
     */
    public function addMessageRaw(WhatsAppSession $session, array $message): void
    {
        $history = $session->history;
        $history[] = $message;

        // Keep history bounded to the last 12 messages
        if (count($history) > 12) {
            $history = array_slice($history, -12);
        }

        $session->history = $history;
    }

    /**
     * Save the session context back to Redis.
     */
    public function saveSession(WhatsAppSession $session): void
    {
        $session->updatedAt = now()->toIso8601String();
        $this->sessionRepository->set($session->phone, $session->toArray());
    }

    /**
     * Delete the conversation memory from Redis.
     */
    public function clearSession(string $phone): void
    {
        $this->sessionRepository->delete($phone);
    }
}
