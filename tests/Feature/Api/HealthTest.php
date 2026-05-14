<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

test('reporta status ok quando mysql e redis respondem', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'mysql' => 'ok',
                'redis' => 'ok',
            ],
        ]);
});

test('propaga o header de trace id na resposta', function () {
    $this->getJson('/health')
        ->assertHeader('X-Trace-Id');
});

test('retorna 503 quando o mysql esta indisponivel', function () {
    DB::shouldReceive('connection')->andThrow(new RuntimeException('mysql indisponivel'));

    $this->getJson('/health')
        ->assertStatus(503)
        ->assertJson([
            'status' => 'error',
            'checks' => ['mysql' => 'error', 'redis' => 'ok'],
        ]);
});

test('retorna 503 quando o redis esta indisponivel', function () {
    Redis::shouldReceive('connection')->andThrow(new RuntimeException('redis indisponivel'));

    $this->getJson('/health')
        ->assertStatus(503)
        ->assertJson([
            'status' => 'error',
            'checks' => ['mysql' => 'ok', 'redis' => 'error'],
        ]);
});
