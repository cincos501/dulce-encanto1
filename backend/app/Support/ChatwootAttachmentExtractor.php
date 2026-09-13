<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normaliza los adjuntos y la ubicación de un payload de webhook de Chatwoot
 * a una lista de referencias ligeras (sin descargar los archivos).
 */
class ChatwootAttachmentExtractor
{
    /**
     * @return list<array{
     *   kind: 'audio'|'image'|'sticker'|'video'|'file'|'location',
     *   url: ?string,
     *   file_type: string,
     *   extension: ?string,
     *   latitude: ?float,
     *   longitude: ?float
     * }>
     */
    public static function fromPayload(array $payload): array
    {
        $attachments = $payload['attachments'] ?? [];
        $hasCaption = trim((string) ($payload['content'] ?? '')) !== '';
        $baseUrl = rtrim((string) config('chatwoot.url'), '/');
        $out = [];

        foreach ($attachments as $att) {
            if (! is_array($att)) {
                continue;
            }

            $fileType = (string) ($att['file_type'] ?? 'file');

            if ($fileType === 'location') {
                $out[] = self::locationRef(
                    $att['coordinates_lat'] ?? $att['latitude'] ?? null,
                    $att['coordinates_long'] ?? $att['longitude'] ?? null
                );

                continue;
            }

            $url = $att['data_url'] ?? $att['file_url'] ?? $att['thumb_url'] ?? null;
            if (is_string($url) && $url !== '' && ! str_starts_with($url, 'http')) {
                $url = $baseUrl.'/'.ltrim($url, '/');
            }
            $url = is_string($url) && $url !== '' ? $url : null;

            $extension = $url
                ? strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))
                : null;
            $extension = $extension !== '' ? $extension : null;

            // Heurística de sticker: WhatsApp entrega los stickers como image/webp sin caption.
            $kind = match ($fileType) {
                'audio' => 'audio',
                'video' => 'video',
                'image' => (! $hasCaption && $extension === 'webp') ? 'sticker' : 'image',
                default => 'file',
            };

            $out[] = [
                'kind' => $kind,
                'url' => $url,
                'file_type' => $fileType,
                'extension' => $extension,
                'latitude' => null,
                'longitude' => null,
            ];
        }

        // Algunas integraciones envían la ubicación en content_attributes en lugar de attachments.
        $ca = $payload['content_attributes']['location'] ?? $payload['location'] ?? null;
        if (is_array($ca) && isset($ca['latitude'], $ca['longitude'])) {
            $out[] = self::locationRef($ca['latitude'], $ca['longitude']);
        }

        return $out;
    }

    /**
     * @return array{kind: 'location', url: null, file_type: 'location', extension: null, latitude: ?float, longitude: ?float}
     */
    private static function locationRef(mixed $lat, mixed $long): array
    {
        return [
            'kind' => 'location',
            'url' => null,
            'file_type' => 'location',
            'extension' => null,
            'latitude' => is_numeric($lat) ? (float) $lat : null,
            'longitude' => is_numeric($long) ? (float) $long : null,
        ];
    }
}
