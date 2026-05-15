<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ContractSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Client::ativos()->take(5)->get();
        $servicos = Service::ativos()->take(5)->get();

        if ($clientes->isEmpty() || $servicos->isEmpty()) {
            return;
        }

        $clientes->each(function (Client $cliente) use ($servicos): void {
            $contract = Contract::factory()->create([
                'client_id' => $cliente->id,
            ]);

            $servicos->random(min(3, $servicos->count()))->each(
                fn (Service $servico) => ContractItem::factory()->create([
                    'contract_id' => $contract->id,
                    'service_id' => $servico->id,
                    'valor_unitario' => $servico->valor_base_mensal,
                ])
            );
        });
    }
}
