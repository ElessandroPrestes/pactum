<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContractItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ContractItem
 */
class ContractItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contract_id,
            'service_id' => $this->service_id,
            'quantidade' => $this->quantidade,
            'valor_unitario' => $this->valor_unitario,
            'service' => ServiceResource::make($this->whenLoaded('service')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
