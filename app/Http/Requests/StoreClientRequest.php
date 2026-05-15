<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DocumentType;
use App\Rules\DocumentoValido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
