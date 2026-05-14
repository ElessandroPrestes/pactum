<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Enums\DocumentType;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(DocumentType::cases());

        return [
            'nome' => fake()->name(),
            'documento' => $tipo === DocumentType::Cpf
                ? fake()->cpf(false)
                : fake()->cnpj(false),
            'tipo_documento' => $tipo,
            'email' => fake()->unique()->safeEmail(),
            'status' => ClientStatus::Ativo,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (): array => ['status' => ClientStatus::Inativo]);
    }
}
