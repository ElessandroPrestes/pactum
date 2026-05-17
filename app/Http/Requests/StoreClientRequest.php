<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Rules\DocumentoValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreClientRequest',
    title: 'Payload para criar Cliente',
    required: ['nome', 'documento', 'tipo_documento', 'email'],
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Acme Ltda'),
        new OA\Property(property: 'documento', type: 'string', description: 'CPF (11) ou CNPJ (14) — mascaras sao removidas automaticamente.', example: '12.345.678/0001-99'),
        new OA\Property(property: 'tipo_documento', type: 'string', enum: ['cpf', 'cnpj'], example: 'cnpj'),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'contato@acme.com'),
    ],
)]
class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $documento = $this->input('documento');

        if (is_string($documento)) {
            $this->merge([
                'documento' => preg_replace('/\D/', '', $documento) ?? '',
            ]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'documento' => ['required', 'string', new DocumentoValido, Rule::unique('clients', 'documento')],
            'tipo_documento' => ['required', Rule::enum(DocumentType::class)],
            'email' => ['required', 'email', 'max:255', Rule::unique('clients', 'email')],
        ];
    }
}
