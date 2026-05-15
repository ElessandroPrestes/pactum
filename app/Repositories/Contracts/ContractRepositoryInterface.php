<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Contract;
use App\Models\ContractItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ContractRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Contract>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator;

    public function find(int $id): ?Contract;

    public function findWithItems(int $id): ?Contract;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Contract;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function updateWithVersion(Contract $contract, array $dados, int $expectedVersion): Contract;

    public function delete(Contract $contract): void;

    /**
     * @param  array<string, mixed>  $dados
     */
    public function addItem(Contract $contract, array $dados): ContractItem;

    public function removeItem(ContractItem $item): void;
}
