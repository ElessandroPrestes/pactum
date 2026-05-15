<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

describe('ApiSecurity', function () {
    it('rejeita listagem de clientes sem autenticacao com 401', function () {
        $this->app->forgetInstance('auth');
        auth()->forgetGuards();

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/clients')
            ->assertStatus(401);
    });

    it('rejeita criacao de contrato sem autenticacao com 401', function () {
        $this->app->forgetInstance('auth');
        auth()->forgetGuards();

        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/contracts', [])
            ->assertStatus(401);
    });

    it('aceita requisicao com token sanctum valido', function () {
        $this->app->forgetInstance('auth');
        auth()->forgetGuards();

        $user = User::factory()->create();
        $token = $user->createToken('teste')->plainTextToken;

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/clients')->assertOk();
    });

    it('aplica headers de seguranca em respostas da api', function () {
        $resposta = $this->getJson('/api/v1/clients')->assertOk();

        $resposta
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none'")
            ->assertHeader('Permissions-Policy')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-site')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');

        expect($resposta->headers->has('X-Powered-By'))->toBeFalse();
    });

    it('aplica csp relaxada para respostas html sem quebrar o welcome', function () {
        $resposta = $this->get('/')->assertOk();

        $csp = (string) $resposta->headers->get('Content-Security-Policy');

        expect($csp)
            ->toContain("default-src 'self'")
            ->toContain("frame-ancestors 'none'")
            ->not->toContain("default-src 'none'");

        $resposta
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    });

    it('remove o header server da resposta', function () {
        $this->getJson('/api/v1/clients')
            ->assertOk()
            ->assertHeaderMissing('Server');
    });

    it('aceita policy autorizando authenticated user em viewAny de clientes', function () {
        Client::factory()->create();

        $this->getJson('/api/v1/clients')
            ->assertOk();
    });

    it('aceita policy autorizando authenticated user em cancel de contrato', function () {
        $contract = Contract::factory()->create(['version' => 1]);

        $this->postJson("/api/v1/contracts/{$contract->id}/cancel", ['version' => 1])
            ->assertOk();
    });

    it('rejeita request sem auth no historico do contrato', function () {
        $contract = Contract::factory()->create();

        $this->app->forgetInstance('auth');
        auth()->forgetGuards();

        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson("/api/v1/contracts/{$contract->id}/history")
            ->assertStatus(401);
    });
});

describe('IdempotencyKey', function () {
    beforeEach(function () {
        Cache::flush();
    });

    it('rejeita post sem header idempotency key com 400', function () {
        $cliente = Client::factory()->create();

        $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/v1/contracts', [
                'client_id' => $cliente->id,
                'data_inicio' => '2026-01-01',
            ], ['Accept' => 'application/json'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Header Idempotency-Key e obrigatorio neste endpoint.');
    });

    it('retorna a mesma resposta para requests repetidas com a mesma chave', function () {
        $cliente = Client::factory()->create();
        $chave = (string) Str::uuid();

        $primeira = $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ], ['Idempotency-Key' => $chave])->assertCreated();

        $segunda = $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ], ['Idempotency-Key' => $chave])->assertCreated();

        expect($segunda->json('data.id'))->toBe($primeira->json('data.id'));
        expect(Contract::count())->toBe(1);
    });

    it('retorna 409 quando mesma chave e usada com payload diferente', function () {
        $cliente = Client::factory()->create();
        $chave = (string) Str::uuid();

        $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ], ['Idempotency-Key' => $chave])->assertCreated();

        $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-06-15',
        ], ['Idempotency-Key' => $chave])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Idempotency-Key ja usada com payload diferente.');
    });

    it('rejeita idempotency key com caracteres invalidos', function () {
        $cliente = Client::factory()->create();

        $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ], ['Idempotency-Key' => 'chave invalida com espaco'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Idempotency-Key invalida.');
    });

    it('rejeita idempotency key muito longa', function () {
        $cliente = Client::factory()->create();

        $this->postJson('/api/v1/contracts', [
            'client_id' => $cliente->id,
            'data_inicio' => '2026-01-01',
        ], ['Idempotency-Key' => str_repeat('a', 200)])
            ->assertStatus(400);
    });

    it('nao exige idempotency em endpoints que nao sao protegidos', function () {
        $contract = Contract::factory()->create(['version' => 1]);

        $this->withHeaders(['Accept' => 'application/json'])
            ->post("/api/v1/contracts/{$contract->id}/cancel", ['version' => 1])
            ->assertOk();
    });
});
