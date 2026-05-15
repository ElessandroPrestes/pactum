<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContractRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Eloquent\EloquentClientRepository;
use App\Repositories\Eloquent\EloquentContractRepository;
use App\Repositories\Eloquent\EloquentServiceRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ClientRepositoryInterface::class,
            EloquentClientRepository::class,
        );

        $this->app->bind(
            ServiceRepositoryInterface::class,
            EloquentServiceRepository::class,
        );

        $this->app->bind(
            ContractRepositoryInterface::class,
            EloquentContractRepository::class,
        );
    }
}
