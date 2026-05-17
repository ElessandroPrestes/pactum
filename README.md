# Pactum

[![CI](https://github.com/ElessandroPrestes/pactum/actions/workflows/ci.yml/badge.svg?branch=develop)](https://github.com/ElessandroPrestes/pactum/actions/workflows/ci.yml)
[![Cobertura](https://img.shields.io/badge/cobertura-%E2%89%A590%25-brightgreen)](https://github.com/ElessandroPrestes/pactum/actions/workflows/ci.yml)
[![MSI](https://img.shields.io/badge/MSI-%E2%89%A570%25-brightgreen)](https://github.com/ElessandroPrestes/pactum/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PHPStan](https://img.shields.io/badge/PHPStan-n%C3%ADvel%208-1abc9c)](https://phpstan.org)

ERP simplificado de **contratos e serviços recorrentes mensais**. O sistema gerencia
clientes, serviços ofertados, contratos que vinculam os dois e itens dentro de cada
contrato, com cálculo dinâmico do valor total mensal e regras de desconto extensíveis.

A API expõe operações REST sob o prefixo `/api/v1` e segue uma arquitetura em três
camadas — **Controller → Service → Repository** — com foco em separação de
responsabilidades, regras de negócio extensíveis e qualidade de testes.

---

## Funcionalidades

- CRUD de **clientes** com validação de CPF/CNPJ (dígitos verificadores próprios).
- CRUD de **serviços** ofertados.
- **Contratos** vinculando cliente e serviços, com itens, cálculo de total e
  histórico de auditoria.
- **Regras de desconto** configuráveis via `config/contract.php` (progressivo por
  quantidade e por fidelidade).
- **Optimistic locking** em contratos, **idempotência** em POST e **cache** do total
  com invalidação explícita.
- Logs estruturados em JSON com `trace_id` e **mascaramento de dados sensíveis** (LGPD).

---

## Tecnologias

| Camada            | Tecnologia          | Versão   |
|-------------------|---------------------|----------|
| Linguagem         | PHP                 | 8.3      |
| Framework         | Laravel             | 11.x     |
| Banco de dados    | MySQL               | 8.0      |
| Cache / Queue     | Redis               | 7.x      |
| Servidor web      | Nginx               | 1.27     |
| Autenticação      | Laravel Sanctum     | —        |
| Testes            | Pest                | 3.x      |
| Mutation testing  | Infection           | 0.29+    |
| Code style        | Laravel Pint        | —        |
| Análise estática  | Larastan (PHPStan)  | nível 8  |
| Containerização   | Docker + Compose    | —        |
| CI                | GitHub Actions      | —        |

---

## Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) 24+
- [Docker Compose](https://docs.docker.com/compose/) v2

Não é necessário ter PHP, Composer ou MySQL instalados na máquina host — tudo roda
em containers.

---

## Subindo o ambiente de desenvolvimento

### 1. Clone o repositório

```bash
git clone git@github.com:ElessandroPrestes/pactum.git
cd pactum
```

### 2. Configure as variáveis de ambiente

```bash
cp .env.example .env
```

O `.env.example` já vem apontando para os serviços do Docker (`DB_HOST=mysql`,
`REDIS_HOST=redis`). Ajuste `HOST_UID` / `HOST_GID` caso o seu usuário não seja
`1000` (descubra com `id -u` e `id -g`).

### 3. Suba os containers

```bash
make up
```

Isso constrói a imagem e sobe seis serviços: `app` (PHP-FPM), `nginx`, `mysql`,
`redis`, `queue` (worker) e `frontend` (Vite). Na primeira execução o MySQL leva
~2 minutos para inicializar — o `start_period` do healthcheck cobre essa janela.

O `nginx` é o único ponto de entrada exposto (porta `APP_PORT`, default `8000`)
e roteia internamente: `/api/*`, `/sanctum/*` e `/health` vão para o Laravel
(PHP-FPM), e qualquer outro path é proxy_pass para o Vite dev server da SPA
(com upgrade de WebSocket para HMR). Decisão registrada na
[ADR-0008](docs/adr/0008-entrypoint-unificado-via-nginx.md).

### 4. Gere a chave da aplicação, rode migrations e seeds

```bash
make shell
php artisan key:generate
exit

make migrate
make seed         # cria usuario de dev e dados de exemplo
```

O `DevTokenSeeder` cria o usuário de testes e imprime as credenciais (email,
senha e token Sanctum) no terminal. As mesmas credenciais ficam descritas em
[Credenciais de desenvolvimento](#credenciais-de-desenvolvimento) abaixo.

### 5. Acesse a aplicação

A SPA Vue e a API ficam disponíveis em **http://localhost:8000** (mesma origem):

- **http://localhost:8000/** — SPA Vue 3 (faça login com as credenciais abaixo).
- **http://localhost:8000/api/v1** — API REST.
- **http://localhost:8000/api/documentation** — Swagger UI (OpenAPI 3.0).
- **http://localhost:8000/health** — health check (MySQL + Redis).

### Credenciais de desenvolvimento

O `DevTokenSeeder` (registrado no `DatabaseSeeder` e executado em
`make seed` / `make fresh`) cria um usuário fixo para login na SPA e um
token Sanctum para chamadas via curl. **Só roda em `local`, `development` e
`testing`** — em produção é no-op.

| Campo    | Valor                |
|----------|----------------------|
| Email    | `dev@pactum.local`   |
| Senha    | `change-me-in-dev`   |
| Nome     | `Dev Pactum`         |
| Token    | impresso em cada run de `db:seed --class=DevTokenSeeder` (é regenerado a cada execução) |

Para (re)imprimir o token sem recriar dados:

```bash
make shell
php artisan db:seed --class=DevTokenSeeder
```

Para usar nas chamadas curl da seção [Exemplos com curl](#exemplos-com-curl):

```bash
export PACTUM_TOKEN=<token impresso pelo seeder>
```

---

## Comandos disponíveis (`Makefile`)

| Comando             | Descrição                                              |
|---------------------|--------------------------------------------------------|
| `make up`           | Sobe os containers em segundo plano                    |
| `make down`         | Derruba os containers                                  |
| `make build`        | Reconstrói as imagens                                  |
| `make shell`        | Abre um shell no container `app`                       |
| `make logs`         | Acompanha os logs de todos os serviços                 |
| `make migrate`      | Executa as migrations                                  |
| `make seed`         | Roda os seeders                                        |
| `make fresh`        | Recria o banco e roda os seeders                       |
| `make test`         | Executa a suíte de testes                              |
| `make test-coverage`| Executa os testes com cobertura mínima de 90%          |
| `make infection`    | Executa o mutation testing                             |
| `make pint`         | Aplica o code style (Laravel Pint)                     |
| `make analyse`      | Roda a análise estática (PHPStan nível 8)              |
| `make check`        | Roda `pint`, `analyse` e `test` em sequência           |
| `make swagger`      | Regenera a documentação OpenAPI (`storage/api-docs/`)  |

---

## Estrutura do projeto

```
app/
├── Http/
│   ├── Controllers/Api/V1/   # Controllers da API
│   ├── Requests/             # Validação de entrada (Form Requests)
│   ├── Resources/            # Serialização de saída (API Resources)
│   └── Middleware/           # Trace ID, mascaramento LGPD, idempotência
├── Models/                   # Models Eloquent
├── Repositories/
│   ├── Contracts/            # Interfaces dos repositórios
│   └── Eloquent/             # Implementações Eloquent
├── Services/                 # Regras de negócio
├── Enums/                    # Enums tipados
├── Rules/                    # Regras de validação customizadas
├── Observers/                # Hooks de Model (histórico, cache)
├── Policies/                 # Autorização
├── Jobs/                     # Processamento assíncrono
└── Exceptions/               # Exceções de domínio

docker/
├── nginx/                    # Configuração do Nginx
└── php/                      # Configuração de OPcache e preload
```

---

## Arquitetura

```
Controller (HTTP)  →  valida com FormRequest, chama Service, retorna API Resource
      │
Service (negócio)  →  orquestra fluxo, aplica invariantes, gerencia transação e cache
      │
Repository (dados) →  interface + implementação Eloquent, centraliza eager loading
```

Detalhes em [docs/arquitetura.md](docs/arquitetura.md) (diagrama de camadas e
fluxo passo a passo de uma request).

---

## Endpoints

Todas as rotas vivem sob `/api/v1` e exigem **Bearer token Sanctum**
(`Authorization: Bearer <token>`). POSTs em `/clients`, `/contracts` e
`/contracts/{id}/items` exigem header `Idempotency-Key`.

### Documentação interativa (Swagger / OpenAPI 3.0)

Toda a API está documentada via anotações OpenAPI (`darkaonline/l5-swagger`).
Em desenvolvimento (`L5_SWAGGER_GENERATE_ALWAYS=true` no `.env.example`),
a doc é regenerada a cada acesso à UI.

| Recurso                        | URL                                                  |
|--------------------------------|------------------------------------------------------|
| Swagger UI                     | http://localhost:8000/api/documentation              |
| OpenAPI 3.0 JSON (raw)         | http://localhost:8000/api/docs                       |
| JSON em disco (artefato)       | `storage/api-docs/api-docs.json` (ignorado pelo git) |

Para regenerar manualmente após editar anotações:

```bash
make swagger
# ou:  docker compose exec app php artisan l5-swagger:generate
```

Para testar endpoints autenticados pela UI:

1. Faça `POST /auth/login` (botão **Try it out**) com as credenciais de dev.
2. Copie o `token` da resposta.
3. Clique em **Authorize** (canto superior direito) e cole o token.
4. As próximas chamadas vão automaticamente com `Authorization: Bearer <token>`.

### Health (sem auth)

| Método | Rota       | Descrição |
|--------|------------|-----------|
| GET    | `/health`  | Liveness + readiness (MySQL + Redis). 503 se algum falhar. |

### Clientes

| Método | Rota                  | Notas |
|--------|-----------------------|-------|
| GET    | `/api/v1/clients`     | Paginado. Filtros: `status`, `documento`, `nome`. |
| POST   | `/api/v1/clients`     | Idempotente. Cria sempre com `status=ativo`. |
| GET    | `/api/v1/clients/{client}` | — |
| PUT    | `/api/v1/clients/{client}` | — |
| DELETE | `/api/v1/clients/{client}` | Soft delete. |

### Serviços

| Método | Rota                     | Notas |
|--------|--------------------------|-------|
| GET    | `/api/v1/services`       | Paginado. Filtros: `nome`, `ativo`. Cacheado por tag. |
| POST   | `/api/v1/services`       | Cria sempre com `ativo=true`. |
| GET    | `/api/v1/services/{service}` | — |
| PUT    | `/api/v1/services/{service}` | — |
| DELETE | `/api/v1/services/{service}` | Soft delete. |

### Contratos

| Método | Rota                                                  | Notas |
|--------|-------------------------------------------------------|-------|
| GET    | `/api/v1/contracts`                                   | Paginado. Filtros: `client_id`, `status`, `data_inicio`. |
| POST   | `/api/v1/contracts`                                   | Idempotente. Transacional (contrato + itens). |
| GET    | `/api/v1/contracts/{contract}`                        | Inclui `itens` e `total_calculado`. |
| PUT    | `/api/v1/contracts/{contract}`                        | Exige `version` no payload. 409 em conflito. |
| DELETE | `/api/v1/contracts/{contract}`                        | Soft delete. |
| POST   | `/api/v1/contracts/{contract}/cancel`                 | Exige `version`. 409 em conflito. |
| POST   | `/api/v1/contracts/{contract}/items`                  | Idempotente. Invalida cache do total. |
| DELETE | `/api/v1/contracts/{contract}/items/{item}`           | Invalida cache do total. |
| GET    | `/api/v1/contracts/{contract}/history`                | Histórico paginado (mais recentes primeiro). |

---

## Exemplos com curl

Exporte o token gerado pelo `DevTokenSeeder` (ou crie um via tinker):

```bash
export PACTUM_TOKEN=<token>
```

### Criar um cliente

```bash
curl -X POST http://localhost:8000/api/v1/clients \
  -H "Authorization: Bearer $PACTUM_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "nome": "Acme Ltda",
    "documento": "11.222.333/0001-81",
    "tipo_documento": "cnpj",
    "email": "contato@acme.com"
  }'
```

### Criar um contrato com itens (atômico)

```bash
curl -X POST http://localhost:8000/api/v1/contracts \
  -H "Authorization: Bearer $PACTUM_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{
    "client_id": 1,
    "data_inicio": "2026-01-01",
    "data_fim": "2027-01-01",
    "itens": [
      { "service_id": 1, "quantidade": 5, "valor_unitario": 200.00 }
    ]
  }'
```

### Atualizar um contrato (optimistic lock)

A `version` corrente vem na resposta de GET. Em conflito o servidor responde 409.

```bash
curl -X PUT http://localhost:8000/api/v1/contracts/1 \
  -H "Authorization: Bearer $PACTUM_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{ "version": 1, "data_fim": "2027-06-30" }'
```

### Adicionar item a um contrato existente

```bash
curl -X POST http://localhost:8000/api/v1/contracts/1/items \
  -H "Authorization: Bearer $PACTUM_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{ "service_id": 2, "quantidade": 1, "valor_unitario": 150.00 }'
```

### Cancelar um contrato

```bash
curl -X POST http://localhost:8000/api/v1/contracts/1/cancel \
  -H "Authorization: Bearer $PACTUM_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{ "version": 2 }'
```

### Consultar histórico

```bash
curl http://localhost:8000/api/v1/contracts/1/history \
  -H "Authorization: Bearer $PACTUM_TOKEN" \
  -H "Accept: application/json"
```

---

## Troubleshooting

### `make up` trava no MySQL ou retorna conexão recusada

O healthcheck do MySQL tem `start_period` de ~2 minutos na primeira execução. O
`app` aguarda o MySQL ficar saudável antes de subir. Acompanhe com `make logs` —
você verá `[MY-010931] [Server] /usr/sbin/mysqld: ready for connections.` quando
estiver pronto.

### Erro 401 em todas as rotas `/api/v1/*`

Falta o header `Authorization: Bearer <token>`. Gere um token:

```bash
make shell
php artisan db:seed --class=DevTokenSeeder
```

O seeder imprime o token no console — exporte como `PACTUM_TOKEN`.

### Erro 400 com `Header Idempotency-Key e obrigatorio`

POST de `/clients`, `/contracts` e `/contracts/{id}/items` exigem
`Idempotency-Key`. Gere um UUID por intenção de criação e reutilize **só** em
retries da mesma operação:

```bash
-H "Idempotency-Key: $(uuidgen)"
```

### Erro 409 ao atualizar contrato

Conflito de versão (optimistic lock). Releia o contrato (`GET /contracts/{id}`),
pegue a `version` corrente e tente de novo.

### Erro 422 com `Contrato cancelado nao pode ser editado`

Invariante de domínio. Contrato cancelado não aceita mutação. Verifique o
`status` antes de tentar.

### Cobertura de teste local não funciona

O container `app` usa Xdebug. Force o modo `coverage`:

```bash
docker compose exec -e XDEBUG_MODE=coverage app php artisan test --coverage
```

### Limpar cache do Redis manualmente

```bash
make shell
php artisan cache:clear
```

Útil quando se mexe diretamente em `contracts` por SQL e o total fica "preso".

---

## Documentação adicional

- [docs/arquitetura.md](docs/arquitetura.md) — camadas, fluxo de request, cache e auditoria.
- [docs/regras-de-negocio.md](docs/regras-de-negocio.md) — cálculo do total, regras de desconto, invariantes, exemplos numéricos.
- [docs/como-adicionar-regra-de-desconto.md](docs/como-adicionar-regra-de-desconto.md) — guia passo a passo para estender o cálculo.
- [docs/decisoes-tecnicas.md](docs/decisoes-tecnicas.md) — sumário das ADRs.
- [docs/adr/](docs/adr/) — ADRs individuais com contexto, decisão, consequências e alternativas.
- [docs/melhorias-futuras.md](docs/melhorias-futuras.md) — backlog técnico priorizado.
