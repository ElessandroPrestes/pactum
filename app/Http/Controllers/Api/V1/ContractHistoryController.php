<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ContractHistoryResource;
use App\Models\Contract;
use App\Services\ContractHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ContractHistoryController extends ApiController
{
    public function __construct(
        private readonly ContractHistoryService $service,
    ) {}

    #[OA\Get(
        path: '/contracts/{contract}/history',
        summary: 'Lista o historico de alteracoes do Contrato (paginado).',
        security: [['sanctum' => []]],
        tags: ['Historico do Contrato'],
        parameters: [
            new OA\Parameter(name: 'contract', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(ref: '#/components/parameters/Page'),
            new OA\Parameter(ref: '#/components/parameters/PerPage'),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pagina de historico',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ContractHistory')),
                    new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Nao autenticado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
            new OA\Response(response: 404, description: 'Contrato nao encontrado.', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')),
        ],
    )]
    public function index(Request $request, Contract $contract): AnonymousResourceCollection
    {
        $this->authorize('viewHistory', $contract);

        return ContractHistoryResource::collection(
            $this->service->paginate($contract, $this->perPagina($request))
        );
    }
}
