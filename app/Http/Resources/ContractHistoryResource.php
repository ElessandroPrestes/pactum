<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContractHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin ContractHistory
 */
#[OA\Schema(
    schema: 'ContractHistory',
    title: 'Historico do Contrato',
    required: ['id', 'contract_id', 'evento', 'payload', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 42),
        new OA\Property(property: 'contract_id', type: 'integer', example: 7),
        new OA\Property(property: 'evento', type: 'string', enum: ['created', 'updated', 'deleted'], example: 'updated'),
        new OA\Property(property: 'payload', type: 'object', description: 'Snapshot JSON do contrato no momento do evento.'),
        new OA\Property(property: 'usuario_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
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
