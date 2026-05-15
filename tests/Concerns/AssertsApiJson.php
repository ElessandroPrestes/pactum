<?php

declare(strict_types=1);

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait AssertsApiJson
{
    /**
     * Verifica o envelope padrao de uma resposta paginada da API.
     *
     * @param  list<string>  $camposItem
     */
    protected function assertJsonPaginado(TestResponse $resposta, array $camposItem): TestResponse
    {
        return $resposta->assertJsonStructure([
            'data' => [$camposItem],
            'links',
            'meta',
        ]);
    }

    /**
     * Verifica o envelope padrao de uma resposta de erro da API.
     */
    protected function assertJsonDeErro(TestResponse $resposta): TestResponse
    {
        return $resposta->assertJsonStructure(['message', 'trace_id']);
    }
}
