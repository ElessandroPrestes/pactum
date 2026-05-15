<?php

declare(strict_types=1);

use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Enums\DocumentType;
use App\Exceptions\ConcurrencyConflictException;
use App\Exceptions\ContractCannotBeEditedException;
use App\Exceptions\InactiveClientException;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Services\ContractService;
use App\Services\DiscountCalculator;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->repository = Mockery::mock(ContractRepositoryInterface::class);
    $this->clientRepository = Mockery::mock(ClientRepositoryInterface::class);
    $this->discountCalculator = Mockery::mock(DiscountCalculator::class);
    $this->service = new ContractService(
        $this->repository,
        $this->clientRepository,
        $this->discountCalculator,
    );
});

function fakeClient(ClientStatus $status = ClientStatus::Ativo): Client
{
    $client = new Client([
        'nome' => 'Acme',
        'documento' => '11222333000181',
        'tipo_documento' => DocumentType::Cnpj,
        'email' => 'a@b.com',
        'status' => $status,
    ]);
    $client->id = 1;
    $client->setRawAttributes(array_merge($client->getAttributes(), ['id' => 1, 'status' => $status->value]));
    $client->syncOriginal();

    return $client;
}

function fakeContract(ContractStatus $status = ContractStatus::Ativo, int $version = 1): Contract
{
    $contract = new Contract([
        'client_id' => 1,
        'data_inicio' => '2026-01-01',
        'status' => $status,
        'version' => $version,
    ]);
    $contract->setRawAttributes([
        'id' => 10,
        'client_id' => 1,
        'data_inicio' => '2026-01-01',
        'data_fim' => null,
        'status' => $status->value,
        'version' => $version,
    ]);
    $contract->syncOriginal();

    return $contract;
}

describe('ContractService', function () {
    it('rejeita criacao de contrato para cliente inativo', function () {
        $this->clientRepository->shouldReceive('find')
            ->once()
            ->with(1)
            ->andReturn(fakeClient(ClientStatus::Inativo));

        expect(fn () => $this->service->create([
            'client_id' => 1,
            'data_inicio' => '2026-01-01',
        ]))->toThrow(InactiveClientException::class);
    });

    it('rejeita criacao de contrato quando cliente nao existe', function () {
        $this->clientRepository->shouldReceive('find')
            ->once()
            ->with(99)
            ->andReturn(null);

        expect(fn () => $this->service->create([
            'client_id' => 99,
            'data_inicio' => '2026-01-01',
        ]))->toThrow(InactiveClientException::class);
    });

    it('rejeita update em contrato cancelado', function () {
        $contract = fakeContract(ContractStatus::Cancelado);

        expect(fn () => $this->service->update($contract, ['data_fim' => '2026-12-31'], 1))
            ->toThrow(ContractCannotBeEditedException::class);
    });

    it('rejeita add item em contrato cancelado', function () {
        $contract = fakeContract(ContractStatus::Cancelado);

        expect(fn () => $this->service->addItem($contract, [
            'service_id' => 1,
            'quantidade' => 1,
            'valor_unitario' => 100,
        ]))->toThrow(ContractCannotBeEditedException::class);
    });

    it('rejeita cancel em contrato ja cancelado', function () {
        $contract = fakeContract(ContractStatus::Cancelado);

        expect(fn () => $this->service->cancel($contract, 1))
            ->toThrow(ContractCannotBeEditedException::class);
    });

    it('propaga conflito de concorrencia no update', function () {
        $contract = fakeContract(ContractStatus::Ativo, 2);

        $this->repository->shouldReceive('updateWithVersion')
            ->once()
            ->with($contract, ['data_fim' => '2026-12-31'], 1)
            ->andThrow(new ConcurrencyConflictException);

        expect(fn () => $this->service->update($contract, ['data_fim' => '2026-12-31'], 1))
            ->toThrow(ConcurrencyConflictException::class);
    });

    it('propaga conflito de concorrencia no cancel', function () {
        $contract = fakeContract();

        $this->repository->shouldReceive('updateWithVersion')
            ->once()
            ->with($contract, ['status' => 'cancelado'], 9)
            ->andThrow(new ConcurrencyConflictException);

        expect(fn () => $this->service->cancel($contract, 9))
            ->toThrow(ConcurrencyConflictException::class);
    });

    it('ignora status e version no payload de update por seguranca', function () {
        $contract = fakeContract();
        $atualizado = fakeContract();

        $this->repository->shouldReceive('updateWithVersion')
            ->once()
            ->with(
                $contract,
                Mockery::on(fn (array $dados): bool => ! array_key_exists('status', $dados) && ! array_key_exists('version', $dados)),
                1,
            )
            ->andReturn($atualizado);

        $resultado = $this->service->update($contract, [
            'data_fim' => '2026-12-31',
            'status' => 'cancelado',
            'version' => 999,
        ], 1);

        expect($resultado)->toBe($atualizado);
    });

    it('remove item somente se pertencer ao contrato informado', function () {
        $contract = fakeContract();
        $itemDeOutroContrato = new ContractItem(['contract_id' => 999, 'service_id' => 1, 'quantidade' => 1, 'valor_unitario' => 10]);
        $itemDeOutroContrato->contract_id = 999;

        expect(fn () => $this->service->removeItem($contract, $itemDeOutroContrato))
            ->toThrow(ContractCannotBeEditedException::class);
    });
});
