<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin Client
 */
#[OA\Schema(
    schema: 'Client',
    title: 'Cliente',
    required: ['id', 'nome', 'documento', 'tipo_documento', 'email', 'status'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nome', type: 'string', example: 'Acme Ltda'),
        new OA\Property(property: 'documento', type: 'string', description: 'Apenas digitos (CPF 11 ou CNPJ 14).', example: '12345678000199'),
        new OA\Property(property: 'tipo_documento', type: 'string', enum: ['cpf', 'cnpj'], example: 'cnpj'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'contato@acme.com'),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo'], example: 'ativo'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    ],
)]
class ClientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'documento' => $this->documento,
            'tipo_documento' => $this->tipo_documento->value,
            'email' => $this->email,
            'status' => $this->status->value,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
