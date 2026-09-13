<?php

declare(strict_types=1);

namespace App\AI\Media;

use App\AI\Contracts\ImageAnalyzerInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Clasificador visual de imágenes basado en la API nativa de Google Gemini.
 */
class GeminiVisionAnalyzer implements ImageAnalyzerInterface
{
    private const ALLOWED = ['comprobante_pago', 'diseno_referencia', 'foto_producto', 'otro'];

    public function analyze(string $image, string $mime): ?array
    {
        $key = (string) config('ai.media.vision.gemini.key');
        $model = (string) config('ai.media.vision.gemini.model', 'gemini-2.0-flash');

        if ($key === '') {
            Log::warning('GeminiVisionAnalyzer: API key vacía, se omite el análisis visual.');

            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $prompt = <<<'TXT'
        Eres el clasificador visual de una pastelería. Analiza la imagen enviada por el cliente y responde
        ÚNICAMENTE con un JSON válido, sin texto adicional, con esta forma exacta:
        {"category": "<comprobante_pago|diseno_referencia|foto_producto|otro>", "summary": "<descripción breve en español, máximo 25 palabras>"}

        Definiciones:
        - comprobante_pago: captura o foto de una transferencia bancaria, un QR ya pagado, un recibo o voucher.
        - diseno_referencia: foto o dibujo de una torta/pastel que el cliente quiere que se le replique.
        - foto_producto: foto de un producto de pastelería sobre el que el cliente pregunta.
        - otro: cualquier otra cosa.
        TXT;

        try {
            $response = Http::timeout(40)->post($url, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => [
                            'mime_type' => $mime,
                            'data' => base64_encode($image),
                        ]],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0.0,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->failed()) {
                Log::error('GeminiVisionAnalyzer: la petición falló', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $raw = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
            $json = json_decode($raw, true);

            if (! is_array($json)) {
                Log::warning('GeminiVisionAnalyzer: respuesta no parseable', ['raw' => $raw]);

                return null;
            }

            $category = $json['category'] ?? 'otro';

            return [
                'category' => in_array($category, self::ALLOWED, true) ? $category : 'otro',
                'summary' => trim((string) ($json['summary'] ?? '')),
            ];
        } catch (\Throwable $e) {
            Log::error('GeminiVisionAnalyzer: excepción durante el análisis', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
