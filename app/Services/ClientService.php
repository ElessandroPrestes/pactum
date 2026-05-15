<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ClientService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Client>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filtros, $porPagina);
    }

    public function find(int $id): ?Client
    {
        /** @var Client|null $client */
        $client = Cache::remember(
            "client:{$id}",
            (int) config('client.cache_ttl'),
            fn () => $this->repository->find($id),
        );

        return $client;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Client
    {
        $dados['status'] = ClientStatus::Ativo->value;

        return $this->repository->create($dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Client $client, array $dados): Client
    {
        return $this->repository->update($client, $dados);
    }

    public function delete(Client $client): void
    {
        $this->repository->delete($client);
    }
}
