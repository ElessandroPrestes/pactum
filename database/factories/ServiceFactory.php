<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->words(3, true),
            'valor_base_mensal' => fake()->randomFloat(2, 50, 5000),
            'ativo' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (): array => ['ativo' => false]);
    }
}
