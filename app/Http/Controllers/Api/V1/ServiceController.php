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

class ServiceController extends ApiController
{
    public function __construct(
        private readonly ServiceService $service,
    ) {}

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

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->authorize('create', Service::class);

        $service = $this->service->create($request->validated());

        return ServiceResource::make($service)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Service $service): ServiceResource
    {
        $this->authorize('view', $service);

        return ServiceResource::make($service);
    }

    public function update(UpdateServiceRequest $request, Service $service): ServiceResource
    {
        $this->authorize('update', $service);

        return ServiceResource::make(
            $this->service->update($service, $request->validated())
        );
    }

    public function destroy(Service $service): Response
    {
        $this->authorize('delete', $service);

        $this->service->delete($service);

        return response()->noContent();
    }
}
