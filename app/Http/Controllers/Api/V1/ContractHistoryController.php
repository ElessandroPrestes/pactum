<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ContractHistoryResource;
use App\Models\Contract;
use App\Services\ContractHistoryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContractHistoryController extends ApiController
{
    public function __construct(
        private readonly ContractHistoryService $service,
    ) {}

    public function index(Request $request, Contract $contract): AnonymousResourceCollection
    {
        $this->authorize('viewHistory', $contract);

        return ContractHistoryResource::collection(
            $this->service->paginate($contract, $this->perPagina($request))
        );
    }
}
