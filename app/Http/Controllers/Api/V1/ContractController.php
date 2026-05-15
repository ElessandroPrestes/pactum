<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\CancelContractRequest;
use App\Http\Requests\StoreContractItemRequest;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractItemResource;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Services\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ContractController extends ApiController
{
    public function __construct(
        private readonly ContractService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->only(['client_id', 'status', 'data_inicio']);

        return ContractResource::collection(
            $this->service->paginate($filtros, $this->perPagina($request))
        );
    }

    public function store(StoreContractRequest $request): JsonResponse
    {
        /** @var array{client_id: int, data_inicio: string, data_fim?: ?string, itens?: list<array{service_id: int, quantidade: int, valor_unitario: numeric-string|float|int}>} $dados */
        $dados = $request->validated();

        $contract = $this->service->create($dados);

        return ContractResource::make($contract)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Contract $contract): ContractResource
    {
        $hidratado = $this->service->find($contract->id) ?? $contract;

        return ContractResource::make($hidratado);
    }

    public function update(UpdateContractRequest $request, Contract $contract): ContractResource
    {
        $dados = $request->validated();
        $version = (int) $dados['version'];
        unset($dados['version']);

        $atualizado = $this->service->update($contract, $dados, $version);

        return ContractResource::make(
            $this->service->find($atualizado->id) ?? $atualizado
        );
    }

    public function destroy(Contract $contract): Response
    {
        $this->service->delete($contract);

        return response()->noContent();
    }

    public function cancel(CancelContractRequest $request, Contract $contract): ContractResource
    {
        $version = (int) $request->validated()['version'];

        $cancelado = $this->service->cancel($contract, $version);

        return ContractResource::make(
            $this->service->find($cancelado->id) ?? $cancelado
        );
    }

    public function storeItem(StoreContractItemRequest $request, Contract $contract): JsonResponse
    {
        /** @var array{service_id: int, quantidade: int, valor_unitario: numeric-string|float|int} $dados */
        $dados = $request->validated();

        $item = $this->service->addItem($contract, $dados);

        return ContractItemResource::make($item->load('service'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function destroyItem(Contract $contract, ContractItem $item): Response
    {
        $this->service->removeItem($contract, $item);

        return response()->noContent();
    }
}
