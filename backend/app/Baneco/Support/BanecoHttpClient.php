<?php

declare(strict_types=1);

namespace App\Baneco\Support;

use App\Baneco\Services\BanecoAuthenticationService;
use App\Baneco\Exceptions\BanecoApiException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BanecoHttpClient
{
    public function __construct(
        protected BanecoAuthenticationService $authService
    ) {}

    /**
     * Send a request to the Baneco API.
     */
    public function request(string $method, string $uri, array $data = []): array
    {
        $method = strtoupper($method);
        $baseUrl = rtrim((string) config('baneco.base_url'), '/');
        $url = $baseUrl . '/' . ltrim($uri, '/');

        $token = $this->authService->getAccessToken();

        $startTime = microtime(true);
        $retryCount = 3;
        $retryDelayMs = 100;

        $loggedData = $this->maskSensitiveData($data);
        Log::channel('baneco')->info("Baneco Request: {$method} {$url}", [
            'payload' => $loggedData
        ]);

        $response = null;
        for ($i = 0; $i < $retryCount; $i++) {
            try {
                $request = Http::timeout((int) config('baneco.timeout', 30))
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->withToken($token);

                if ($method === 'GET') {
                    $response = $request->get($url, $data);
                } elseif ($method === 'POST') {
                    $response = $request->post($url, $data);
                } elseif ($method === 'PUT') {
                    $response = $request->put($url, $data);
                } elseif ($method === 'DELETE') {
                    $response = $request->delete($url, $data);
                } else {
                    throw new BanecoApiException("Unsupported HTTP method: {$method}");
                }

                if ($response->successful()) {
                    break;
                }

                // If unauthorized (401), invalidate token to force fetch on next retry/request
                if ($response->status() === 401) {
                    $this->authService->invalidateToken();
                    $token = $this->authService->getAccessToken();
                }

            } catch (\Throwable $e) {
                Log::channel('baneco')->warning("Baneco Request attempt " . ($i + 1) . " failed.", [
                    'error' => $e->getMessage()
                ]);
            }

            if ($i < $retryCount - 1) {
                usleep($retryDelayMs * 1000);
            }
        }

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if (!$response || !$response->successful()) {
            $status = $response ? $response->status() : 'unknown';
            $body = $response ? $response->body() : 'No response body';
            
            Log::channel('baneco')->error("Baneco Request Failed: {$method} {$url} [Status: {$status}] [Duration: {$duration}ms]", [
                'response' => $body
            ]);

            throw new BanecoApiException("Baneco API error: [Status: {$status}] [Response: {$body}]");
        }

        Log::channel('baneco')->info("Baneco Response: [Status: " . $response->status() . "] [Duration: {$duration}ms]", [
            'response' => $response->json()
        ]);

        return $response->json();
    }

    /**
     * Mask sensitive fields from logging payload.
     */
    protected function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'aeskey', 'aes_key', 'token', 'accountCredit', 'accountCode'];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            } elseif (in_array($key, $sensitiveKeys, true)) {
                $data[$key] = '********';
            }
        }
        return $data;
    }
}
