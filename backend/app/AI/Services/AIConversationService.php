<?php

declare(strict_types=1);

namespace App\AI\Services;

use App\AI\Contracts\LLMProviderInterface;
use App\AI\DTO\AIResponseDTO;
use App\AI\Prompts\DulceEncantoPrompt;

class AIConversationService
{
    public function __construct(
        protected LLMProviderInterface $llmProvider
    ) {}

    /**
     * Generate AI reply based on conversation history and optional tool definitions.
     *
     * @param array<array{role: string, content: string}> $history
     * @param array|null $tools
     * @return AIResponseDTO
     */
    public function generateReply(array $history, ?array $tools = null): AIResponseDTO
    {
        // 1. Build message list for LLM (including system prompt)
        $messages = [];
        $messages[] = [
            'role' => 'system',
            'content' => DulceEncantoPrompt::getSystemPrompt(),
        ];

        // Append recent history messages (limit to last 15 messages for token efficiency)
        $recentHistory = array_slice($history, -15);
        foreach ($recentHistory as $msg) {
            $messages[] = $msg;
        }

        // 2. Invoke the active LLM Provider
        $result = $this->llmProvider->chat($messages, $tools);

        return new AIResponseDTO(
            reply: $result['reply'] ?? null,
            toolCalls: $result['tool_calls'] ?? null,
            rawResponse: $result
        );
    }
}
