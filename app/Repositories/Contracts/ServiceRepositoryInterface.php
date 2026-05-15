<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServiceRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator;

    public function find(int $id): ?Service;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Service;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Service $service, array $dados): Service;

    public function delete(Service $service): void;
}
