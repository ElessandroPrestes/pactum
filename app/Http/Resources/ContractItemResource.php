<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContractItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin ContractItem
 */
#[OA\Schema(
    schema: 'ContractItem',
    title: 'Item do Contrato',
    required: ['id', 'contract_id', 'service_id', 'quantidade', 'valor_unitario'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 100),
        new OA\Property(property: 'contract_id', type: 'integer', example: 7),
        new OA\Property(property: 'service_id', type: 'integer', example: 10),
        new OA\Property(property: 'quantidade', type: 'integer', minimum: 1, example: 2),
        new OA\Property(property: 'valor_unitario', type: 'string', description: 'Decimal 12,2 como string (pode diferir do valor_base do servico).', example: '180.00'),
        new OA\Property(property: 'service', ref: '#/components/schemas/Service', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
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
