<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TTL do cache de cliente
    |--------------------------------------------------------------------------
    |
    | Tempo de vida (em segundos) do cache do cliente individual. Serve apenas
    | como fallback de eventual consistency — a invalidacao explicita pelo
    | ClientObserver e o mecanismo principal de correcao.
    |
    */

    'cache_ttl' => env('CLIENT_CACHE_TTL', 1800),

];
