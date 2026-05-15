<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContractHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegistrarHistoricoContrato implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly int $contractId,
        public readonly string $evento,
        public readonly array $payload,
        public readonly string $uniqueId,
        public readonly ?int $usuarioId = null,
    ) {}

    public function handle(): void
    {
        if (ContractHistory::query()->where('unique_id', $this->uniqueId)->exists()) {
            return;
        }

        ContractHistory::create([
            'contract_id' => $this->contractId,
            'evento' => $this->evento,
            'payload' => $this->payload,
            'usuario_id' => $this->usuarioId,
            'unique_id' => $this->uniqueId,
        ]);
    }
}
