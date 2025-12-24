<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your OpenAI API settings including API key,
    | model, timeout and other options for interacting with OpenAI services.
    |
    */

    'api_key' => env('OPENAI_API_KEY'),

    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),

    'timeout' => env('OPENAI_TIMEOUT', 30),

];
