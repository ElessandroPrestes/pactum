<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreContractItemPayload',
    title: 'Item inicial do contrato (inline)',
    required: ['service_id', 'quantidade', 'valor_unitario'],
    properties: [
        new OA\Property(property: 'service_id', type: 'integer', example: 10),
        new OA\Property(property: 'quantidade', type: 'integer', minimum: 1, example: 2),
        new OA\Property(property: 'valor_unitario', type: 'number', format: 'float', minimum: 0, maximum: 9999999999.99, example: 180.00),
    ],
)]
#[OA\Schema(
    schema: 'StoreContractRequest',
    title: 'Payload para criar Contrato',
    required: ['client_id', 'data_inicio'],
    properties: [
        new OA\Property(property: 'client_id', type: 'integer', example: 1),
        new OA\Property(property: 'data_inicio', type: 'string', format: 'date', example: '2026-01-01'),
        new OA\Property(property: 'data_fim', type: 'string', format: 'date', nullable: true, example: '2027-01-01'),
        new OA\Property(
            property: 'itens',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/StoreContractItemPayload'),
        ),
    ],
)]
class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'itens' => ['sometimes', 'array'],
            'itens.*.service_id' => ['required_with:itens', 'integer', 'exists:services,id'],
            'itens.*.quantidade' => ['required_with:itens', 'integer', 'min:1'],
            'itens.*.valor_unitario' => ['required_with:itens', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
