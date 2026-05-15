<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Restritivo por padrao: apenas o frontend Vite (dev) e dominios listados
    | em FRONTEND_URL podem acessar a API. Producao deve apontar para o dominio
    | real via env. Idempotency-Key e Authorization estao explicitos em
    | allowed_headers para passar no preflight.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
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
