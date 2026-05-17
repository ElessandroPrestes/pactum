<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'LoginRequest',
    title: 'Payload de Login',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'admin@pactum.test'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
        new OA\Property(property: 'device_name', type: 'string', maxLength: 255, description: 'Identificador opcional do dispositivo/cliente.', example: 'spa-pactum'),
    ],
)]
#[OA\Schema(
    schema: 'LoginResponse',
    title: 'Resposta do Login',
    required: ['token', 'user'],
    properties: [
        new OA\Property(property: 'token', type: 'string', description: 'Personal Access Token (Sanctum).', example: '1|abcdef...'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
)]
class LoginRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
