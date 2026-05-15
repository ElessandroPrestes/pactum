<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ContractItem;
use Illuminate\Support\Facades\Cache;

class ContractItemObserver
{
    public function created(ContractItem $item): void
    {
        $this->invalidarCacheDoContrato($item);
    }

    public function updated(ContractItem $item): void
    {
        $this->invalidarCacheDoContrato($item);
    }

    public function deleted(ContractItem $item): void
    {
        $this->invalidarCacheDoContrato($item);
    }

    private function invalidarCacheDoContrato(ContractItem $item): void
    {
        Cache::forget("contract:{$item->contract_id}:total");
    }
}
