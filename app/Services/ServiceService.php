<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class ServiceService
{
    private const CACHE_TAG = 'services';

    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        $chave = 'services:list:'.md5(serialize($filtros).':'.$porPagina);

        /** @var LengthAwarePaginator<int, Service> $resultado */
        $resultado = Cache::tags([self::CACHE_TAG])->remember(
            $chave,
            (int) config('service.cache_ttl'),
            fn () => $this->repository->paginate($filtros, $porPagina),
        );

        return $resultado;
    }

    public function find(int $id): ?Service
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Service
    {
        $dados['ativo'] ??= true;

        return $this->repository->create($dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Service $service, array $dados): Service
    {
        return $this->repository->update($service, $dados);
    }

    public function delete(Service $service): void
    {
        $this->repository->delete($service);
    }
}
