<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ClientController extends ApiController
{
    public function __construct(
        private readonly ClientService $service,
    ) {}

    #[OA\Get(
        path: '/clients',
        summary: 'Lista clientes (paginado, com filtros).',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['ativo', 'inativo'])),
            new OA\Parameter(name: 'documento', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'nome', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de clientes',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Client')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Client::class);

        $filtros = $request->only(['status', 'documento', 'nome']);

        return ClientResource::collection(
            $this->service->paginate($filtros, $this->perPagina($request))
        );
    }

    #[OA\Post(
        path: '/clients',
        summary: 'Cria um novo Cliente (idempotente via Idempotency-Key).',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [new OA\Parameter(ref: '#/components/parameters/IdempotencyKey')],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreClientRequest')),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Cliente criado',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Client')]),
            ),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Erro de validacao.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Rate limit excedido.'),
        ],
    )]
    public function store(StoreClientRequest $request): JsonResponse
    {
        $this->authorize('create', Client::class);

        $client = $this->service->create($request->validated());

        return ClientResource::make($client)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/clients/{client}',
        summary: 'Recupera um Cliente.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'client', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Client')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Cliente nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Client $client): ClientResource
    {
        $this->authorize('view', $client);

        return ClientResource::make($this->service->find($client->id));
    }

    #[OA\Put(
        path: '/clients/{client}',
        summary: 'Atualiza um Cliente.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'client', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateClientRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Cliente atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Client')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Cliente nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Erro de validacao.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        $this->authorize('update', $client);

        return ClientResource::make(
            $this->service->update($client, $request->validated())
        );
    }

    #[OA\Delete(
        path: '/clients/{client}',
        summary: 'Exclui (soft delete) um Cliente.',
        security: [['sanctum' => []]],
        tags: ['Clientes'],
        parameters: [
            new OA\Parameter(name: 'client', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cliente removido.'),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Cliente nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Client $client): Response
    {
        $this->authorize('delete', $client);

        $this->service->delete($client);

        return response()->noContent();
    }
}
