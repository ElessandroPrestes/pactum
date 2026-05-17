<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ConcurrencyConflict',
    title: 'Conflito de concorrencia (409)',
    description: 'Versao informada no payload nao corresponde a versao atual do contrato.',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Conflito de versao no contrato. Recarregue antes de tentar novamente.'),
        new OA\Property(property: 'trace_id', type: 'string', nullable: true),
    ],
)]
final class ConcurrencyConflict {}
