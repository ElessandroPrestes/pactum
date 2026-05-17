<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreServiceRequest',
    title: 'Payload para criar Servico',
    required: ['nome', 'valor_base_mensal'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Hospedagem Cloud'),
        new OA\Property(property: 'valor_base_mensal', type: 'number', format: 'float', minimum: 0, maximum: 9999999999.99, example: 199.90),
        new OA\Property(property: 'ativo', type: 'boolean', example: true),
    ],
)]
class StoreServiceRequest extends FormRequest
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
            'nome' => ['required', 'string', 'max:255'],
            'valor_base_mensal' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
