<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreContractItemRequest',
    title: 'Payload para adicionar Item ao Contrato',
    required: ['service_id', 'quantidade', 'valor_unitario'],
    properties: [
        new OA\Property(property: 'service_id', type: 'integer', example: 10),
        new OA\Property(property: 'quantidade', type: 'integer', minimum: 1, example: 2),
        new OA\Property(property: 'valor_unitario', type: 'number', format: 'float', minimum: 0, maximum: 9999999999.99, example: 180.00),
    ],
)]
class StoreContractItemRequest extends FormRequest
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
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'quantidade' => ['required', 'integer', 'min:1'],
            'valor_unitario' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
        ];
    }
}
