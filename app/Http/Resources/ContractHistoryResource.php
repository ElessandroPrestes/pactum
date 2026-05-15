<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContractHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContractHistory
 */
class ContractHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'evento' => $this->evento,
            'payload' => $this->payload,
            'usuario_id' => $this->usuario_id,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
