<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    openapi: '3.0.0',
    security: [],
)]
#[OA\Info(
    version: '1.0.0',
    description: 'API REST do Pactum — ERP de contratos e serviços recorrentes mensais. '
        .'Autentique via POST /api/v1/auth/login para obter um token Bearer (Sanctum) '
        .'e use-o no botão Authorize.',
    title: 'Pactum API',
    contact: new OA\Contact(name: 'Equipe Pactum'),
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Servidor da API (versão 1) — ajuste em produção via OA\\Server.',
)]
#[OA\Tag(name: 'Auth', description: 'Autenticação via Sanctum (token Bearer).')]
#[OA\Tag(name: 'Clientes', description: 'CRUD de clientes com validação de CPF/CNPJ.')]
#[OA\Tag(name: 'Servicos', description: 'CRUD de serviços ofertados.')]
#[OA\Tag(name: 'Contratos', description: 'Contratos vinculando clientes a serviços, com optimistic locking.')]
#[OA\Tag(name: 'Itens do Contrato', description: 'Itens vinculados a um contrato.')]
#[OA\Tag(name: 'Historico do Contrato', description: 'Auditoria das alterações de contrato.')]
#[OA\Tag(name: 'Health', description: 'Liveness e readiness da aplicação.')]
#[OA\Parameter(
    parameter: 'PerPage',
    name: 'per_page',
    description: 'Itens por pagina (default 15).',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15),
)]
#[OA\Parameter(
    parameter: 'Page',
    name: 'page',
    description: 'Numero da pagina (default 1).',
    in: 'query',
    required: false,
    schema: new OA\Schema(type: 'integer', minimum: 1, default: 1),
)]
#[OA\Parameter(
    parameter: 'IdempotencyKey',
    name: 'Idempotency-Key',
    description: 'UUID enviado pelo cliente para deduplicar POSTs (TTL 24h).',
    in: 'header',
    required: false,
    schema: new OA\Schema(type: 'string', format: 'uuid'),
)]
final class OpenApiDefinition {}
