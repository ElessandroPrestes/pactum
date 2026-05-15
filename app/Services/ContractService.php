<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ClientStatus;
use App\Enums\ContractStatus;
use App\Exceptions\ContractCannotBeEditedException;
use App\Exceptions\InactiveClientException;
use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(
        private readonly ContractRepositoryInterface $repository,
        private readonly ClientRepositoryInterface $clientRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $filtros
     * @return LengthAwarePaginator<int, Contract>
     */
    public function paginate(array $filtros = [], int $porPagina = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filtros, $porPagina);
    }

    public function find(int $id): ?Contract
    {
        return $this->repository->findWithItems($id);
    }

    /**
     * @param  array{client_id: int, data_inicio: string, data_fim?: ?string, itens?: list<array{service_id: int, quantidade: int, valor_unitario: numeric-string|float|int}>}  $dados
     */
    public function create(array $dados): Contract
    {
        $cliente = $this->clientRepository->find($dados['client_id']);

        if (! $cliente instanceof Client) {
            throw new InactiveClientException('Cliente nao encontrado.');
        }

        if ($cliente->status !== ClientStatus::Ativo) {
            throw new InactiveClientException;
        }

        $itens = $dados['itens'] ?? [];
        unset($dados['itens']);

        $dados['status'] = ContractStatus::Ativo->value;
        $dados['version'] = 1;

        /** @var Contract $contract */
        $contract = DB::transaction(function () use ($dados, $itens): Contract {
            $contract = $this->repository->create($dados);

            foreach ($itens as $item) {
                $this->repository->addItem($contract, $item);
            }

            return $contract;
        }, 3);

        $hidratado = $this->repository->findWithItems($contract->id);

        return $hidratado ?? $contract;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public function update(Contract $contract, array $dados, int $expectedVersion): Contract
    {
        $this->garantirEditavel($contract);

        unset($dados['status'], $dados['version']);

        return $this->repository->updateWithVersion($contract, $dados, $expectedVersion);
    }

    public function cancel(Contract $contract, int $expectedVersion): Contract
    {
        $this->garantirEditavel($contract);

        return $this->repository->updateWithVersion(
            $contract,
            ['status' => ContractStatus::Cancelado->value],
            $expectedVersion,
        );
    }

    public function delete(Contract $contract): void
    {
        $this->repository->delete($contract);
    }

    /**
     * @param  array{service_id: int, quantidade: int, valor_unitario: numeric-string|float|int}  $dados
     */
    public function addItem(Contract $contract, array $dados): ContractItem
    {
        $this->garantirEditavel($contract);

        return DB::transaction(
            fn (): ContractItem => $this->repository->addItem($contract, $dados),
            3,
        );
    }

    public function removeItem(Contract $contract, ContractItem $item): void
    {
        $this->garantirEditavel($contract);

        if ($item->contract_id !== $contract->id) {
            throw new ContractCannotBeEditedException('Item nao pertence ao contrato informado.');
        }

        DB::transaction(fn () => $this->repository->removeItem($item), 3);
    }

    private function garantirEditavel(Contract $contract): void
    {
        if ($contract->status === ContractStatus::Cancelado) {
            throw new ContractCannotBeEditedException;
        }
    }
}
