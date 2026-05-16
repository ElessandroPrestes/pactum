# Pactum — Frontend (SPA Vue 3)

SPA do Pactum: consome a API REST `/api/v1` do backend Laravel via token Sanctum,
com foco em design system consistente, acessibilidade (WCAG AA), feedback de
loading/erro e testes.

## Stack

| Camada               | Tecnologia          | Versão  |
|----------------------|---------------------|---------|
| Framework            | Vue                 | 3.5     |
| Linguagem            | TypeScript          | 5.7     |
| Build                | Vite                | 6.x     |
| Estado               | Pinia               | 2.x     |
| Roteamento           | Vue Router          | 4.x     |
| HTTP                 | Axios               | 1.x     |
| Estilo               | Tailwind CSS        | 3.4     |
| Testes unitários     | Vitest + Vue Test Utils | latest |
| E2E                  | Playwright (Chromium) | 1.60+ |
| Lint / format        | ESLint 9 + Prettier 3 | —     |

A justificativa da stack está em
[`docs/adr/0007-vue-3-typescript-pinia-tailwind.md`](../docs/adr/0007-vue-3-typescript-pinia-tailwind.md).

## Estrutura

```
src/
├── api/                ← clientes HTTP por recurso (axios)
├── components/
│   ├── layout/         ← AppShell, AppHeader, AppSidebar, AppBreadcrumbs, ThemeToggle
│   └── ui/             ← AppButton, AppInput, AppSelect, AppModal, AppSkeleton, etc.
├── config/             ← navegação primária
├── lib/                ← cliente axios + ApiError tipado
├── router/             ← Vue Router com guards de auth
├── stores/             ← Pinia (auth, clients, services, contracts, toast, theme)
├── styles/             ← main.css (tokens CSS + componentes utilitários)
├── types/              ← tipos de domínio espelhando os Resources da API
├── utils/              ← formatação de moeda, documento, idempotency key
└── views/              ← rotas (cada feature em sua pasta)

tests/e2e/              ← specs Playwright contra backend real
```

## Pré-requisitos

- [Node.js](https://nodejs.org/) 20+ (ou rodar pelo container `frontend` do compose)
- API backend rodando em `http://localhost:8000` — `make up` no diretório raiz

## Desenvolvimento

### Pelo Docker (recomendado)

```bash
# Na raiz do projeto
make up               # sobe app + nginx + mysql + redis + queue + frontend
make front-shell      # shell no container frontend, se precisar
```

O serviço `frontend` no compose já executa `npm run dev` em
`http://localhost:5173` com hot-reload.

### Pelo Node local

```bash
npm install
npm run dev           # http://localhost:5173
```

Confirme que `VITE_API_BASE_URL` (em `.env`) aponta para a API:

```
VITE_API_BASE_URL=http://localhost:8000/api/v1
VITE_APP_NAME=Pactum
```

## Scripts

| Comando                  | O que faz                                          |
|--------------------------|----------------------------------------------------|
| `npm run dev`            | Servidor de desenvolvimento Vite com HMR           |
| `npm run build`          | Type-check (vue-tsc) + build de produção           |
| `npm run preview`        | Serve o `dist/` para sanity-check do build         |
| `npm run type-check`     | Apenas `vue-tsc --noEmit`                          |
| `npm run lint`           | ESLint com `--max-warnings=0`                      |
| `npm run lint:fix`       | ESLint com auto-fix                                |
| `npm run format`         | Prettier escreve correções                         |
| `npm run format:check`   | Prettier valida (sem escrever)                     |
| `npm run test`           | Vitest em modo único (CI)                          |
| `npm run test:watch`     | Vitest em watch                                    |
| `npm run test:e2e`       | Playwright (precisa de backend rodando)            |
| `npm run test:e2e:install` | Baixa o Chromium do Playwright (~150MB)          |

## Testes

### Unitários e de componente (Vitest)

```bash
npm run test
```

Cobre stores Pinia (mockando `@/api/*`), componentes (AppInput, AppButton,
AppBreadcrumbs) e views chave (formulários e fluxos críticos). Roda em
`happy-dom`, sem browser real, na casa dos segundos.

### End-to-end (Playwright)

```bash
# Uma vez, para baixar o Chromium
npm run test:e2e:install

# A cada execução
make up                      # backend precisa estar de pé
make migrate seed            # se ainda nao migrou
npm run test:e2e
```

A spec `tests/e2e/contrato.spec.ts` cobre o fluxo crítico:
**login → criar contrato → abrir detalhe → cancelar**.

Credenciais usadas (`DevTokenSeeder`):

- email: `dev@pactum.local`
- senha: `change-me-in-dev`

Variáveis para sobrescrever:

```
E2E_EMAIL=outro@pactum.local
E2E_PASSWORD=outra-senha
PLAYWRIGHT_BASE_URL=http://localhost:5173
```

## Build de produção

```bash
npm run build         # gera dist/ com type-check + bundle Vite
npm run preview       # serve dist/ em http://localhost:4173
```

O `Dockerfile` faz build multi-stage e serve o `dist/` via Nginx no estágio
final.

## Convenções

- **Design tokens** ficam em `tailwind.config.ts` + variáveis CSS em
  `src/styles/main.css`. Componentes consomem via classes semânticas
  (`bg-surface`, `text-ink`, `border-border`) — dark mode adapta automaticamente.
- **Estados de tela** sempre cobertos: `loading` (skeleton), `error` (alerta com
  retry), `empty` (call-to-action) e `ready`. Nenhuma tela é vazia em loading.
- **Forms**: label sempre visível, validação inline, erros 422 da API mapeados
  por campo via `ApiError.firstError(campo)`.
- **Confirmação para acoes destrutivas** (delete cliente, delete serviço,
  cancelar contrato) usa `AppModal` com focus trap.
- **Acessibilidade**: HTML semântico, ARIA roles, navegação por teclado,
  focus-visible global, contraste WCAG AA, skip link no shell.

## Dark mode

Toggle no header alterna **system → light → dark → system**. A escolha é
persistida em `localStorage` (`pactum:theme`). Um script inline no `index.html`
aplica a classe `dark` no `<html>` antes do bundle carregar — evita FOUC.

Componentes usam tokens semânticos (`bg-surface`, `text-ink`, …) cujos valores
mudam via CSS variables no seletor `.dark`. Adicionar dark mode a um componente
novo é, na maioria dos casos, usar os tokens — sem `dark:` espalhado.

## Troubleshooting

- **`401` em todas as requests**: token expirado ou backend reiniciado sem
  re-seed. Faça login novamente.
- **`CORS` no console**: o backend libera `http://localhost:5173` por padrão.
  Se o frontend está em outra porta, ajustar `config/cors.php` no backend.
- **`409` ao salvar contrato**: outra sessão alterou o contrato — a UI mostra
  mensagem específica e recarrega a versão atual.
- **Playwright "browser not found"**: rodar `npm run test:e2e:install`.
- **Hot reload não pega mudanças no container**: garantir que o volume
  `frontend:/app` no `docker-compose.yml` está montado e que o `vite.config.ts`
  tem `server.host: '0.0.0.0'`.
