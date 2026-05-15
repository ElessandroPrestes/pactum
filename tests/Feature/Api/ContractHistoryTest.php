<?php

declare(strict_types=1);

use App\Jobs\RegistrarHistoricoContrato;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('ContractHistory', function () {
    it('registra historico ao criar contrato via observer', function () {
        $cliente = Client::factory()->create();

        $resposta = $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ])->assertCreated();

        $contractId = $resposta->json('data.id');

        $this->assertDatabaseHas('contract_histories', [
            'contract_id' => $contractId,
            'evento' => 'created',
        ]);
    });

    it('registra historico ao atualizar contrato via optimistic lock', function () {
        $contract = Contract::factory()->create(['version' => 1]);

        $this->putJson("/api/v1/contracts/{$contract->id}", [
            'version' => 1,
            'data_fim' => '2026-12-31',
        ])->assertOk();

        $historico = ContractHistory::query()
            ->where('contract_id', $contract->id)
            ->where('evento', 'updated')
            ->first();

        expect($historico)->not->toBeNull()
            ->and($historico->payload)->toHaveKeys(['changes', 'version_anterior', 'version_nova'])
            ->and($historico->payload['version_anterior'])->toBe(1)
            ->and($historico->payload['version_nova'])->toBe(2);
    });

    it('registra historico ao cancelar contrato', function () {
        $contract = Contract::factory()->create(['version' => 1]);

        $this->postJson("/api/v1/contracts/{$contract->id}/cancel", ['version' => 1])
            ->assertOk();

        $this->assertDatabaseHas('contract_histories', [
            'contract_id' => $contract->id,
            'evento' => 'updated',
        ]);
    });

    it('registra historico ao deletar contrato', function () {
        $contract = Contract::factory()->create();

        $this->deleteJson("/api/v1/contracts/{$contract->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('contract_histories', [
            'contract_id' => $contract->id,
            'evento' => 'deleted',
        ]);
    });

    it('e idempotente quando o job e executado duas vezes com mesmo unique id', function () {
        $contract = Contract::factory()->create();
        $uniqueId = (string) Str::uuid();

        (new RegistrarHistoricoContrato($contract->id, 'updated', ['x' => 1], $uniqueId))->handle();
        (new RegistrarHistoricoContrato($contract->id, 'updated', ['x' => 1], $uniqueId))->handle();

        expect(ContractHistory::query()->where('unique_id', $uniqueId)->count())->toBe(1);
    });

    it('lista historico paginado do contrato', function () {
        $contract = Contract::withoutEvents(fn () => Contract::factory()->create());
        ContractHistory::factory()->count(3)->create(['contract_id' => $contract->id]);

        $resposta = $this->getJson("/api/v1/contracts/{$contract->id}/history")
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertJsonPaginado($resposta, ['id', 'contract_id', 'evento', 'payload', 'created_at']);
    });

    it('lista historico apenas do contrato pedido', function () {
        [$contract, $outro] = Contract::withoutEvents(fn () => [
            Contract::factory()->create(),
            Contract::factory()->create(),
        ]);

        ContractHistory::factory()->count(2)->create(['contract_id' => $contract->id]);
        ContractHistory::factory()->count(4)->create(['contract_id' => $outro->id]);

        $this->getJson("/api/v1/contracts/{$contract->id}/history")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('lista historico em ordem decrescente de data', function () {
        $contract = Contract::withoutEvents(fn () => Contract::factory()->create());
        $antigo = ContractHistory::factory()->create([
            'contract_id' => $contract->id,
            'evento' => 'created',
            'created_at' => now()->subDay(),
        ]);
        $recente = ContractHistory::factory()->create([
            'contract_id' => $contract->id,
            'evento' => 'updated',
            'created_at' => now(),
        ]);

        $this->getJson("/api/v1/contracts/{$contract->id}/history")
            ->assertOk()
            ->assertJsonPath('data.0.id', $recente->id)
            ->assertJsonPath('data.1.id', $antigo->id);
    });

    it('retorna 404 para contrato inexistente no historico', function () {
        $this->getJson('/api/v1/contracts/999999/history')->assertNotFound();
    });
});
