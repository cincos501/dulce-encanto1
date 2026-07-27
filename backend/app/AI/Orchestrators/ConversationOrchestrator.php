<?php

declare(strict_types=1);

namespace App\AI\Orchestrators;

use App\AI\Contracts\ConversationMemoryInterface;
use App\AI\Services\AIConversationService;
use App\AI\Registry\ToolRegistry;
use App\DTO\ChatwootMessageDTO;
use App\Services\ChatwootService;
use Illuminate\Support\Facades\Log;

use App\AI\Orders\OrderDraftManager;

class ConversationOrchestrator
{
    public function __construct(
        protected ConversationMemoryInterface $memory,
        protected AIConversationService $aiService,
        protected ChatwootService $chatwootService,
        protected ToolRegistry $toolRegistry,
        protected OrderDraftManager $draftManager
    ) {}

    /**
     * Coordinate the entire conversational flow including tool execution loops.
     */
    public function handle(ChatwootMessageDTO $message): string
    {
        $phone = $message->phone;

        // 1. Load context memory from Redis
        $session = $this->memory->loadSession($phone, $message->senderName);

        // 2. Append user's incoming message to history
        $this->memory->addMessage($session, 'user', $message->text);
        $session->lastMessage = $message->text;

        // Check if there is an active draft (has items in cart) to decide on tools filtering
        $hasActiveDraft = false;
        try {
            $draft = $this->draftManager->getDraft($phone);
            $hasActiveDraft = !empty($draft->items);
        } catch (\Throwable $e) {
            Log::warning('Error checking active draft in orchestrator', ['error' => $e->getMessage()]);
        }

        // 3. Get registered tools schema dynamically filtered by context
        $toolsSchema = $this->toolRegistry->getToolsSchema($message->text, $hasActiveDraft);

        $maxIterations = 5; // Prevent infinite tool calling loops
        $iteration = 0;
        $reply = null;

        while ($iteration < $maxIterations) {
            $iteration++;

            Log::debug('AI model call started', [
                'iteration' => $iteration,
                'phone' => $phone
            ]);

            // Call LLM
            $aiResponse = $this->aiService->generateReply($session->history, $toolsSchema);

            if (!empty($aiResponse->toolCalls)) {
                Log::info('AI decided to execute tool calls', [
                    'tool_calls' => $aiResponse->toolCalls,
                    'phone' => $phone
                ]);

                // Append assistant message containing the tool calls to history
                $assistantMsg = [
                    'role' => 'assistant',
                    'content' => $aiResponse->reply ?? '',
                    'tool_calls' => $aiResponse->toolCalls,
                ];
                $this->memory->addMessageRaw($session, $assistantMsg);

                // Execute each tool call
                foreach ($aiResponse->toolCalls as $toolCall) {
                    $toolName = $toolCall['function']['name'] ?? '';
                    $toolCallId = $toolCall['id'] ?? '';
                    $arguments = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?: [];

                    Log::info("Executing tool: {$toolName}", [
                        'tool_call_id' => $toolCallId,
                        'arguments' => $arguments,
                        'phone' => $phone
                    ]);

                    $tool = $this->toolRegistry->get($toolName);
                    if ($tool) {
                        try {
                            $result = $tool->execute($arguments, ['phone' => $phone]);
                            Log::info("Tool {$toolName} executed successfully", [
                                'result_summary' => substr($result, 0, 300) . (strlen($result) > 300 ? '...' : '')
                            ]);
                        } catch (\Throwable $e) {
                            Log::error("Error executing tool {$toolName}", ['error' => $e->getMessage()]);
                            $result = "Error al ejecutar la herramienta: " . $e->getMessage();
                        }
                    } else {
                        Log::warning("Tool {$toolName} not found in registry");
                        $result = "Herramienta '{$toolName}' no encontrada.";
                    }

                    // Append tool response to history
                    $toolMsg = [
                        'role' => 'tool',
                        'name' => $toolName,
                        'tool_call_id' => $toolCallId,
                        'content' => $result,
                    ];
                    $this->memory->addMessageRaw($session, $toolMsg);
                }

                // Loop again to feed results back to the LLM
                continue;
            }

            // No tool calls means this is the final textual answer
            $reply = $aiResponse->reply ?? '';
            Log::info('AI final response generated', [
                'reply' => $reply,
                'phone' => $phone
            ]);
            $this->memory->addMessage($session, 'assistant', $reply);
            break;
        }

        // 4. Save updated context back to Redis
        $this->memory->saveSession($session);

        // 5. Send final reply to Chatwoot
        $this->chatwootService->sendMessage(
            $message->conversationId,
            $reply ?? 'Lo siento, no pude procesar tu solicitud.'
        );

        Log::info('ConversationOrchestrator successfully processed message', [
            'conversation_id' => $message->conversationId,
            'phone' => $phone,
            'step' => $session->step,
        ]);

        return $reply ?? '';
    }
}
