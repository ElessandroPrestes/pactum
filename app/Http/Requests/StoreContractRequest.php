<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
