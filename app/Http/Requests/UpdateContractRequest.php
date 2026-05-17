<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateContractRequest',
    title: 'Payload para atualizar Contrato',
    required: ['version'],
    properties: [
        new OA\Property(property: 'version', type: 'integer', minimum: 1, description: 'Versao atual do contrato (optimistic lock).', example: 3),
        new OA\Property(property: 'data_inicio', type: 'string', format: 'date'),
        new OA\Property(property: 'data_fim', type: 'string', format: 'date', nullable: true),
    ],
)]
class UpdateContractRequest extends FormRequest
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
            'version' => ['required', 'integer', 'min:1'],
            'data_inicio' => ['sometimes', 'required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
        ];
    }
}
