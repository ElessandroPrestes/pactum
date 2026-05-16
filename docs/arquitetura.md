# Arquitetura

O Pactum é composto por um **backend Laravel** (API REST sob `/api/v1`) e uma
**SPA Vue 3** que o consome. O backend segue arquitetura em três camadas —
**Controller → Service → Repository** — com inversão de dependência via
interfaces. A SPA segue um equivalente de três camadas no front:
**View → Store (Pinia) → API client (axios)**.

## Diagrama de camadas (end-to-end)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                          Navegador (SPA Vue 3)                           │
│                                                                          │
│  View (.vue)  →  Store (Pinia)  →  API client (src/api/*.ts)  →          │
│                                       └─ axios instance (src/lib/http)   │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │  Bearer token (Sanctum) + Idempotency-Key
                                  ▼
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

---

## Camada SPA (frontend/)

A SPA é Vue 3 + TypeScript + Pinia + Tailwind, justificada na
[ADR-0007](adr/0007-vue-3-typescript-pinia-tailwind.md).

### Entrypoint unificado (nginx)

SPA e API rodam **na mesma origem**: o `nginx` é o único container exposto ao
host (porta `APP_PORT`, default `8000`) e roteia internamente — decisão em
[ADR-0008](adr/0008-entrypoint-unificado-via-nginx.md).

```
                       ┌─────────────────────────────┐
  Navegador ──:8000──▶ │           nginx             │
                       │                             │
                       │  /api/*    ─┐               │
                       │  /sanctum/* ├─▶ fastcgi ──▶ │ app  (PHP-FPM)
                       │  /health    ┘               │
                       │                             │
                       │  /*  ─▶ proxy_pass  ──────▶ │ frontend  (Vite + HMR
                       │           (HTTP/1.1 +       │            via WebSocket)
                       │            Upgrade)         │
                       └─────────────────────────────┘
```

Consequências práticas:

- `VITE_API_BASE_URL=/api/v1` (path relativo) — mesma origem dispensa CORS
  no fluxo padrão.
- HMR do Vite atravessa o nginx via WebSocket; `vite.config.ts` aponta
  `server.hmr.clientPort` para `APP_PORT` para o cliente conectar no nginx.
- Em prod, o estágio `prod` do `frontend/Dockerfile` serve `dist/` por
  nginx — topologia idêntica à de dev.

### Equivalente de três camadas no front

```
┌──────────────────────────────────────────────────────────────────────────┐
│  View (src/views/**/*.vue)                                               │
│                                                                          │
│  • Apenas template + handlers de UI                                      │
│  • Estados: loading (skeleton), error (alert), empty (CTA), ready        │
│  • Forms: validação local + mapeamento de 422 por campo                  │
│  • Confirmação em acoes destrutivas via AppModal (focus trap)            │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │ usa store
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  Store (src/stores/*.ts — Pinia)                                         │
│                                                                          │
│  • Estado canônico do recurso (items, current, filters, status)          │
│  • Acoes: fetch, loadOne, create, update, remove, cancel, addItem…       │
│  • Encapsula optimistic update e refetch em mutações sensíveis           │
│  • Não conhece axios — só chama o api client                             │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │ chama api/*
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  API client (src/api/*.ts)                                               │
│                                                                          │
│  • Função por endpoint (listContracts, createContract, cancelContract…)  │
│  • Tipos do retorno espelham o Resource (src/types/*)                    │
│  • Adiciona Idempotency-Key onde aplicável                               │
│  • Sem regra de negócio                                                  │
└─────────────────────────────────┬────────────────────────────────────────┘
                                  │ via http (singleton)
                                  ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  HTTP layer (src/lib/http.ts)                                            │
│                                                                          │
│  • Axios instance única com baseURL + headers                            │
│  • Request interceptor injeta Bearer token via tokenProvider             │
│  • Response interceptor converte AxiosError em ApiError tipado           │
│  • 401 dispara unauthorizedHandler (logout + redirect)                   │
└──────────────────────────────────────────────────────────────────────────┘
```

### Fluxo end-to-end — criar contrato pela SPA

1. **View** (`ContractCreateView.vue`) coleta cliente, datas e itens. Validação
   local impede submit incompleto.
2. **Store** (`useContractsStore.create`) chama `api.createContract(payload)`.
3. **API client** (`src/api/contracts.ts`) faz `POST /contracts` com header
   `Idempotency-Key` gerado por request (uuid).
4. **HTTP** anexa `Authorization: Bearer <token>` do `useAuthStore`.
5. **Backend** processa pelo pipeline normal (middleware → controller →
   service → repository → MySQL/Redis) e retorna o contrato hidratado.
6. **Store** insere no topo da lista, incrementa `meta.total`, e a view
   redireciona para `/contratos/:id`.
7. **Detail view** carrega via `loadOne`, mostra `total_calculado` (que vem do
   cache Redis do backend) e a timeline de histórico (`loadHistory`).

### Tratamento de erros tipado

`ApiError` (em `src/lib/http.ts`) carrega `status`, `errors` por campo (422),
`traceId` e mensagem. As views consomem com helpers:

- `ApiError.isValidation` (422) → renderiza erros inline por campo.
- `ApiError.isConflict` (409) → mostra mensagem de conflito de versão e
  recarrega o estado atual (caso do cancelamento de contrato).
- `ApiError.isUnauthorized` (401) → handler global desloga e redireciona.

### Design system

Tokens semânticos em CSS variables (`--color-surface`, `--color-ink`,
`--color-border`) consumidos pelo Tailwind via `rgb(var(--color-X) /
<alpha-value>)`. O seletor `.dark` no `<html>` troca os valores — toda a UI
adapta sem `dark:` espalhado nos componentes.

A escolha do tema (`system` / `light` / `dark`) fica em `localStorage` e é
aplicada por um script inline no `index.html` antes do bundle carregar, evitando
flash de tema errado.

### Acessibilidade

- Skip link no `AppShell` (visível ao focar).
- `:focus-visible` global com `ring-2 ring-brand-500 ring-offset-2`.
- Modais com focus trap (Tab/Shift+Tab) e restore focus on close.
- Sidebar mobile fecha com `Escape`.
- Labels sempre visíveis; obrigatoriedade indicada por `*` aria-hidden + texto
  sr-only.
- Breadcrumbs usam `aria-current="page"` na folha.
