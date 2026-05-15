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

class ClientController extends ApiController
{
    public function __construct(
        private readonly ClientService $service,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->only(['status', 'documento', 'nome']);

        return ClientResource::collection(
            $this->service->paginate($filtros, $this->perPagina($request))
        );
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->service->create($request->validated());

        return ClientResource::make($client)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Client $client): ClientResource
    {
        return ClientResource::make($this->service->find($client->id));
    }

    public function update(UpdateClientRequest $request, Client $client): ClientResource
    {
        return ClientResource::make(
            $this->service->update($client, $request->validated())
        );
    }

    public function destroy(Client $client): Response
    {
        $this->service->delete($client);

        return response()->noContent();
    }
}
