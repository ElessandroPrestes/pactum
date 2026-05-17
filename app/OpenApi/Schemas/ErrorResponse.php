<?php

declare(strict_types=1);

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ErrorResponse',
    title: 'Erro padrao',
    description: 'Formato padrao de erro retornado pela API.',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Recurso nao encontrado.'),
        new OA\Property(property: 'trace_id', type: 'string', nullable: true, example: '01J3K4M5N6P7Q8R9S0T1V2W3'),
    ],
)]
final class ErrorResponse {}
