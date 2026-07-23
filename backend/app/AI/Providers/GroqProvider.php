<?php

declare(strict_types=1);

namespace App\AI\Providers;

use App\AI\Contracts\LLMProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GroqProvider implements LLMProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;
    protected float $temperature;
    protected float $topP;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.providers.groq.key');
        $this->model = (string) config('ai.providers.groq.model', 'llama-3.3-70b-versatile');
        $this->apiUrl = (string) config('ai.providers.groq.url', 'https://api.groq.com/openai/v1/chat/completions');
        $this->temperature = (float) config('ai.providers.groq.temperature', 0.0);
        $this->topP = (float) config('ai.providers.groq.top_p', 0.0);
    }

    /**
     * Send messages to the Groq API and return reply and tool calls.
     */
    public function chat(array $messages, ?array $tools = null): array
    {
        try {
            $payload = [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $this->temperature,
                'top_p' => $this->topP,
            ];

            if (!empty($tools)) {
                $payload['tools'] = $tools;
            }

            Log::debug('Sending request to Groq API', [
                'url' => $this->apiUrl,
                'payload' => $payload
            ]);

            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->post($this->apiUrl, $payload);

            if ($response->failed()) {
                Log::error('Groq LLM request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to communicate with Groq LLM API: ' . $response->body());
            }

            $data = $response->json();
            
            Log::debug('Received response from Groq API', [
                'response' => $data
            ]);

            $message = $data['choices'][0]['message'] ?? null;

            if ($message === null) {
                Log::error('Groq LLM returned an empty or invalid choices format', [
                    'payload' => $data
                ]);
                throw new \Exception('Invalid response structure received from Groq LLM.');
            }

            return [
                'reply' => $message['content'] ?? null,
                'tool_calls' => $message['tool_calls'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Groq Provider exception during LLM chat', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
