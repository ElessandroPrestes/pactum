<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RegistrarHistoricoContrato;
use App\Models\Contract;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContractObserver
{
    public function created(Contract $contract): void
    {
        $this->registrarHistorico($contract, 'created', $contract->toArray());
    }

    public function updated(Contract $contract): void
    {
        $this->invalidarCache($contract);

        $this->registrarHistorico($contract, 'updated', [
            'changes' => $contract->getChanges(),
            'original' => $contract->getOriginal(),
        ]);
    }

    public function deleted(Contract $contract): void
    {
        $this->invalidarCache($contract);

        $this->registrarHistorico($contract, 'deleted', $contract->toArray());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function registrarHistorico(Contract $contract, string $evento, array $payload): void
    {
        RegistrarHistoricoContrato::dispatch(
            $contract->id,
            $evento,
            $payload,
            (string) Str::uuid(),
        );
    }

    private function invalidarCache(Contract $contract): void
    {
        Cache::forget("contract:{$contract->id}:total");
    }
}
