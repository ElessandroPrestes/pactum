<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contract;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Contract
 */
#[OA\Schema(
    schema: 'Contract',
    title: 'Contrato',
    required: ['id', 'client_id', 'data_inicio', 'status', 'version'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'client_id', type: 'integer', example: 1),
        new OA\Property(property: 'data_inicio', type: 'string', format: 'date', example: '2026-01-01'),
        new OA\Property(property: 'data_fim', type: 'string', format: 'date', nullable: true, example: '2027-01-01'),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'cancelado'], example: 'ativo'),
        new OA\Property(property: 'version', type: 'integer', description: 'Optimistic lock. Envie a versao atual em PUT/DELETE/cancel.', example: 3),
        new OA\Property(property: 'client', ref: '#/components/schemas/Client', nullable: true),
        new OA\Property(
            property: 'itens',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ContractItem'),
            nullable: true,
        ),
        new OA\Property(property: 'total_calculado', type: 'string', description: 'Total mensal apos descontos (BCMath). Presente quando os itens estao carregados.', example: '479.50', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
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
            'total_calculado' => $this->when(
                $this->relationLoaded('items'),
                fn (): string => app(ContractService::class)->calculateTotal($this->resource),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
