<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Em dev SPA e API rodam na mesma origem (nginx em APP_URL), entao CORS
    | nao e exercitado no fluxo padrao. A configuracao ainda existe para
    | cenarios em que o frontend seja servido fora (ex: rodar Vite direto
    | em outra porta) ou para producao em dominio separado — basta apontar
    | FRONTEND_URL e, se necessario, FRONTEND_URL_ALT.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:8000'),
        env('FRONTEND_URL_ALT'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-Trace-Id',
        'Idempotency-Key',
    ],

    'exposed_headers' => ['X-Trace-Id'],

    'max_age' => 3600,

    'supports_credentials' => false,

];
