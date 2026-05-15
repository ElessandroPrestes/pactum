<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('ContractApi', function () {
    it('lista contratos paginados', function () {
        Contract::factory()->count(3)->create();

        $resposta = $this->getJson('/api/v1/contracts')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertJsonPaginado($resposta, ['id', 'client_id', 'data_inicio', 'status', 'version']);
    });

    it('filtra contratos por status', function () {
        Contract::factory()->create();
        Contract::factory()->cancelado()->create();

        $this->getJson('/api/v1/contracts?status=cancelado')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'cancelado');
    });

    it('filtra contratos por cliente', function () {
        $cliente = Client::factory()->create();
        Contract::factory()->create(['client_id' => $cliente->id]);
        Contract::factory()->count(2)->create();

        $this->getJson("/api/v1/contracts?client_id={$cliente->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.client_id', $cliente->id);
    });

    it('cria contrato com itens transacionalmente', function () {
        $cliente = Client::factory()->create();
        $servico = Service::factory()->create();

        $resposta = $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
            'itens' => [
                ['service_id' => $servico->id, 'quantidade' => 2, 'valor_unitario' => 150.00],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.client_id', $cliente->id)
            ->assertJsonPath('data.status', 'ativo')
            ->assertJsonPath('data.version', 1)
            ->assertJsonCount(1, 'data.itens');

        $this->assertDatabaseHas('contracts', [
            'id' => $resposta->json('data.id'),
            'client_id' => $cliente->id,
            'status' => 'ativo',
        ]);
        $this->assertDatabaseHas('contract_items', [
            'contract_id' => $resposta->json('data.id'),
            'service_id' => $servico->id,
            'quantidade' => 2,
        ]);
    });

    it('rejeita criacao de contrato para cliente inativo', function () {
        $cliente = Client::factory()->inativo()->create();

        $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ])->assertStatus(422);
    });

    it('rejeita data_fim anterior a data_inicio', function () {
        $cliente = Client::factory()->create();

        $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-02-01',
            'data_fim' => '2026-01-01',
        ])->assertStatus(422)->assertJsonValidationErrors('data_fim');
    });

    it('exibe contrato com itens', function () {
        $contract = Contract::factory()->create();
        ContractItem::factory()->count(2)->create(['contract_id' => $contract->id]);

        $this->getJson("/api/v1/contracts/{$contract->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $contract->id)
            ->assertJsonCount(2, 'data.itens');
    });

    it('atualiza contrato com version correta', function () {
        $contract = Contract::factory()->create(['version' => 1]);

        $this->putJson("/api/v1/contracts/{$contract->id}", [
            'version' => 1,
            'data_fim' => '2026-12-31',
        ])->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.data_fim', '2026-12-31');

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'version' => 2,
            'data_fim' => '2026-12-31',
        ]);
    });

    it('retorna 409 em conflito de versao no update', function () {
        $contract = Contract::factory()->create(['version' => 5]);

        $this->putJson("/api/v1/contracts/{$contract->id}", [
            'version' => 1,
            'data_fim' => '2026-12-31',
        ])->assertStatus(409);

        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'version' => 5,
        ]);
    });

    it('cancela contrato e bloqueia mutacoes seguintes', function () {
        $contract = Contract::factory()->create(['version' => 1]);
        $servico = Service::factory()->create();

        $this->postJson("/api/v1/contracts/{$contract->id}/cancel", ['version' => 1])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelado')
            ->assertJsonPath('data.version', 2);

        $this->postJson("/api/v1/contracts/{$contract->id}/items", [
            'service_id' => $servico->id,
            'quantidade' => 1,
            'valor_unitario' => 100,
        ])->assertStatus(422);
    });

    it('retorna 409 ao cancelar com version desatualizada', function () {
        $contract = Contract::factory()->create(['version' => 3]);

        $this->postJson("/api/v1/contracts/{$contract->id}/cancel", ['version' => 1])
            ->assertStatus(409);
    });

    it('adiciona item a contrato ativo', function () {
        $contract = Contract::factory()->create();
        $servico = Service::factory()->create();

        $this->postJson("/api/v1/contracts/{$contract->id}/items", [
            'service_id' => $servico->id,
            'quantidade' => 3,
            'valor_unitario' => 99.90,
        ])->assertCreated()
            ->assertJsonPath('data.service_id', $servico->id)
            ->assertJsonPath('data.quantidade', 3);

        $this->assertDatabaseHas('contract_items', [
            'contract_id' => $contract->id,
            'service_id' => $servico->id,
            'quantidade' => 3,
        ]);
    });

    it('rejeita quantidade zero ao adicionar item', function () {
        $contract = Contract::factory()->create();
        $servico = Service::factory()->create();

        $this->postJson("/api/v1/contracts/{$contract->id}/items", [
            'service_id' => $servico->id,
            'quantidade' => 0,
            'valor_unitario' => 50,
        ])->assertStatus(422)->assertJsonValidationErrors('quantidade');
    });

    it('remove item do contrato', function () {
        $contract = Contract::factory()->create();
        $item = ContractItem::factory()->create(['contract_id' => $contract->id]);

        $this->deleteJson("/api/v1/contracts/{$contract->id}/items/{$item->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('contract_items', ['id' => $item->id]);
    });

    it('rejeita remocao de item que nao pertence ao contrato', function () {
        $contract = Contract::factory()->create();
        $outro = Contract::factory()->create();
        $item = ContractItem::factory()->create(['contract_id' => $outro->id]);

        $this->deleteJson("/api/v1/contracts/{$contract->id}/items/{$item->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('contract_items', ['id' => $item->id]);
    });

    it('remove contrato com soft delete', function () {
        $contract = Contract::factory()->create();

        $this->deleteJson("/api/v1/contracts/{$contract->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('contracts', ['id' => $contract->id]);
    });

    it('cascateia delete de itens ao apagar contrato em hard delete', function () {
        $contract = Contract::factory()->create();
        $item = ContractItem::factory()->create(['contract_id' => $contract->id]);

        DB::table('contracts')->where('id', $contract->id)->delete();

        $this->assertDatabaseMissing('contract_items', ['id' => $item->id]);
    });
});
