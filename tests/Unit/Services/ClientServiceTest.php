<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Services\ClientService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->repository = Mockery::mock(ClientRepositoryInterface::class);
    $this->service = new ClientService($this->repository);
});

describe('ClientService', function () {
    it('delega a listagem paginada ao repositorio', function () {
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $filtros = ['status' => 'ativo'];

        $this->repository->shouldReceive('paginate')
            ->once()
            ->with($filtros, 25)
            ->andReturn($paginator);

        expect($this->service->paginate($filtros, 25))->toBe($paginator);
    });

    it('busca no repositorio quando o cliente nao esta em cache', function () {
        $client = new Client;

        $this->repository->shouldReceive('find')
            ->once()
            ->with(7)
            ->andReturn($client);

        expect($this->service->find(7))->toBe($client);
    });

    it('reutiliza o cache em buscas subsequentes do mesmo cliente', function () {
        $client = new Client;

        $this->repository->shouldReceive('find')
            ->once()
            ->with(7)
            ->andReturn($client);

        $this->service->find(7);

        expect($this->service->find(7))->toBe($client);
    });

    it('cria o cliente sempre com status ativo ignorando o status informado', function () {
        $client = new Client;

        $this->repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(
                fn (array $dados): bool => $dados['status'] === ClientStatus::Ativo->value
            ))
            ->andReturn($client);

        $resultado = $this->service->create([
            'nome' => 'Acme',
            'status' => ClientStatus::Inativo->value,
        ]);

        expect($resultado)->toBe($client);
    });

    it('delega a atualizacao ao repositorio', function () {
        $client = new Client;
        $dados = ['nome' => 'Novo Nome'];

        $this->repository->shouldReceive('update')
            ->once()
            ->with($client, $dados)
            ->andReturn($client);

        expect($this->service->update($client, $dados))->toBe($client);
    });

    it('delega a remocao ao repositorio', function () {
        $client = new Client;

        $this->repository->shouldReceive('delete')
            ->once()
            ->with($client);

        expect($this->service->delete($client))->toBeNull();
    });
});
