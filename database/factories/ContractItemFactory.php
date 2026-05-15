<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractItem>
 */
class ContractItemFactory extends Factory
{
    protected $model = ContractItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'service_id' => Service::factory(),
            'quantidade' => fake()->numberBetween(1, 10),
            'valor_unitario' => fake()->randomFloat(2, 50, 1000),
        ];
    }
}
