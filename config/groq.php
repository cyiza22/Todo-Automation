<?php

return [
    'api_key' => env('GROQ_API_KEY'),
    'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    'model' => env('GROQ_MODEL', 'qwen/qwen3.8-27b'),
    'request_timeout' => env('GROQ_REQUEST_TIMEOUT', 30),
];
