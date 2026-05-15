<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Service;
use Illuminate\Support\Facades\Cache;

class ServiceObserver
{
    public function created(Service $service): void
    {
        $this->flushCache();
    }

    public function updated(Service $service): void
    {
        $this->flushCache();
    }

    public function deleted(Service $service): void
    {
        $this->flushCache();
    }

    private function flushCache(): void
    {
        Cache::tags(['services'])->flush();
    }
}
