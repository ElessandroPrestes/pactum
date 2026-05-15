<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Exceptions\ConcurrencyConflictException;
use App\Jobs\RegistrarHistoricoContrato;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EloquentContractRepository implements ContractRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Contract>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        return Contract::query()
            ->with(['client', 'items.service'])
            ->when(
                isset($filtros['client_id']),
                fn (Builder $query) => $query->where('client_id', (int) $filtros['client_id'])
            )
            ->when(
                isset($filtros['status']),
                fn (Builder $query) => $query->where('status', $filtros['status'])
            )
            ->when(
                isset($filtros['data_inicio']),
                fn (Builder $query) => $query->whereDate('data_inicio', '>=', $filtros['data_inicio'])
            )
            ->latest()
            ->paginate($porPagina);
    }

    public function find(int $id): ?Contract
    {
        return Contract::find($id);
    }

    public function findWithItems(int $id): ?Contract
    {
        return Contract::with(['client', 'items.service'])->find($id);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function create(array $dados): Contract
    {
        return Contract::create($dados);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function updateWithVersion(Contract $contract, array $dados, int $expectedVersion): Contract
    {
        $dados['version'] = $expectedVersion + 1;
        $dados['updated_at'] = now();

        $afetadas = DB::table('contracts')
            ->where('id', $contract->id)
            ->where('version', $expectedVersion)
            ->whereNull('deleted_at')
            ->update($dados);

        if ($afetadas === 0) {
            throw new ConcurrencyConflictException;
        }

        Cache::forget("contract:{$contract->id}:total");

        RegistrarHistoricoContrato::dispatch(
            $contract->id,
            'updated',
            [
                'changes' => $dados,
                'version_anterior' => $expectedVersion,
                'version_nova' => $expectedVersion + 1,
            ],
            (string) Str::uuid(),
        );

        return $contract->refresh();
    }

    public function delete(Contract $contract): void
    {
        $contract->delete();
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function addItem(Contract $contract, array $dados): ContractItem
    {
        /** @var ContractItem $item */
        $item = $contract->items()->create($dados);

        return $item;
    }

    public function removeItem(ContractItem $item): void
    {
        $item->delete();
    }
}
