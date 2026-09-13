<?php

declare(strict_types=1);

namespace App\AI\Media;

use App\AI\Contracts\AudioTranscriberInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transcriptor de audio basado en la API nativa de Google Gemini
 * (endpoint generateContent con inline_data). Driver por defecto.
 */
class GeminiAudioTranscriber implements AudioTranscriberInterface
{
    public function transcribe(string $audio, string $mime): ?string
    {
        $key = (string) config('ai.media.transcription.gemini.key');
        $model = (string) config('ai.media.transcription.gemini.model', 'gemini-2.0-flash');
        $lang = (string) config('ai.media.transcription.language', 'es');

        if ($key === '') {
            Log::warning('GeminiAudioTranscriber: API key vacía, se omite la transcripción.');

            return null;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";

        $prompt = $lang === 'es'
            ? 'Transcribe literalmente este audio al español. Devuelve SOLO el texto transcrito, '
              .'sin comillas, prefijos ni comentarios. Si no hay voz inteligible, responde exactamente: [inaudible].'
            : 'Transcribe this audio verbatim. Return ONLY the transcription text. '
              .'If there is no intelligible speech, reply exactly: [inaudible].';

        try {
            $response = Http::timeout(45)->post($url, [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        ['inline_data' => [
                            'mime_type' => $mime,
                            'data' => base64_encode($audio),
                        ]],
                    ],
                ]],
                'generationConfig' => ['temperature' => 0.0],
            ]);

            if ($response->failed()) {
                Log::error('GeminiAudioTranscriber: la petición falló', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = trim((string) data_get($response->json(), 'candidates.0.content.parts.0.text', ''));

            if ($text === '' || strtolower($text) === '[inaudible]') {
                return null;
            }

            return $text;
        } catch (\Throwable $e) {
            Log::error('GeminiAudioTranscriber: excepción durante la transcripción', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
