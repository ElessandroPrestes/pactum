<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CancelContractRequest',
    title: 'Payload para cancelar Contrato',
    required: ['version'],
    properties: [
        new OA\Property(property: 'version', type: 'integer', minimum: 1, description: 'Versao atual do contrato.', example: 3),
    ],
)]
class CancelContractRequest extends FormRequest
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
        ];
    }
}
