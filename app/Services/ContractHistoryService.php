<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContractHistoryService
{
    /**
     * @return LengthAwarePaginator<int, ContractHistory>
     */
    public function paginate(Contract $contract, int $porPagina = 15): LengthAwarePaginator
    {
        return ContractHistory::query()
            ->where('contract_id', $contract->id)
            ->latest('created_at')
            ->paginate($porPagina);
    }
}
