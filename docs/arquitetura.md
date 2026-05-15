# Arquitetura

O Pactum segue uma arquitetura em três camadas — **Controller → Service →
Repository** — com inversão de dependência via interfaces e separação clara de
responsabilidades.

## Diagrama de camadas

```
┌──────────────────────────────────────────────────────────────────────────┐
│                              HTTP (Cliente)                              │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │
                                  │ Request (com Idempotency-Key, Bearer token)
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                           Middleware Pipeline                            │
│                                                                          │
│  TraceId  →  MaskSensitiveData  →  SecurityHeaders  →  Sanctum  →        │
│  Throttle  →  IdempotencyKey                                             │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                      Controller (Http\Controllers\Api\V1)                │
│                                                                          │
│  • Resolve FormRequest (validação + autorização)                         │
│  • Chama o Service correspondente                                        │
│  • Aplica Policy via $this->authorize()                                  │
│  • Retorna API Resource                                                  │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                            Service (App\Services)                        │
│                                                                          │
│  • Orquestra fluxo e aplica regras de negócio                            │
│  • Gerencia transações (DB::transaction)                                 │
│  • Gerencia cache (Cache::remember / forget)                             │
│  • Lança exceções de domínio (ContractCannotBeEditedException, …)        │
│  • Depende de InterfaceX (não da implementação)                          │
└──────────────┬───────────────────────────────────────┬───────────────────┘
               │                                       │
               ▼                                       ▼
┌─────────────────────────────────┐  ┌───────────────────────────────────┐
│  Repository (App\Repositories)  │  │   Outros services / componentes   │
│                                 │  │                                   │
│  • Interface + impl. Eloquent   │  │  • DiscountCalculator             │
│  • Eager loading centralizado   │  │  • ContractHistoryService         │
│  • Optimistic lock no update    │  │  • Observers (cache, histórico)   │
│  • Sem regra de negócio         │  │                                   │
└──────────────┬──────────────────┘  └───────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────────────────────┐
│                          Persistência / Infra                            │
│                                                                          │
│   MySQL 8.0    Redis 7.x    Queue Redis (job de histórico)               │
└──────────────────────────────────────────────────────────────────────────┘
```

## Fluxo de uma request — `POST /api/v1/contracts`

1. **Middleware pipeline**
   - `TraceId` gera/propaga `X-Trace-Id` no log e na resposta.
   - `MaskSensitiveData` redige CPF/CNPJ/email no log da request (LGPD).
   - `SecurityHeaders` aplica CSP, X-Frame-Options, Referrer-Policy, etc. na resposta.
   - `auth:sanctum` autentica o usuário pelo Bearer token.
   - `throttle:10,1` limita criação de contrato a 10/min por usuário.
   - `idempotency` valida e consulta `Idempotency-Key` em Redis — retorna resposta
     original se a chave já foi processada (24h).

2. **Controller** (`ContractController::store`)
   - Recebe `StoreContractRequest` validado (cliente existe, datas coerentes,
     itens válidos).
   - Chama `$this->authorize('create', Contract::class)` → `ContractPolicy::create`.
   - Delega para `ContractService::create($dados)`.
   - Retorna `ContractResource` com status 201.

3. **Service** (`ContractService::create`)
   - Carrega cliente pelo repositório; lança `InactiveClientException` se inativo.
   - Abre transação com retry em deadlock (`DB::transaction(fn() => …, 3)`).
   - Cria o contrato e seus itens via repositório.
   - Hidrata o resultado com `findWithItems()` (eager load) para o resource ter
     tudo já pronto e não disparar N+1.

4. **Repository** (`EloquentContractRepository::create` + `addItem`)
   - `Contract::create($dados)` dispara `ContractObserver::created`, que enfileira
     `RegistrarHistoricoContrato`.
   - Cada `addItem` dispara `ContractItemObserver::created`, que invalida
     `contract:{id}:total` no Redis.

5. **Resource** (`ContractResource`)
   - Inclui `total_calculado` via `app(ContractService::class)->calculateTotal()`,
     que faz `Cache::remember('contract:{id}:total', …, fn() => discount->apply(...))`.
   - Como é a primeira leitura, calcula e armazena.

## Como o cache do total se mantém consistente

```
┌─────────────────────────────────┐
│  GET /contracts/{id}            │  cache miss → calcula → guarda em Redis (TTL 1h)
└─────────────────────────────────┘

┌─────────────────────────────────┐
│  POST /contracts/{id}/items     │
│  DELETE /contracts/{id}/items/* │  ContractItemObserver invalida o cache do contrato pai
└─────────────────────────────────┘

┌─────────────────────────────────┐
│  PUT /contracts/{id}            │
│  POST /contracts/{id}/cancel    │  repository.updateWithVersion() chama
└─────────────────────────────────┘  Cache::forget() porque o caminho bypassa o Eloquent

┌─────────────────────────────────┐
│  DELETE /contracts/{id}         │  ContractObserver::deleted invalida o cache
└─────────────────────────────────┘
```

A invalidação explícita é o mecanismo principal de correção. O TTL é apenas
fallback de eventual consistency.

## Por que existe duplicação (aparente) entre Observer e Repository

O caminho de optimistic lock (`updateWithVersion`) usa `DB::table()->update()`,
que **não** dispara eventos do Eloquent. Por isso, `ContractObserver::updated`
não é invocado quando `update`/`cancel` passa pelo lock.

Para garantir comportamento consistente, o repositório:

1. Faz o `UPDATE ... WHERE version = :expected`.
2. Chama `Cache::forget("contract:{$id}:total")` explicitamente.
3. Dispara `RegistrarHistoricoContrato` explicitamente.

O Observer cobre `created` e `deleted` (que **sim** passam pelo Eloquent). A
combinação garante que toda mutação aciona invalidação + histórico — independente
do caminho de escrita.

## Pontos de extensão

- **Nova regra de desconto**: ver [como-adicionar-regra-de-desconto.md](como-adicionar-regra-de-desconto.md).
- **Nova entidade**: criar enum → migration → model + factory → interface + impl
  do repository → service → request + resource + controller → rota + teste. O
  diretório `Database/Seeders/ContractSeeder.php` serve de exemplo da pegada.
- **Nova policy**: adicionar arquivo em `app/Policies/` seguindo a convenção
  `Model\Foo` → `Policies\FooPolicy`. Laravel auto-registra.
