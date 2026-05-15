<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Client;
use Illuminate\Support\Facades\Cache;

class ClientObserver
{
    public function updated(Client $client): void
    {
        $this->invalidarCache($client);
    }

    public function deleted(Client $client): void
    {
        $this->invalidarCache($client);
    }

    private function invalidarCache(Client $client): void
    {
        Cache::forget("client:{$client->id}");
    }
}
