<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ClientRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Client>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator;

    public function find(int $id): ?Client;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Client;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Client $client, array $dados): Client;

    public function delete(Client $client): void;
}
