<?php

declare(strict_types=1);

namespace App\AI\Services;

use App\AI\Contracts\LLMProviderInterface;
use App\AI\DTO\AIResponseDTO;
use App\AI\Prompts\DulceEncantoPrompt;

use Illuminate\Support\Facades\Log;

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
        $systemPrompt = DulceEncantoPrompt::getSystemPrompt();

        // 1. Build initial message list for LLM (including system prompt)
        $messages = [];
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        // 2. Safe history pruning (keep last 10 messages max by default, starting on a 'user' message)
        $recentHistory = self::pruneHistory($history, 10);

        // 3. Token Estimation & Safety Control Loop
        $promptTokens = (int) ceil(strlen($systemPrompt) / 4);
        $toolsTokens = empty($tools) ? 0 : (int) ceil(strlen(json_encode($tools)) / 4);
        
        while (count($recentHistory) > 0) {
            $historyTokens = 0;
            foreach ($recentHistory as $msg) {
                $content = $msg['content'] ?? '';
                if (!empty($msg['tool_calls'])) {
                    $content .= json_encode($msg['tool_calls']);
                }
                $historyTokens += (int) ceil(strlen($content) / 4);
            }

            $totalEstimatedTokens = $promptTokens + $toolsTokens + $historyTokens;

            // If under 3000 tokens limit, we are good to go
            if ($totalEstimatedTokens <= 3000) {
                break;
            }

            // Otherwise, recursively prune oldest messages
            $recentHistory = self::pruneHistory(array_slice($recentHistory, 1), count($recentHistory) - 1);
        }

        // Final count of history tokens
        $historyTokens = 0;
        foreach ($recentHistory as $msg) {
            $content = $msg['content'] ?? '';
            if (!empty($msg['tool_calls'])) {
                $content .= json_encode($msg['tool_calls']);
            }
            $historyTokens += (int) ceil(strlen($content) / 4);
        }
        $totalEstimatedTokens = $promptTokens + $toolsTokens + $historyTokens;

        // Log Token Usage Estimates
        Log::info('Groq Token Estimation & Safety Control', [
            'tokens_estimated_sent' => $totalEstimatedTokens,
            'tokens_history' => $historyTokens,
            'tokens_prompt' => $promptTokens,
            'tokens_tools' => $toolsTokens,
            'messages_count_sent' => count($recentHistory),
            'original_history_count' => count($history),
        ]);

        foreach ($recentHistory as $msg) {
            $messages[] = $msg;
        }

        // 4. Invoke the active LLM Provider
        $result = $this->llmProvider->chat($messages, $tools);

        return new AIResponseDTO(
            reply: $result['reply'] ?? null,
            toolCalls: $result['tool_calls'] ?? null,
            rawResponse: $result
        );
    }

    /**
     * Bounded history pruner starting cleanly on 'user' messages to avoid orphaned tool calls.
     */
    public static function pruneHistory(array $history, int $maxMessages = 10): array
    {
        if (count($history) <= $maxMessages) {
            return $history;
        }

        $total = count($history);
        $cutoff = $total - $maxMessages;

        // Advance to a user message
        while ($cutoff < $total && $history[$cutoff]['role'] !== 'user') {
            $cutoff++;
        }

        // Fallback: search backwards for the last user message
        if ($cutoff >= $total) {
            for ($i = $total - 1; $i >= 0; $i--) {
                if ($history[$i]['role'] === 'user') {
                    $cutoff = $i;
                    break;
                }
            }
        }

        return array_slice($history, $cutoff);
    }
}
