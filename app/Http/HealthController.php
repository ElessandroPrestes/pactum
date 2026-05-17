<?php

declare(strict_types=1);

namespace App\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use OpenApi\Attributes as OA;
use Throwable;

class HealthController
{
    #[OA\Get(
        path: '/health',
        summary: 'Liveness e readiness (MySQL e Redis).',
        servers: [new OA\Server(url: 'http://localhost:8000', description: 'Host raiz (fora de /api/v1).')],
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Servico saudavel',
                content: new OA\JsonContent(
                    required: ['status', 'checks'],
                    properties: [
                        new OA\Property(property: 'status', type: 'string', enum: ['ok', 'error'], example: 'ok'),
                        new OA\Property(
                            property: 'checks',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'mysql', type: 'string', enum: ['ok', 'error'], example: 'ok'),
                                new OA\Property(property: 'redis', type: 'string', enum: ['ok', 'error'], example: 'ok'),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 503, description: 'Alguma dependencia falhou.'),
        ],
    )]
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
