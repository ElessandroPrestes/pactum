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

Isso constrói a imagem e sobe cinco serviços: `app` (PHP-FPM), `nginx`, `mysql`,
`redis` e `queue` (worker). Na primeira execução o MySQL leva ~2 minutos para
inicializar — o `start_period` do healthcheck cobre essa janela.

### 4. Gere a chave da aplicação e rode as migrations

```bash
make shell
php artisan key:generate
exit

make migrate
```

### 5. Acesse a aplicação

A API fica disponível em **http://localhost:8000**.

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

> Os alvos de qualidade (`pint`, `analyse`, `infection`) dependem de ferramentas
> que são adicionadas ao projeto nas fases seguintes do plano de execução.

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

As decisões estruturais e seus trade-offs serão documentados como ADRs no diretório
`docs/adr/`.
