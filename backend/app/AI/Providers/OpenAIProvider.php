<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIProvider implements LLMProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;
    protected float $temperature;
    protected float $topP;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.openai.key');
        $this->model = (string) config('ai.providers.openai.model', 'gpt-4o-mini');
        $this->apiUrl = (string) config('ai.providers.openai.url', 'https://api.openai.com/v1/chat/completions');
        $this->temperature = (float) config('ai.providers.openai.temperature', 0.0);
        $this->topP = (float) config('ai.providers.openai.top_p', 0.0);
    }

    /**
     * Send messages to the OpenAI API and return reply and tool calls.
     */
    public function chat(array $messages, ?array $tools = null): array
    {
        $maxRetries = 4;
        $attempt = 0;
        $delay = 2; // initial delay in seconds

        while ($attempt < $maxRetries) {
            $attempt++;
            try {
                $payload = [
                    'model' => $this->model,
                    'messages' => $messages,
                    'temperature' => $this->temperature,
                    'top_p' => $this->topP,
                ];

                if (!empty($tools)) {
                    $payload['tools'] = $tools;
                    $payload['tool_choice'] = 'auto';
                }

                Log::debug('Sending request to OpenAI API', [
                    'url' => $this->apiUrl,
                    'payload' => $payload,
                    'attempt' => $attempt
                ]);

                $response = Http::withToken($this->apiKey)
                    ->acceptJson()
                    ->post($this->apiUrl, $payload);

                if ($response->status() === 429) {
                    if ($attempt < $maxRetries) {
                        $retryAfter = null;
                        
                        // 1. Try to read Retry-After header
                        $headerRetry = $response->header('Retry-After') ?? $response->header('retry-after');
                        if ($headerRetry) {
                            $retryAfter = (float) $headerRetry;
                        }
                        
                        // 2. Try to parse from response body message
                        if (!$retryAfter) {
                            $bodyText = $response->body();
                            if (preg_match('/try again in (\d+(\.\d+)?)\s*s/i', $bodyText, $matches)) {
                                $retryAfter = (float) $matches[1];
                            }
                        }
                        
                        // 3. Fallback to exponential delay if not found
                        $sleepTime = $retryAfter ? (int) ceil($retryAfter) + 1 : $delay;
                        
                        Log::warning("OpenAI rate limit hit (429). Waiting {$sleepTime} seconds before retry. (Attempt {$attempt} of {$maxRetries})", [
                            'body' => $response->body()
                        ]);
                        
                        sleep($sleepTime);
                        $delay *= 2;
                        continue;
                    }
                }

                if ($response->failed()) {
                    Log::error('OpenAI LLM request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    throw new \Exception('Failed to communicate with OpenAI LLM API: ' . $response->body());
                }

                $data = $response->json();
                
                Log::debug('Received response from OpenAI API', [
                    'response' => $data
                ]);

                $message = $data['choices'][0]['message'] ?? null;

                if ($message === null) {
                    Log::error('OpenAI LLM returned an empty or invalid choices format', [
                        'payload' => $data
                    ]);
                    throw new \Exception('Invalid response structure received from OpenAI LLM.');
                }

                return [
                    'reply' => $message['content'] ?? null,
                    'tool_calls' => $message['tool_calls'] ?? null,
                ];
            } catch (\Throwable $e) {
                if ($attempt < $maxRetries && str_contains(strtolower($e->getMessage()), 'rate limit')) {
                    $retryAfter = null;
                    if (preg_match('/try again in (\d+(\.\d+)?)\s*s/i', $e->getMessage(), $matches)) {
                        $retryAfter = (float) $matches[1];
                    }
                    $sleepTime = $retryAfter ? (int) ceil($retryAfter) + 1 : $delay;
                    Log::warning("Rate limit exception caught. Waiting {$sleepTime} seconds...", ['error' => $e->getMessage()]);
                    sleep($sleepTime);
                    $delay *= 2;
                    continue;
                }
                Log::error('OpenAI Provider exception during LLM chat', ['error' => $e->getMessage()]);
                throw $e;
            }
        }
        throw new \Exception('Failed to communicate with OpenAI LLM API after maximum retries due to rate limiting.');
    }
}
