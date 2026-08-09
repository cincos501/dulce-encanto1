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

        $session->history = $this->boundHistory($history);
    }

    /**
     * Add a raw message structure (e.g. tool calls or responses) to history.
     */
    public function addMessageRaw(WhatsAppSession $session, array $message): void
    {
        $history = $session->history;
        $history[] = $message;

        $session->history = $this->boundHistory($history);
    }

    /**
     * Keep history bounded to the last 12 messages and ensure the first message is always a user message.
     */
    protected function boundHistory(array $history, int $limit = 12): array
    {
        if (count($history) <= $limit) {
            return $history;
        }

        $history = array_slice($history, -$limit);

        // Clean up any leading model/assistant or tool response messages so the history always starts with user
        while (count($history) > 0) {
            $first = $history[0];
            $role = $first['role'] ?? 'user';
            
            if ($role !== 'user' && $role !== 'system') {
                array_shift($history);
                continue;
            }

            // If it's user role, ensure it's not a tool response representation
            if ($role === 'user' && (isset($first['tool_calls']) || isset($first['name']))) {
                array_shift($history);
                continue;
            }

            break;
        }

        return $history;
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
