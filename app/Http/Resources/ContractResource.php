<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'data_inicio' => $this->data_inicio->toDateString(),
            'data_fim' => $this->data_fim?->toDateString(),
            'status' => $this->status->value,
            'version' => $this->version,
            'client' => ClientResource::make($this->whenLoaded('client')),
            'itens' => ContractItemResource::collection($this->whenLoaded('items')),
            'total_calculado' => null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
