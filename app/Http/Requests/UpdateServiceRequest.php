<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
