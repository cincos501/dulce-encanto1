<?php

declare(strict_types=1);

namespace App\AI\Media;

use App\AI\Contracts\AudioTranscriberInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Transcriptor de audio basado en la API de OpenAI Whisper
 * (POST v1/audio/transcriptions, multipart).
 */
class OpenAIWhisperTranscriber implements AudioTranscriberInterface
{
    public function transcribe(string $audio, string $mime): ?string
    {
        $key = (string) config('ai.media.transcription.openai_whisper.key');
        $model = (string) config('ai.media.transcription.openai_whisper.model', 'whisper-1');
        $url = (string) config('ai.media.transcription.openai_whisper.url', 'https://api.openai.com/v1/audio/transcriptions');
        $lang = (string) config('ai.media.transcription.language', 'es');

        if ($key === '') {
            Log::warning('OpenAIWhisperTranscriber: WHISPER_API_KEY vacía, se omite la transcripción.');

            return null;
        }

        $extension = match ($mime) {
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/wav' => 'wav',
            'audio/amr' => 'amr',
            'audio/aac' => 'aac',
            default => 'ogg',
        };

        try {
            $response = Http::withToken($key)
                ->timeout(60)
                ->attach('file', $audio, "audio.{$extension}")
                ->post($url, [
                    'model' => $model,
                    'language' => $lang,
                    'response_format' => 'text',
                    'temperature' => 0,
                ]);

            if ($response->failed()) {
                Log::error('OpenAIWhisperTranscriber: la petición falló', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $text = trim($response->body());

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::error('OpenAIWhisperTranscriber: excepción durante la transcripción', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
