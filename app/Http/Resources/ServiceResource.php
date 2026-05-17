<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Service
 */
#[OA\Schema(
    schema: 'Service',
    title: 'Servico',
    required: ['id', 'nome', 'valor_base_mensal', 'ativo'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'nome', type: 'string', example: 'Hospedagem Cloud'),
        new OA\Property(property: 'valor_base_mensal', type: 'string', description: 'Decimal 12,2 como string para preservar precisao.', example: '199.90'),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'valor_base_mensal' => $this->valor_base_mensal,
            'ativo' => $this->ativo,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
