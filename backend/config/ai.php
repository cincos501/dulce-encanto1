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
    ],
];
