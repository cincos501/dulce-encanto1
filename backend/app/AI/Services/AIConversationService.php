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
     * @param string|null $intent
     * @param array|null $toolNames
     * @return AIResponseDTO
     */
    public function generateReply(array $history, ?array $tools = null, ?string $intent = null, ?array $toolNames = null): AIResponseDTO
    {
        $systemPrompt = DulceEncantoPrompt::getSystemPrompt();

        // Inject dynamic real-time date and time context
        $currentDate = now()->format('Y-m-d');
        $currentDateTime = now()->format('Y-m-d H:i');
        $systemPrompt .= "\n\nCONTEXTO DE TIEMPO REAL:\n";
        $systemPrompt .= "- Fecha actual: {$currentDate}\n";
        $systemPrompt .= "- Fecha y hora actual completa: {$currentDateTime}\n";
        $systemPrompt .= "- Año actual: " . now()->year . "\n";
        $systemPrompt .= "Usa siempre este año actual al interpretar fechas del usuario (ej: '30 de julio' se interpreta como '30 de julio de " . now()->year . "').\n";

        // 1. Build initial message list for LLM (including system prompt)
        $messages = [];
        $messages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        // 2. Safe history pruning (keep last 10 messages max, starting on a human 'user' message)
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

            // Safe token limit ceiling for free tier models (4500 tokens)
            if ($totalEstimatedTokens <= 4500) {
                break;
            }

            // Attempt to prune older messages, but ensure we never prune away all human user messages
            $nextHistory = self::pruneHistory(array_slice($recentHistory, 1), count($recentHistory) - 1);
            if (empty($nextHistory)) {
                break; // Stop pruning to protect the active user prompt turn
            }
            $recentHistory = $nextHistory;
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

        // Log Token Usage Estimates and Intent Classification
        Log::info('Groq Token Estimation & Safety Control', [
            'intencion_detectada' => $intent ?? 'no_detectada',
            'tools_enviadas' => $toolNames ?? [],
            'tokens_estimados_antes_groq' => $totalEstimatedTokens,
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
     * Bounded history pruner starting cleanly on human 'user' messages (no tool responses) to avoid orphaned tool calls.
     */
    public static function pruneHistory(array $history, int $maxMessages = 10): array
    {
        $total = count($history);
        if ($total <= $maxMessages) {
            return $history;
        }

        $cutoff = $total - $maxMessages;

        // Helper check to identify true human user messages (role user and not a tool response)
        $isHumanUserMsg = fn($msg) => ($msg['role'] ?? '') === 'user' && !isset($msg['tool_call_id']) && !isset($msg['name']);

        // Scan backwards to find the nearest human 'user' message to start the history cleanly
        while ($cutoff > 0 && !$isHumanUserMsg($history[$cutoff])) {
            $cutoff--;
        }

        // If after scanning backwards, the resulting array size exceeds $maxMessages + 4
        // fallback to scanning forwards
        if (($total - $cutoff) > ($maxMessages + 4)) {
            $cutoff = $total - $maxMessages;
            while ($cutoff < $total && !$isHumanUserMsg($history[$cutoff])) {
                $cutoff++;
            }
            if ($cutoff >= $total) {
                for ($i = $total - 1; $i >= 0; $i--) {
                    if ($isHumanUserMsg($history[$i])) {
                        $cutoff = $i;
                        break;
                    }
                }
            }
        }

        return array_slice($history, $cutoff);
    }
}
