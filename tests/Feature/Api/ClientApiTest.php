<?php

declare(strict_types=1);

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('ClientApi', function () {
    it('lista clientes paginados', function () {
        Client::factory()->count(3)->create();

        $this->getJson('/api/v1/clients')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'nome', 'documento', 'tipo_documento', 'email', 'status']],
                'links',
                'meta',
            ]);
    });

    it('filtra clientes por status', function () {
        Client::factory()->create();
        Client::factory()->inativo()->create();

        $this->getJson('/api/v1/clients?status=inativo')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'inativo');
    });

    it('cria um cliente normalizando o documento', function () {
        $resposta = $this->postJson('/api/v1/clients', [
            'nome' => 'Acme Ltda',
            'documento' => '11.222.333/0001-81',
            'tipo_documento' => 'cnpj',
            'email' => 'contato@acme.com',
        ]);

        $resposta->assertCreated()
            ->assertJsonPath('data.nome', 'Acme Ltda')
            ->assertJsonPath('data.documento', '11222333000181')
            ->assertJsonPath('data.status', 'ativo');

        $this->assertDatabaseHas('clients', [
            'documento' => '11222333000181',
            'status' => 'ativo',
        ]);
    });

    it('forca status ativo ao criar mesmo quando informado inativo', function () {
        $this->postJson('/api/v1/clients', [
            'nome' => 'Cliente Novo',
            'documento' => '111.444.777-35',
            'tipo_documento' => 'cpf',
            'email' => 'novo@example.com',
            'status' => 'inativo',
        ])->assertCreated()->assertJsonPath('data.status', 'ativo');
    });

    it('rejeita criacao com documento invalido', function () {
        $this->postJson('/api/v1/clients', [
            'nome' => 'Cliente',
            'documento' => '12345678901',
            'tipo_documento' => 'cpf',
            'email' => 'cliente@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('documento');
    });

    it('rejeita criacao com documento duplicado', function () {
        $existente = Client::factory()->create();

        $this->postJson('/api/v1/clients', [
            'nome' => 'Outro Cliente',
            'documento' => $existente->documento,
            'tipo_documento' => $existente->tipo_documento->value,
            'email' => 'outro@example.com',
        ])->assertStatus(422)->assertJsonValidationErrors('documento');
    });

    it('rejeita criacao sem os campos obrigatorios', function () {
        $this->postJson('/api/v1/clients', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome', 'documento', 'tipo_documento', 'email']);
    });

    it('exibe um cliente especifico', function () {
        $client = Client::factory()->create();

        $this->getJson("/api/v1/clients/{$client->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $client->id);
    });

    it('retorna 404 para cliente inexistente', function () {
        $this->getJson('/api/v1/clients/999999')
            ->assertNotFound()
            ->assertJsonStructure(['message', 'trace_id']);
    });

    it('atualiza um cliente', function () {
        $client = Client::factory()->create();

        $this->putJson("/api/v1/clients/{$client->id}", ['nome' => 'Nome Atualizado'])
            ->assertOk()
            ->assertJsonPath('data.nome', 'Nome Atualizado');

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'nome' => 'Nome Atualizado',
        ]);
    });

    it('remove um cliente com soft delete', function () {
        $client = Client::factory()->create();

        $this->deleteJson("/api/v1/clients/{$client->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    });

    it('cacheia o cliente ao exibir e invalida ao atualizar', function () {
        $client = Client::factory()->create();
        $chave = "client:{$client->id}";

        expect(Cache::has($chave))->toBeFalse();

        $this->getJson("/api/v1/clients/{$client->id}")->assertOk();
        expect(Cache::has($chave))->toBeTrue();

        $this->putJson("/api/v1/clients/{$client->id}", ['nome' => 'Outro Nome'])->assertOk();
        expect(Cache::has($chave))->toBeFalse();
    });

    it('mascara dados sensiveis nos logs ao criar cliente', function () {
        Log::spy();

        $this->postJson('/api/v1/clients', [
            'nome' => 'Joao da Silva',
            'documento' => '111.444.777-35',
            'tipo_documento' => 'cpf',
            'email' => 'joao@example.com',
        ])->assertCreated();

        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) {
                return $message === 'http.request'
                    && ($context['payload']['documento'] ?? null) === '111******35'
                    && ($context['payload']['email'] ?? null) === 'j***@example.com'
                    && ($context['payload']['nome'] ?? null) === '***';
            })
            ->once();
    });
});
