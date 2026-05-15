<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

describe('Auth', function () {
    beforeEach(function () {
        $this->app->forgetInstance('auth');
        auth()->forgetGuards();
    });

    it('autentica com credenciais validas e retorna token e user', function () {
        $user = User::factory()->create([
            'email' => 'admin@pactum.local',
            'password' => Hash::make('segredo123'),
        ]);

        $resposta = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@pactum.local',
            'password' => 'segredo123',
            'device_name' => 'browser-teste',
        ])->assertOk();

        $resposta
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', 'admin@pactum.local')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        expect($resposta->json('user'))->not->toHaveKey('password');
        expect($resposta->json('token'))->toBeString()->not->toBeEmpty();
    });

    it('rejeita credenciais invalidas com 422', function () {
        User::factory()->create([
            'email' => 'admin@pactum.local',
            'password' => Hash::make('correto'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@pactum.local',
            'password' => 'errado',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });

    it('rejeita usuario inexistente com 422 e mensagem generica', function () {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nao-existe@pactum.local',
            'password' => 'qualquer',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    });

    it('rejeita login sem campos obrigatorios', function () {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('aplica throttle de 5 tentativas por minuto no login', function () {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'admin@pactum.local',
                'password' => 'qualquer',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@pactum.local',
            'password' => 'qualquer',
        ])->assertStatus(429);
    });

    it('exige autenticacao em logout', function () {
        $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(401);
    });

    it('exige autenticacao em me', function () {
        $this->withHeaders(['Accept' => 'application/json'])
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    });

    it('revoga o token corrente no logout', function () {
        $user = User::factory()->create();
        $token = $user->createToken('teste')->plainTextToken;
        $tokenId = $user->tokens()->first()?->id;

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/v1/auth/logout')->assertStatus(204);

        expect(PersonalAccessToken::find($tokenId))->toBeNull();
        expect($user->fresh()->tokens()->count())->toBe(0);
    });

    it('retorna o usuario autenticado em me', function () {
        $user = User::factory()->create([
            'email' => 'me@pactum.local',
        ]);
        $token = $user->createToken('teste')->plainTextToken;

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ])->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'me@pactum.local');
    });
});
