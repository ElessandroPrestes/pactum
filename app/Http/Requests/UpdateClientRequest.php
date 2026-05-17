<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClientStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use App\Rules\DocumentoValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateClientRequest',
    title: 'Payload para atualizar Cliente',
    description: 'Todos os campos sao opcionais; envie apenas o que deseja alterar.',
    properties: [
        new OA\Property(property: 'nome', type: 'string', maxLength: 255, example: 'Acme S/A'),
        new OA\Property(property: 'documento', type: 'string', example: '12345678000199'),
        new OA\Property(property: 'tipo_documento', type: 'string', enum: ['cpf', 'cnpj']),
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255),
        new OA\Property(property: 'status', type: 'string', enum: ['ativo', 'inativo']),
    ],
)]
class UpdateClientRequest extends FormRequest
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
        /** @var Client $client */
        $client = $this->route('client');

        return [
            'nome' => ['sometimes', 'required', 'string', 'max:255'],
            'documento' => ['sometimes', 'required', 'string', new DocumentoValido, Rule::unique('clients', 'documento')->ignore($client)],
            'tipo_documento' => ['sometimes', 'required', Rule::enum(DocumentType::class)],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($client)],
            'status' => ['sometimes', 'required', Rule::enum(ClientStatus::class)],
        ];
    }
}
