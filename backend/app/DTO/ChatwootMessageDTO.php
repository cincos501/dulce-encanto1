<?php

declare(strict_types=1);

namespace App\DTO;

use App\Support\ChatwootAttachmentExtractor;
use App\Support\PhoneHelper;

class ChatwootMessageDTO
{
    /**
     * @param  list<array<string, mixed>>  $attachmentRefs  Referencias ligeras de adjuntos (ChatwootAttachmentExtractor).
     */
    public function __construct(
        public readonly int $conversationId,
        public readonly string $phone,
        public readonly string $senderName,
        public readonly string $text,
        public readonly string $messageType,
        public readonly array $rawPayload,
        public readonly array $attachmentRefs = [],
        public readonly bool $hasMedia = false,
    ) {}

    /**
     * Create DTO from incoming Chatwoot webhook payload.
     */
    public static function fromWebhook(array $payload): self
    {
        $conversation = $payload['conversation'] ?? [];

        // Robust fallback to retrieve the conversation ID
        $conversationId = (int) (
            $conversation['id'] ??
            $payload['conversation_id'] ??
            0
        );

        // Robust fallbacks to retrieve the client's phone number
        $contactInbox = $conversation['contact_inbox'] ?? [];
        $phone = (string) (
            $contactInbox['source_id'] ??
            $payload['sender']['phone_number'] ??
            $conversation['contact']['phone_number'] ??
            ''
        );

        $sender = $payload['sender'] ?? [];
        $senderName = (string) ($sender['name'] ?? '');

        $text = (string) ($payload['content'] ?? '');

        // Los adjuntos (audio, imágenes, stickers, ubicación) se normalizan a
        // referencias ligeras. El texto real se resuelve luego en el Job vía
        // IncomingMediaResolver (transcripción / visión), por lo que aquí NO se
        // genera ningún placeholder de "archivo adjunto".
        $attachmentRefs = ChatwootAttachmentExtractor::fromPayload($payload);

        $messageType = (string) ($payload['message_type'] ?? '');

        return new self(
            conversationId: $conversationId,
            phone: PhoneHelper::normalize($phone),
            senderName: $senderName,
            text: trim($text),
            messageType: $messageType,
            rawPayload: $payload,
            attachmentRefs: $attachmentRefs,
            hasMedia: $attachmentRefs !== [],
        );
    }
}
