<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EloquentServiceRepository implements ServiceRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        return Service::query()
            ->when(
                array_key_exists('ativo', $filtros),
                fn (Builder $query) => $query->where('ativo', $filtros['ativo'])
            )
            ->when(
                isset($filtros['nome']),
                fn (Builder $query) => $query->where('nome', 'like', '%'.((string) $filtros['nome']).'%')
            )
            ->latest()
            ->paginate($porPagina);
    }

    public function find(int $id): ?Service
    {
        return Service::find($id);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Service
    {
        return Service::create($dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Service $service, array $dados): Service
    {
        $service->update($dados);

        return $service;
    }

    public function delete(Service $service): void
    {
        $service->delete();
    }
}
