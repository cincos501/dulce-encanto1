<?php

declare(strict_types=1);

namespace App\Baneco\Services;

use App\Baneco\Contracts\EncryptionServiceInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BanecoAuthenticationService
{
    protected string $cacheKey = 'baneco_bearer_token';

    public function __construct(
        protected EncryptionServiceInterface $encryptionService
    ) {}

    /**
     * Get the Bearer Token from cache, or request a new one if not cached.
     */
    public function getAccessToken(): string
    {
        $token = Cache::get($this->cacheKey);
        if ($token) {
            return (string) $token;
        }

        return $this->authenticate();
    }

    /**
     * Authenticate against Baneco API and cache the token.
     */
    public function authenticate(): string
    {
        $baseUrl = config('baneco.base_url');
        $username = config('baneco.username');
        $password = config('baneco.password');

        if (empty($username) || empty($password)) {
            Log::channel('baneco')->warning('BanecoAuthenticationService: Credentials are not configured.');
            return '';
        }

        // Encrypt the password using AES-256 before sending it
        $encryptedPassword = $this->encryptionService->encrypt($password);

        try {
            $response = Http::timeout(config('baneco.timeout', 30))
                ->withHeaders(['Accept' => 'application/json'])
                ->post(rtrim($baseUrl, '/') . '/api/authentication/authenticate', [
                    'userName' => $username,
                    'password' => $encryptedPassword
                ]);

            if (!$response->successful()) {
                Log::channel('baneco')->error('BanecoAuthenticationService: HTTP request failed.', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return '';
            }

            $data = $response->json();
            $responseCode = $data['responseCode'] ?? -1;

            if ($responseCode !== 0) {
                Log::channel('baneco')->error('BanecoAuthenticationService: Authentication rejected by bank.', [
                    'code' => $responseCode,
                    'message' => $data['message'] ?? 'No message provided'
                ]);
                return '';
            }

            $token = $data['token'] ?? null;
            if (!$token) {
                Log::channel('baneco')->error('BanecoAuthenticationService: Response body missing token.');
                return '';
            }

            // Cache token for 55 minutes to allow auto-refresh before standard 1 hour expiration
            Cache::put($this->cacheKey, $token, now()->addMinutes(55));

            Log::channel('baneco')->info('BanecoAuthenticationService: Token fetched and cached successfully.');

            return $token;
        } catch (\Throwable $e) {
            Log::channel('baneco')->error('BanecoAuthenticationService: Authentication exception occurred.', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Invalidate the cached bearer token.
     */
    public function invalidateToken(): void
    {
        Cache::forget($this->cacheKey);
        Log::channel('baneco')->info('BanecoAuthenticationService: Cached token invalidated.');
    }
}
