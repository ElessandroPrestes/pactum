<?php

declare(strict_types=1);

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

describe('ServiceApi', function () {
    it('lista servicos paginados', function () {
        Service::factory()->count(3)->create();

        $resposta = $this->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertJsonPaginado($resposta, ['id', 'nome', 'valor_base_mensal', 'ativo']);
    });

    it('filtra servicos por ativo', function () {
        Service::factory()->create();
        Service::factory()->inativo()->create();

        $this->getJson('/api/v1/services?ativo=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.ativo', false);
    });

    it('cria um servico', function () {
        $this->postJson('/api/v1/services', [
            'nome' => 'Hospedagem Cloud',
            'valor_base_mensal' => 199.90,
        ])
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Hospedagem Cloud')
            ->assertJsonPath('data.valor_base_mensal', '199.90')
            ->assertJsonPath('data.ativo', true);

        $this->assertDatabaseHas('services', ['nome' => 'Hospedagem Cloud']);
    });

    it('rejeita criacao sem os campos obrigatorios', function () {
        $this->postJson('/api/v1/services', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'valor_base_mensal']);
    });

    it('rejeita valor base mensal negativo', function () {
        $this->postJson('/api/v1/services', [
            'nome' => 'Servico Invalido',
            'valor_base_mensal' => -10,
        ])->assertStatus(422)->assertJsonValidationErrors('valor_base_mensal');
    });

    it('exibe um servico especifico', function () {
        $service = Service::factory()->create();

        $this->getJson("/api/v1/services/{$service->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $service->id);
    });

    it('retorna 404 para servico inexistente', function () {
        $this->assertJsonDeErro(
            $this->getJson('/api/v1/services/999999')->assertNotFound()
        );
    });

    it('atualiza um servico', function () {
        $service = Service::factory()->create();

        $this->putJson("/api/v1/services/{$service->id}", ['valor_base_mensal' => 350])
            ->assertOk()
            ->assertJsonPath('data.valor_base_mensal', '350.00');

        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'valor_base_mensal' => '350.00',
        ]);
    });

    it('remove um servico com soft delete', function () {
        $service = Service::factory()->create();

        $this->deleteJson("/api/v1/services/{$service->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('services', ['id' => $service->id]);
    });

    it('usa cache na listagem e invalida ao mutar um servico', function () {
        Service::factory()->count(2)->create();

        $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(2, 'data');

        // Insercao direta nao dispara o observer: o cache permanece valido.
        DB::table('services')->insert([
            'nome' => 'Inserido Sem Evento',
            'valor_base_mensal' => 100.00,
            'ativo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(2, 'data');

        // Criar via model dispara o ServiceObserver e faz o flush da tag.
        Service::factory()->create();

        $this->getJson('/api/v1/services')->assertOk()->assertJsonCount(4, 'data');
    });
});
