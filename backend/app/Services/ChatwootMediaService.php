<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatwootMediaService
{
    /**
     * Descarga el binario de un adjunto de Chatwoot.
     *
     * Intenta primero de forma anónima (los blobs de ActiveStorage suelen ser
     * públicos) y luego con el api_access_token como cabecera.
     *
     * @return array{contents: string, mime: string}|null
     */
    public function download(string $url, int $maxBytes): ?array
    {
        $attempts = [
            [],
            ['api_access_token' => (string) config('chatwoot.api_token')],
        ];

        foreach ($attempts as $headers) {
            try {
                $response = Http::withHeaders(array_filter($headers))
                    ->timeout(20)
                    ->get($url);

                if (! $response->successful()) {
                    continue;
                }

                $body = $response->body();

                if ($body === '') {
                    continue;
                }

                if (strlen($body) > $maxBytes) {
                    Log::warning('ChatwootMediaService: adjunto excede el tamaño máximo permitido', [
                        'url' => $url,
                        'bytes' => strlen($body),
                        'max_bytes' => $maxBytes,
                    ]);

                    return null;
                }

                return [
                    'contents' => $body,
                    'mime' => $this->normalizeMime((string) $response->header('Content-Type'), $url),
                ];
            } catch (\Throwable $e) {
                Log::warning('ChatwootMediaService: intento de descarga fallido', [
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::error('ChatwootMediaService: no se pudo descargar el adjunto', ['url' => $url]);

        return null;
    }

    private function normalizeMime(string $headerMime, string $url): string
    {
        $headerMime = trim(strtolower(explode(';', $headerMime)[0]));

        if ($headerMime !== '' && $headerMime !== 'application/octet-stream' && str_contains($headerMime, '/')) {
            return $headerMime;
        }

        return $this->guessMimeFromUrl($url);
    }

    private function guessMimeFromUrl(string $url): string
    {
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($ext) {
            'ogg', 'oga', 'opus' => 'audio/ogg',
            'mp3', 'mpga' => 'audio/mpeg',
            'm4a', 'mp4' => 'audio/mp4',
            'wav' => 'audio/wav',
            'amr' => 'audio/amr',
            'aac' => 'audio/aac',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'heic', 'heif' => 'image/heic',
            default => 'application/octet-stream',
        };
    }
}
