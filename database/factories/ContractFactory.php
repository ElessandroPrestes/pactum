<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ContractStatus;
use App\Models\Client;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'data_inicio' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'data_fim' => null,
            'status' => ContractStatus::Ativo,
            'version' => 1,
        ];
    }

    public function cancelado(): static
    {
        return $this->state(fn (): array => ['status' => ContractStatus::Cancelado]);
    }
}
