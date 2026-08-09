<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiProvider implements LLMProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;
    protected float $temperature;
    protected float $topP;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.openai.key');
        $this->model = (string) config('ai.providers.openai.model', 'gemini-1.5-flash');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";
        $this->temperature = (float) config('ai.providers.openai.temperature', 0.2);
        $this->topP = (float) config('ai.providers.openai.top_p', 0.9);
    }

    /**
     * Send messages to the Gemini Native API and return reply and tool calls.
     */
    public function chat(array $messages, ?array $tools = null): array
    {
        $maxRetries = 3;
        $attempt = 0;
        $delay = 2;

        // 1. Translate messages from OpenAI format to Gemini native format
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';

            if ($role === 'system') {
                $systemInstruction = [
                    'parts' => [
                        ['text' => $content]
                    ]
                ];
                continue;
            }

            $parts = [];

            // Assistant tool calls
            if ($role === 'assistant' || $role === 'model') {
                $role = 'model';
                
                if ($content !== null && $content !== '') {
                    $parts[] = ['text' => $content];
                }

                if (!empty($msg['tool_calls'])) {
                    foreach ($msg['tool_calls'] as $tc) {
                        $args = is_string($tc['function']['arguments']) 
                            ? json_decode($tc['function']['arguments'], true) 
                            : $tc['function']['arguments'];
                        $part = [
                            'functionCall' => [
                                'name' => $tc['function']['name'],
                                'args' => $args ?: (object)[]
                            ]
                        ];
                        if (isset($tc['thought_signature'])) {
                            $part['thoughtSignature'] = $tc['thought_signature'];
                        }
                        $parts[] = $part;
                    }
                }
            }
            // Tool response
            elseif ($role === 'tool') {
                $role = 'user';
                $toolResult = is_string($content) ? json_decode($content, true) : $content;
                if ($toolResult === null) {
                    $toolResult = ['output' => $content];
                }

                $parts[] = [
                    'functionResponse' => [
                        'name' => $msg['name'] ?? '',
                        'response' => $toolResult
                    ]
                ];
            } 
            // Regular User message
            else {
                if ($content !== null && $content !== '') {
                    $parts[] = ['text' => $content];
                }
            }

            if (empty($parts)) {
                $parts[] = ['text' => ''];
            }

            // CORRECCIÓN CLAVE DE GEMINI: Agrupar tool responses seguidos en un mismo turno 'user'
            $lastIndex = count($contents) - 1;
            if ($role === 'user' && isset($msg['role']) && $msg['role'] === 'tool' && $lastIndex >= 0 && $contents[$lastIndex]['role'] === 'user') {
                // Verificar si el bloque previo también contenía functionResponse
                $hasFunctionResponse = false;
                foreach ($contents[$lastIndex]['parts'] as $p) {
                    if (isset($p['functionResponse'])) {
                        $hasFunctionResponse = true;
                        break;
                    }
                }

                if ($hasFunctionResponse) {
                    // Combinar las partes en el bloque anterior en vez de crear uno nuevo
                    $contents[$lastIndex]['parts'] = array_merge($contents[$lastIndex]['parts'], $parts);
                    continue;
                }
            }

            $contents[] = [
                'role' => $role,
                'parts' => $parts
            ];
        }

        // Sanity Check: Ensure Gemini contents payload starts cleanly with a 'user' turn containing text
        if (!empty($contents)) {
            $firstRole = $contents[0]['role'] ?? '';
            $hasFunctionResponse = false;
            foreach ($contents[0]['parts'] as $part) {
                if (isset($part['functionResponse'])) {
                    $hasFunctionResponse = true;
                    break;
                }
            }

            // If the history starts with a model call or tool response (without the preceding user prompt),
            // prepend the last human user message at the beginning to keep Gemini payload structure valid.
            if ($firstRole === 'model' || $hasFunctionResponse) {
                $lastUserText = 'Consulta sobre productos y pedidos';
                foreach (array_reverse($messages) as $m) {
                    if (($m['role'] ?? '') === 'user' && !empty($m['content']) && !isset($m['tool_call_id']) && !isset($m['name'])) {
                        $lastUserText = $m['content'];
                        break;
                    }
                }

                array_unshift($contents, [
                    'role' => 'user',
                    'parts' => [['text' => $lastUserText]]
                ]);
            }
        }

        if (empty($contents)) {
            $contents = [
                [
                    'role' => 'user',
                    'parts' => [['text' => 'Hola']]
                ]
            ];
        }

        // 2. Translate tools from OpenAI format to Gemini native format
        $geminiTools = null;
        if (!empty($tools)) {
            $declarations = [];
            foreach ($tools as $t) {
                if (($t['type'] ?? '') === 'function') {
                    $fn = $t['function'];
                    
                    // Convert schema parameter types to uppercase for Gemini strictness
                    $params = $fn['parameters'] ?? [];
                    if (!empty($params)) {
                        $params = $this->convertTypesToUppercase($params);
                    }

                    $declarations[] = [
                        'name' => $fn['name'],
                        'description' => $fn['description'] ?? '',
                        'parameters' => $params ?: (object)[]
                    ];
                }
            }
            if (!empty($declarations)) {
                $geminiTools = [
                    ['function_declarations' => $declarations]
                ];
            }
        }

        $payload = [
            'contents' => $contents
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        if ($geminiTools) {
            $payload['tools'] = $geminiTools;
        }

        $payload['generationConfig'] = [
            'temperature' => $this->temperature,
            'topP' => $this->topP,
        ];

        $url = $this->apiUrl . '?key=' . $this->apiKey;

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                Log::debug('Sending request to Gemini Native API', [
                    'url' => $this->apiUrl,
                    'payload' => $payload,
                    'attempt' => $attempt
                ]);

                $response = Http::acceptJson()->post($url, $payload);

                if ($response->status() === 429) {
                    if ($attempt < $maxRetries) {
                        Log::warning("Gemini Native API rate limit hit (429). Waiting {$delay} seconds before retry.");
                        sleep($delay);
                        $delay *= 2;
                        continue;
                    }
                }

                if ($response->failed()) {
                    Log::error('Gemini Native API request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    throw new \Exception('Failed to communicate with Gemini Native API: ' . $response->body());
                }

                $data = $response->json();
                
                Log::debug('Received response from Gemini Native API', [
                    'response' => $data
                ]);

                $candidate = $data['candidates'][0] ?? null;
                $contentObj = $candidate['content'] ?? null;
                $parts = $contentObj['parts'] ?? [];
                $globalThoughtSignature = $candidate['thoughtSignature'] ?? null;

                $reply = null;
                $toolCalls = null;

                foreach ($parts as $part) {
                    if (isset($part['text'])) {
                        $reply = $part['text'];
                    }
                    if (isset($part['functionCall'])) {
                        if ($toolCalls === null) {
                            $toolCalls = [];
                        }
                        $fc = $part['functionCall'];
                        $thoughtSig = $part['thoughtSignature'] ?? $globalThoughtSignature ?? null;
                        
                        $tcObj = [
                            'id' => 'call_' . uniqid(),
                            'type' => 'function',
                            'function' => [
                                'name' => $fc['name'],
                                'arguments' => json_encode($fc['args'] ?? [])
                            ]
                        ];
                        if ($thoughtSig) {
                            $tcObj['thought_signature'] = $thoughtSig;
                        }
                        $toolCalls[] = $tcObj;
                    }
                }

                return [
                    'reply' => $reply,
                    'tool_calls' => $toolCalls,
                ];

            } catch (\Throwable $e) {
                if ($attempt < $maxRetries) {
                    Log::warning("Gemini Native API exception. Retrying in {$delay} seconds...", ['error' => $e->getMessage()]);
                    sleep($delay);
                    $delay *= 2;
                    continue;
                }
                Log::error('Gemini Native Provider exception during LLM chat', ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        throw new \Exception('Failed to communicate with Gemini Native API after maximum retries.');
    }

    /**
     * Recursively convert type property values to uppercase.
     */
    private function convertTypesToUppercase(array $schema): array
    {
        if (isset($schema['type'])) {
            if (is_array($schema['type'])) {
                $types = array_filter($schema['type'], fn($t) => $t !== 'null');
                $type = reset($types) ?: 'string';
                $schema['type'] = strtoupper((string) $type);
            } else {
                $schema['type'] = strtoupper((string) $schema['type']);
            }
        }
        if (isset($schema['properties']) && is_array($schema['properties'])) {
            foreach ($schema['properties'] as $key => $prop) {
                if (is_array($prop)) {
                    $schema['properties'][$key] = $this->convertTypesToUppercase($prop);
                }
            }
        }
        if (isset($schema['items']) && is_array($schema['items'])) {
            $schema['items'] = $this->convertTypesToUppercase($schema['items']);
        }
        return $schema;
    }
}