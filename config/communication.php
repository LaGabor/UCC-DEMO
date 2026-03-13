<?php

use App\Enums\MessageResponseSetting;

return [
    'message_response_setting' => env(
        'MESSAGE_RESPONSE_SETTING',
        MessageResponseSetting::HYBRID->value
    ),
    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://host.docker.internal:11434/api/generate'),
        'model' => env('OLLAMA_MODEL', 'llama3.2'),
        'timeout_seconds' => (int) env('OLLAMA_TIMEOUT_SECONDS', 120),
        'fallback_message' => env(
            'OLLAMA_FALLBACK_MESSAGE',
            'The assistant is temporarily unavailable. Please try again later.'
        ),
    ],
];
