# 0008 - Entrypoint unificado: nginx serve SPA e API na mesma origem

**Status**: Aceita
**Data**: 2026-05-17

## Contexto

A entrega da SPA Vue 3 + Vite trouxe, no `docker-compose.yml` original, **duas
portas expostas ao desenvolvedor**: `APP_PORT` (Laravel via nginx, default
`8000`) e `FRONTEND_PORT` (Vite dev server, default `5173`). Esse modelo "duas
portas" tem custos:

1. **CORS obrigatório.** Toda chamada do navegador para a API atravessa
   cross-origin, exige preflight `OPTIONS` em mutations e exige manutenção
   explícita de `FRONTEND_URL` / `FRONTEND_URL_ALT` em `config/cors.php`.
2. **Divergência dev × prod.** Em produção a SPA é buildada e servida por
   nginx junto da API (mesma origem). Em dev, separadas. Bugs ligados a
   origens, cookies de Sanctum e `Idempotency-Key` aparecem só fora do dev.
3. **URL absoluta em `VITE_API_BASE_URL`.** Forçar `http://localhost:8000/api/v1`
   em build de dev confunde quem sobe em outro host (devcontainer, túnel SSH,
   colega rodando em outra máquina).
4. **Onboarding mais ruidoso.** Dois links no README, duas portas para
   liberar no firewall, dois pontos de confusão.

Tudo isso é resolvido alinhando dev ao modelo de prod: **um nginx, uma porta,
mesma origem para SPA e API**.

## Decisão

O `nginx` é o **único** ponto de entrada exposto ao host (porta `APP_PORT`,
default `8000`) e roteia internamente:

```
/api/*      → fastcgi_pass app:9000   (Laravel PHP-FPM)
/sanctum/*  → fastcgi_pass app:9000   (Laravel PHP-FPM)
/health     → fastcgi_pass app:9000   (Laravel PHP-FPM)
/*          → proxy_pass http://frontend:5173  (Vite dev server, com
                                                upgrade de WebSocket p/ HMR)
```

Reflexos:

- `frontend` no `docker-compose.yml` **não** expõe porta para o host
  (`5173` continua existindo, mas só na rede docker).
- `VITE_API_BASE_URL` passa a ser `/api/v1` (path relativo, mesma origem).
- `FRONTEND_URL` no `.env.example` passa a ser `http://localhost:8000` (mesma
  origem). CORS continua configurado para o cenário "rodar Vite fora do
  nginx" (ex: `npm run dev` direto).
- `vite.config.ts` aponta `server.hmr.clientPort` para `APP_PORT` via
  `VITE_PUBLIC_PORT`, garantindo que o WebSocket do HMR conecte na porta
  pública do nginx, não em `5173`.
- `nginx` no compose passa a `depends_on: [app, frontend]` (em vez do
  inverso) — assim `make up` ordena o boot certo.
- `default.conf` usa `resolver 127.0.0.11` e atribui o upstream em variável
  (`set $vite frontend:5173`) para que o IP do container `frontend` seja
  re-resolvido a cada request, evitando 502 após `docker compose up` recriar
  o container.

## Consequências

**Positivas**

- **CORS some do caminho feliz** em dev — debugar a UI fica sem ruído de
  preflight, headers `Origin` e cookies cross-site.
- **Paridade dev × prod**: a topologia HTTP é a mesma em ambos. Bug que só
  aparece em prod por causa de origem deixa de existir.
- **Onboarding trivial**: um único link (`http://localhost:8000`) entrega
  SPA, API e health.
- **`VITE_API_BASE_URL` portável**: `/api/v1` funciona em qualquer host,
  túnel, devcontainer ou colega na rede.
- **Sanctum cookies (SPA mode)** ficam consistentes quando/​se forem
  ligados no futuro — mesma origem implica em first-party cookies sem
  `SameSite=None`.

**Negativas**

- **Acoplamento operacional**: derrubar o `frontend` derruba a SPA inteira;
  o nginx fica retornando 502 no path raiz. Mitigado pelo `resolver` interno
  e pelo `depends_on`.
- **HMR via WebSocket através do nginx** exige `proxy_http_version 1.1`,
  `Upgrade`/`Connection` headers e `proxy_read_timeout` longo. Configurado
  no `default.conf`. Se algum middleware (CDN, WAF) for adicionado no
  futuro, terá que preservar o upgrade.
- **`npm run dev` puro** (fora do compose) deixa de ser o caminho
  recomendado — ainda funciona, mas exige CORS configurado e ajustar
  `VITE_API_BASE_URL` para URL absoluta. Documentado no
  [`frontend/README.md`](../../frontend/README.md#pelo-node-local-fora-do-compose).

**Neutras**

- O container `frontend` continua existindo e rodando `vite dev` — só não é
  mais exposto ao host. Build de prod (estágio `prod` do
  `frontend/Dockerfile`) já era servido por nginx; agora dev também.

## Alternativas consideradas

- **Manter SPA em `5173` e API em `8000` com CORS** (estado anterior).
  Funciona, mas paga todos os custos listados em "Contexto". Não há ganho
  que justifique a divergência com prod.
- **Vite middleware embutido no Laravel** (`@vite` no Blade + Vite dev
  server proxy via `vite-plugin-laravel`). Cria acoplamento bizarro:
  Laravel passa a servir a SPA, mas a SPA é completamente independente do
  ciclo de request do Laravel. Pior dos dois mundos para uma SPA pura.
- **Proxy do Vite para o backend** (`server.proxy` no `vite.config.ts`).
  Resolve CORS em dev, mas mantém duas portas para o usuário e não traz
  paridade com prod (em prod não tem Vite).
- **Servir o `dist/` buildado em dev** (sem HMR). Resolve paridade total,
  mas perde HMR — fricção alta em desenvolvimento de UI.

## Quando reavaliar

- **Se a SPA passar a viver em um domínio dedicado** (`app.pactum.com`
  separado de `api.pactum.com`): CORS volta ao caminho feliz, mas em prod
  — e o overhead vira aceitável porque é configuração de DNS, não de dev.
- **Se for adicionada autenticação SPA mode do Sanctum** (cookies em vez de
  Bearer token): mesma origem se torna *requisito*, então essa ADR vira
  obrigação, não escolha.
- **Se o time de frontend crescer e quiser fluxo Node puro** sem Docker:
  documentar explicitamente o fluxo "Vite direto + CORS" como suportado
  (já está no `frontend/README.md`), mas manter o nginx como padrão.
