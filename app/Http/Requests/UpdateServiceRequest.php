<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateServiceRequest',
    title: 'Payload para atualizar Servico',
    description: 'Campos opcionais; envie apenas o que deseja alterar.',
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255),
        new OA\Property(property: 'valor_base_mensal', type: 'number', format: 'float', minimum: 0, maximum: 9999999999.99),
        new OA\Property(property: 'ativo', type: 'boolean'),
    ],
)]
class UpdateServiceRequest extends FormRequest
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
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'valor_base_mensal' => ['sometimes', 'required', 'numeric', 'min:0', 'max:9999999999.99'],
            'ativo' => ['sometimes', 'boolean'],
        ];
    }
}
