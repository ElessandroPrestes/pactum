<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ServiceController extends ApiController
{
    public function __construct(
        private readonly ServiceService $service,
    ) {}

    #[OA\Get(
        path: '/services',
        summary: 'Lista servicos (paginado, com filtros).',
        security: [['sanctum' => []]],
        tags: ['Servicos'],
        parameters: [
            new OA\Parameter(name: 'nome', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'ativo', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de servicos',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Service')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Service::class);

        $filtros = $request->only(['nome']);

        if ($request->has('ativo')) {
            $filtros['ativo'] = $request->boolean('ativo');
        }

        return ServiceResource::collection(
            $this->service->paginate($filtros, $this->perPagina($request))
        );
    }

    #[OA\Post(
        path: '/services',
        summary: 'Cria um novo Servico.',
        security: [['sanctum' => []]],
        tags: ['Servicos'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreServiceRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Servico criado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Service')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Erro de validacao.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->authorize('create', Service::class);

        $service = $this->service->create($request->validated());

        return ServiceResource::make($service)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/services/{service}',
        summary: 'Recupera um Servico.',
        security: [['sanctum' => []]],
        tags: ['Servicos'],
        parameters: [new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Servico', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Service')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Servico nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function show(Service $service): ServiceResource
    {
        $this->authorize('view', $service);

        return ServiceResource::make($service);
    }

    #[OA\Put(
        path: '/services/{service}',
        summary: 'Atualiza um Servico.',
        security: [['sanctum' => []]],
        tags: ['Servicos'],
        parameters: [new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateServiceRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Servico atualizado', content: new OA\JsonContent(properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Service')])),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Servico nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 422, description: 'Erro de validacao.', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ],
    )]
    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        return ServiceResource::make(
            $this->service->update($service, $request->validated())
        );
    }

    #[OA\Delete(
        path: '/services/{service}',
        summary: 'Exclui (soft delete) um Servico.',
        security: [['sanctum' => []]],
        tags: ['Servicos'],
        parameters: [new OA\Parameter(name: 'service', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 204, description: 'Servico removido.'),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 403, description: 'Sem permissao.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Servico nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function destroy(Service $service): Response
    {
        $this->authorize('delete', $service);

        $this->service->delete($service);

        return response()->noContent();
    }
}
