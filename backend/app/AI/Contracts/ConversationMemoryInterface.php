<?php

declare(strict_types=1);

namespace App\AI\Contracts;

use App\Models\WhatsAppSession;

interface ConversationMemoryInterface
{
    /**
     * Load the WhatsAppSession context, or create a new one.
     */
    public function loadSession(string $phone, string $senderName): WhatsAppSession;

    /**
     * Add a message to the session's conversational history.
     */
    public function addMessage(WhatsAppSession $session, string $role, string $content): void;

    /**
     * Add a raw message structure (e.g. tool calls or responses) to history.
     */
    public function addMessageRaw(WhatsAppSession $session, array $message): void;

    /**
     * Save the session context.
     */
    public function saveSession(WhatsAppSession $session): void;

    /**
     * Delete the conversation memory.
     */
    public function clearSession(string $phone): void;
}
