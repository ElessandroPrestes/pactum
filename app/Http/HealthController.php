<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class HealthController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'mysql' => $this->probe(fn () => DB::connection()->getPdo()),
            'redis' => $this->probe(fn () => Redis::connection()->ping()),
        ];

        $healthy = ! in_array('error', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function probe(callable $check): string
    {
        try {
            $check();

            return 'ok';
        } catch (Throwable) {
            return 'error';
        }
    }
}
