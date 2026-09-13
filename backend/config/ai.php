<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI & Conversational Engine Configurations
    |--------------------------------------------------------------------------
    |
    | Define the active LLM provider, models, and third-party API credentials.
    |
    */

    'provider' => env('AI_PROVIDER', 'groq'),

    'providers' => [
        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama-3.3-70b-versatile'),
            'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions'),
            'temperature' => (float) env('GROQ_TEMPERATURE', 0.0),
            'top_p' => (float) env('GROQ_TOP_P', 0.0),
        ],
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'url' => env('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions'),
            'temperature' => (float) env('OPENAI_TEMPERATURE', 0.0),
            'top_p' => (float) env('OPENAI_TOP_P', 0.0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Procesamiento multimedia (audio / imágenes de WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Transcripción de notas de voz y análisis visual de imágenes recibidas
    | por Chatwoot. Deja el driver vacío para desactivar cada capacidad.
    |
    */

    'media' => [

        'transcription' => [
            // gemini | openai_whisper | null (vacío = desactivado, mantiene el placeholder)
            'driver' => env('AI_TRANSCRIPTION_DRIVER', 'gemini'),
            'language' => env('AI_TRANSCRIPTION_LANGUAGE', 'es'),
            'max_bytes' => (int) env('AI_TRANSCRIPTION_MAX_BYTES', 16 * 1024 * 1024), // 16 MB

            'gemini' => [
                // Reutiliza la key de Gemini (que hoy vive en OPENAI_API_KEY).
                'key' => env('GEMINI_MEDIA_API_KEY', env('OPENAI_API_KEY')),
                'model' => env('GEMINI_MEDIA_MODEL', 'gemini-2.0-flash'),
            ],
            'openai_whisper' => [
                'key' => env('WHISPER_API_KEY'),
                'model' => env('WHISPER_MODEL', 'whisper-1'),
                'url' => env('WHISPER_API_URL', 'https://api.openai.com/v1/audio/transcriptions'),
            ],
        ],

        'vision' => [
            'driver' => env('AI_VISION_DRIVER', 'gemini'), // gemini | null
            'enabled' => filter_var(env('AI_VISION_ENABLED', true), FILTER_VALIDATE_BOOL),
            'max_bytes' => (int) env('AI_VISION_MAX_BYTES', 8 * 1024 * 1024), // 8 MB

            'gemini' => [
                'key' => env('GEMINI_MEDIA_API_KEY', env('OPENAI_API_KEY')),
                'model' => env('GEMINI_MEDIA_MODEL', 'gemini-2.0-flash'),
            ],
        ],
    ],
];
