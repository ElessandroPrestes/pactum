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
use OpenApi\Attributes as OA;

class ContractController extends ApiController
{
    public function __construct(
        private readonly ContractService $service,
    ) {}

    #[OA\Get(
        path: '/contracts',
        summary: 'Lista contratos (paginado, com filtros).',
        security: [['sanctum' => []]],
        tags: ['Contratos'],
        parameters: [
            new OA\Parameter(name: 'client_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ativo', 'cancelado'])),
            new OA\Parameter(name: 'data_inicio', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de contratos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Contract')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Contract::class);

        $filtros = $request->only(['client_id', 'status', 'data_inicio']);

        return ContractResource::collection(
            $this->service->paginate($filtros, $this->perPagina($request))
        );
    }

    #[OA\Post(
        path: '/contracts',
        summary: 'Cria um Contrato (transacional, idempotente).',
        security: [['sanctum' => []]],
        tags: ['Contratos'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/IdempotencyKey')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreContractRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Contrato criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Contract')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Erro de validacao.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function store(StoreContractRequest $request): JsonResponse
    {
        $this->authorize('create', Contract::class);

        /** @var array{client_id: int, data_inicio: string, data_fim?: ?string, itens?: list<array{service_id: int, quantidade: int, valor_unitario: numeric-string|float|int}>} $dados */
        $dados = $request->validated();

        $contract = $this->service->create($dados);

        return ContractResource::make($contract)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/contracts/{contract}',
        summary: 'Recupera um Contrato com itens e total calculado.',
        security: [['sanctum' => []]],
        tags: ['Contratos'],
        parameters: [new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Contrato', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Contract')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Contract $contract): ContractResource
    {
        $this->authorize('view', $contract);

        $hidratado = $this->service->find($contract->id) ?? $contract;

        return ContractResource::make($hidratado);
    }

    #[OA\Put(
        path: '/contracts/{contract}',
        summary: 'Atualiza um Contrato (exige version atual).',
        security: [['sanctum' => []]],
        tags: ['Contratos'],
        parameters: [new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateContractRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Contrato atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Contract')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflito de versao (optimistic lock).', content: new OA\JsonContent(ref: '#/components/schemas/ConcurrencyConflict')),
            new OA\Response(response: 422, description: 'Erro de validacao ou contrato cancelado.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function update(UpdateContractRequest $request, Contract $contract): ContractResource
    {
        $this->authorize('update', $contract);

        $dados = $request->validated();
        $version = (int) $dados['version'];
        unset($dados['version']);

        $atualizado = $this->service->update($contract, $dados, $version);

        return ContractResource::make(
            $this->service->find($atualizado->id) ?? $atualizado
        );
    }

    #[OA\Delete(
        path: '/contracts/{contract}',
        summary: 'Exclui (soft delete) um Contrato.',
        security: [['sanctum' => []]],
        tags: ['Contratos'],
        parameters: [new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Contrato removido.'),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Contract $contract): Response
    {
        $this->authorize('delete', $contract);

        $this->service->delete($contract);

        return response()->noContent();
    }

    #[OA\Post(
        path: '/contracts/{contract}/cancel',
        summary: 'Cancela um Contrato (exige version atual).',
        security: [['sanctum' => []]],
        tags: ['Contratos'],
        parameters: [new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/CancelContractRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Contrato cancelado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Contract')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 409, description: 'Conflito de versao.', content: new OA\JsonContent(ref: '#/components/schemas/ConcurrencyConflict')),
            new OA\Response(response: 422, description: 'Contrato ja cancelado ou versao invalida.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function cancel(CancelContractRequest $request, Contract $contract): ContractResource
    {
        $this->authorize('cancel', $contract);

        $version = (int) $request->validated()['version'];

        $cancelado = $this->service->cancel($contract, $version);

        return ContractResource::make(
            $this->service->find($cancelado->id) ?? $cancelado
        );
    }

    #[OA\Post(
        path: '/contracts/{contract}/items',
        summary: 'Adiciona um item ao Contrato (idempotente, invalida cache).',
        security: [['sanctum' => []]],
        tags: ['Itens do Contrato'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/IdempotencyKey'),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreContractItemRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Item adicionado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/ContractItem')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Contrato cancelado ou payload invalido.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function storeItem(StoreContractItemRequest $request, Contract $contract): JsonResponse
    {
        $this->authorize('addItem', $contract);

        /** @var array{service_id: int, quantidade: int, valor_unitario: numeric-string|float|int} $dados */
        $dados = $request->validated();

        $item = $this->service->addItem($contract, $dados);

        return ContractItemResource::make($item->load('service'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Delete(
        path: '/contracts/{contract}/items/{item}',
        summary: 'Remove um item do Contrato (invalida cache).',
        security: [['sanctum' => []]],
        tags: ['Itens do Contrato'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'item', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Item removido.'),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato ou item nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Contrato cancelado.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function destroyItem(Contract $contract, ContractItem $item): Response
    {
        $this->authorize('removeItem', $contract);

        $this->service->removeItem($contract, $item);

        return response()->noContent();
    }
}
