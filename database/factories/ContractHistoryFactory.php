<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractHistory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContractHistory>
 */
class ContractHistoryFactory extends Factory
{
    protected $model = ContractHistory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'evento' => fake()->randomElement(['created', 'updated', 'deleted']),
            'payload' => ['origem' => 'factory'],
            'usuario_id' => null,
            'unique_id' => (string) Str::uuid(),
        ];
    }
}
