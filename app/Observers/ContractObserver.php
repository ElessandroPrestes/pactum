<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Contract;
use Illuminate\Support\Facades\Cache;

class ContractObserver
{
    public function updated(Contract $contract): void
    {
        $this->invalidarCache($contract);
    }

    public function deleted(Contract $contract): void
    {
        $this->invalidarCache($contract);
    }

    private function invalidarCache(Contract $contract): void
    {
        Cache::forget("contract:{$contract->id}:total");
    }
}
