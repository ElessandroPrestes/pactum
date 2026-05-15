# Melhorias futuras

Lista priorizada de evoluções que ficaram fora do escopo atual. Cada item tem
contexto, esboço de solução e quando faz sentido investir.

## Performance e escala

### Cursor pagination em `/contracts/{id}/history`

**Contexto**: o histórico é insert-only e tende a crescer indefinidamente.
Paginação por offset (`->paginate()`) força o MySQL a varrer e descartar registros
em páginas profundas.

**Solução**: trocar por `->cursorPaginate('created_at')` no
`ContractHistoryService`. Resource já tem `created_at` na saída, então a chave do
cursor é natural.

**Quando**: assim que algum contrato passar de ~1000 eventos, ou se aparecer
endpoint público com paginação profunda.

### Métricas Prometheus

**Contexto**: hoje a observabilidade depende de logs JSON + `X-Trace-Id`. Para
detectar regressão de latência ou pico de erro 5xx, precisamos de séries
temporais.

**Solução**: pacote `spatie/laravel-prometheus`, endpoint `GET /metrics`
restringido por IP/secret, métricas de: `http_request_duration_seconds`,
`http_requests_total{status}`, `contract_total_calculation_duration_seconds`,
`idempotency_cache_hit_total`.

**Quando**: quando houver SRE/oncall responsável. Hoje, log estruturado +
Cloudwatch/Loki cobrem.

### Tracing distribuído com OpenTelemetry

**Contexto**: o `X-Trace-Id` é propagado em log, mas não cruza para Redis/MySQL.

**Solução**: pacote `open-telemetry/sdk` + auto-instrumentation de PDO e Predis.
Trace_id no header continua o mesmo; spans aparecem no Jaeger/Tempo.

**Quando**: quando a stack tiver mais hops (microserviços, gateways externos).

## Segurança e LGPD

### Criptografia em repouso para `documento` e `email`

**Contexto**: hoje CPF/CNPJ e email são mascarados em log (`MaskSensitiveData`),
mas armazenados em texto plano em `clients`. Para um pentest formal ou exigência
de LGPD/ISO 27001 explícita, o ideal é cifrar em repouso.

**Solução**: Cast customizado usando `Crypt::encryptString`/`decryptString`.
Atenção: `unique` em `documento` deixa de funcionar diretamente — solução é
manter coluna `documento_hash` (SHA-256 do documento limpo) com índice unique,
e o campo cifrado na coluna `documento`. Validações usam o hash.

**Quando**: quando o requisito formal de criptografia em repouso aparecer.

### RBAC completo nas Policies

**Contexto**: as Policies hoje retornam `true` para qualquer usuário
autenticado. A estrutura está pronta para evoluir — métodos, controllers, rotas
e testes já passam por elas.

**Solução**: tabela `roles` + `role_user`, trait `HasRoles` no `User`, Policy
checando `$user->hasRole('admin')` ou `$user->can($ability)`. Recomendado:
`spatie/laravel-permission`.

**Quando**: quando aparecer perfil diferente de "admin tudo".

### Refresh token e revogação de Sanctum

**Contexto**: tokens Sanctum hoje não expiram (default `null`). Para perda de
token, depende-se de revogação manual via dashboard de admin.

**Solução**: configurar `sanctum.expiration` (em minutos), implementar endpoint
`POST /tokens/refresh` que invalida o atual e emite novo, e endpoint
`POST /tokens/revoke` para logout.

**Quando**: quando aparecer aplicação cliente com fluxo de sessão real (mobile
ou SPA pública).

### CORS configurado

**Contexto**: `config/cors.php` não foi customizado.

**Solução**: declarar `allowed_origins`, `allowed_methods`, `allowed_headers`
restritos quando aparecer cliente web/mobile real. Incluir `Idempotency-Key` e
`Authorization` em `allowed_headers`.

**Quando**: antes do primeiro deploy com cliente externo.

## API e DX

### OpenAPI spec gerado

**Contexto**: documentação dos endpoints está em README + curl. Conforme
endpoints crescem, manter sincronizado vira fricção.

**Solução**: anotações Swagger em FormRequests/Resources via
`darkaonline/l5-swagger`, ou gerar do Pest com `pestphp/pest-plugin-doc`.
Endpoint público `/api/v1/openapi.json` + Swagger UI em dev/staging.

**Quando**: quando aparecer cliente externo consumindo a API.

### Versionamento de Resource

**Contexto**: a API já está em `/v1`, mas mudanças no Resource (campos novos,
remoção, renomeação) podem quebrar clientes.

**Solução**: política explícita: campo novo nunca quebra; remoção/renomeação
exige `v2`. Documentar em ADR quando a primeira `v2` aparecer.

**Quando**: na primeira breaking change real.

## Domínio

### Regras de desconto por banco / cupom

**Contexto**: hoje regras estão em `config/contract.php` — extensíveis em código
mas não dinâmicas em runtime.

**Solução**: tabela `discount_rules` com `nome`, `tipo`, `parametros` (JSON),
`ativo`, `vigencia_inicio`, `vigencia_fim`. `DiscountCalculator` lê do banco em
vez da config, com cache de 5min.

**Quando**: quando regras precisarem ser editadas por operador (admin UI), não
por deploy.

### Validação de email com lista de domínios bloqueados

**Contexto**: hoje só validamos formato com `email`.

**Solução**: lista de domínios temporários (`mailinator.com` etc.) em config +
rule custom no `StoreClientRequest`. Ou pacote `chr15k/laravel-email-validator`.

**Quando**: quando aparecer abuso real.

## Operação

### Rate limit por usuário e endpoint, com burst

**Contexto**: hoje `throttle:api` (60/min/IP) + throttle refinado nos POSTs
sensíveis. Burst alto é tratado como abuso.

**Solução**: limiter custom em `RouteServiceProvider` com `Limit::perMinute(...)`
diferenciado por: usuário autenticado vs anônimo, endpoint, tier do plano.

**Quando**: quando aparecer plano pago com SLA de throughput.

### Worker queue com auto-scaling

**Contexto**: hoje a queue Redis tem 1 worker fixo (`docker-compose.yml`).

**Solução**: Horizon (`laravel/horizon`) com supervisores autoescalando por
tamanho de fila, ou Kubernetes com HPA pelo length da fila.

**Quando**: quando o volume de histórico passar de ~1000 contratos com mutação
frequente.

### Backup automatizado e PITR do MySQL

**Contexto**: não documentado no MVP.

**Solução**: snapshot diário + binlog para Point-In-Time Recovery, retenção
configurada conforme política. Em managed (RDS/Cloud SQL) já vem pronto.

**Quando**: antes de produção real.
