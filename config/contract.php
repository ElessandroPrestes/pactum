<?php

return [

    /*
    |--------------------------------------------------------------------------
    | TTL do cache do total do contrato
    |--------------------------------------------------------------------------
    |
    | Tempo de vida (em segundos) do cache do total calculado. Serve apenas
    | como fallback de eventual consistency — a invalidacao explicita pelos
    | observers e o mecanismo principal de correcao.
    |
    */

    'cache_ttl' => env('CONTRACT_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Regras de desconto
    |--------------------------------------------------------------------------
    |
    | Regras aplicadas pelo DiscountCalculator em ordem. Adicionar nova regra
    | exige incluir uma entrada aqui e um metodo correspondente no calculator.
    |
    */

    'descontos' => [

        'quantidade_progressivo' => [
            'ativo' => true,
            'faixas' => [
                ['min_itens' => 5, 'percentual' => 5.0],
                ['min_itens' => 10, 'percentual' => 10.0],
            ],
        ],

        'fidelidade' => [
            'ativo' => true,
            'meses_minimos' => 12,
            'percentual' => 7.0,
        ],

    ],

];
