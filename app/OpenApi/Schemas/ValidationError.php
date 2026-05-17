<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ValidationError',
    title: 'Erro de validacao (422)',
    description: 'Retornado quando o payload viola as regras do FormRequest.',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: ['email' => ['O campo email e obrigatorio.']],
        ),
        new OA\Property(property: 'trace_id', type: 'string', nullable: true),
    ],
)]
final class ValidationError {}
