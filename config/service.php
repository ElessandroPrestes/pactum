<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TTL do cache de listagem de servicos
    |--------------------------------------------------------------------------
    |
    | Tempo de vida (em segundos) do cache das listagens de servico. Serve
    | apenas como fallback de eventual consistency — o flush por tag feito
    | pelo ServiceObserver e o mecanismo principal de correcao.
    |
    */

    'cache_ttl' => env('SERVICE_CACHE_TTL', 600),

];
