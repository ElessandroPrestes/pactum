<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentClientRepository implements ClientRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Client>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        return Client::query()
            ->when(
                isset($filtros['status']),
                fn (Builder $query) => $query->where('status', $filtros['status'])
            )
            ->when(
                isset($filtros['documento']),
                fn (Builder $query) => $query->where('documento', $filtros['documento'])
            )
            ->when(
                isset($filtros['nome']),
                fn (Builder $query) => $query->where('nome', 'like', '%'.((string) $filtros['nome']).'%')
            )
            ->latest()
            ->paginate($porPagina);
    }

    public function find(int $id): ?Client
    {
        return Client::find($id);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Client
    {
        return Client::create($dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Client $client, array $dados): Client
    {
        $client->update($dados);

        return $client;
    }

    public function delete(Client $client): void
    {
        $client->delete();
    }
}
