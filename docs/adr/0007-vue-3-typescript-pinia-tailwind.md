# 0007 - Stack frontend: Vue 3 + TypeScript + Pinia + Tailwind

**Status**: Aceita
**Data**: 2026-05-16

## Contexto

O CLAUDE.md (seção 2) define Vue.js como framework do frontend, e a fase 9 do
plano entrega uma SPA que consome `/api/v1`. As decisões abaixo se desdobram
dessa escolha base:

1. **Vue 2 vs Vue 3.** Vue 2 sai de suporte oficial em 2023; novos projetos não
   têm justificativa para começar nele. Vue 3 traz Composition API,
   melhor tipagem, melhor tree-shaking.
2. **JavaScript vs TypeScript.** Sem tipagem, a paridade com o backend (PHPStan
   nível 8, ver [ADR-0006](0006-phpstan-nivel-8.md)) seria assimétrica: backend
   tipado, frontend não. TypeScript permite espelhar os Resources da API e
   detectar quebras de contrato em compile time.
3. **Estado: Vuex 4 vs Pinia.** Pinia é a recomendação oficial do core team
   desde Vue 3.2 e deprecou Vuex 4 efetivamente. Sintaxe mais leve, sem
   mutations boilerplate, suporte nativo a Composition API e TypeScript.
4. **CSS: Tailwind vs CSS modules / utility-first próprio / componentes
   pré-prontos (Vuetify/PrimeVue).** Componentes pré-prontos resolvem rápido
   mas amarram o design system ao fornecedor; Tailwind dá controle total com
   atrito baixo e funciona bem com tokens semânticos via CSS variables (chave
   para o dark mode da fase 9.8).
5. **Build: Vite vs Vue CLI/Webpack.** Vue CLI está em modo manutenção desde
   2022. Vite é o padrão recomendado, com HMR instantâneo e dev server leve.
6. **Roteamento: Vue Router 4** (padrão para Vue 3 — não há debate).
7. **HTTP: axios vs fetch.** Axios oferece interceptors estruturados (injeção
   de token, normalização de erro em `ApiError`) que com `fetch` exigiria
   reinventar.

## Decisão

Adotar a stack:

| Camada     | Escolha             | Versão  |
|------------|---------------------|---------|
| Framework  | Vue                 | 3.5     |
| Linguagem  | TypeScript          | 5.7     |
| Build      | Vite                | 6.x     |
| Estado     | Pinia               | 2.x     |
| Roteamento | Vue Router          | 4.x     |
| HTTP       | Axios               | 1.x     |
| Estilo     | Tailwind CSS        | 3.4     |
| Unit/comp. | Vitest + Vue Test Utils | latest |
| E2E        | Playwright (Chromium) | 1.60+ |

Camadas no frontend espelham o backend:
**View → Store (Pinia) → API client (axios)** — equivalente do
**Controller → Service → Repository** do backend. Detalhe em
[`docs/arquitetura.md`](../arquitetura.md#camada-spa-frontend).

## Consequências

**Positivas**
- Pinia store + api client mockável: testes de store rodam com
  `vi.mock('@/api/X')` sem precisar de servidor — paridade conceitual com
  testes de Service mockando Repository no backend.
- TypeScript captura quebras de contrato quando o Resource muda no backend
  (basta atualizar o tipo em `src/types/`).
- Tailwind + CSS variables permitiu adicionar dark mode na fase 9.8 sem tocar
  componentes — só tokens.
- Vite dev server inicia em ~300ms; HMR preserva estado.
- Vitest compartilha config com Vite, então a fronteira test ↔ runtime é
  mínima.

**Negativas**
- Pinia em store-de-função (`defineStore('x', () => { ... })`) exige disciplina
  para evitar dependências circulares entre stores. Não é o caso hoje, mas a
  fricção aparece com tamanho.
- Tailwind sem componentes prontos significa que cada item de UI vira código
  próprio. Mitigado por `src/components/ui/` e classes utilitárias
  (`pactum-card`, `pactum-alert-*`).
- Playwright baixa Chromium (~150MB) e exige backend rodando — overhead em CI.
  Documentado em [`frontend/README.md`](../../frontend/README.md#end-to-end-playwright).

**Neutras**
- TypeScript `strict: true` aumenta atrito inicial mas é alinhado com o gate de
  PHPStan nível 8 (ADR-0006). A simetria reduz surpresas em revisão.

## Alternativas consideradas

- **React + Next.js**: descartado por não cumprir o requisito explícito do
  CLAUDE.md de usar Vue. Mesmo se permitido, traria SSR não necessário no
  escopo (SPA sem requisito de SEO).
- **Nuxt** (Vue + SSR + file-based routing): descartado pelo mesmo motivo — a
  SPA não precisa de SSR, e Nuxt adiciona convenções que valem mais em apps
  com muitas páginas estáticas/marketing.
- **Vuetify / PrimeVue**: descartado por amarrar o design system a um
  fornecedor e dificultar dark mode com tokens próprios.
- **Componentes em JavaScript puro**: descartado por quebrar a paridade de
  tipagem com o backend (PHPStan nível 8). Quanto antes uma quebra de contrato
  for detectada, melhor.

## Quando reavaliar

- **Próximo major do Vue (4.x)** quando sair: avaliar caminho de migração.
- **Crescimento do app além de ~50 telas**: considerar Nuxt para file-based
  routing e split por feature module.
- **Necessidade de SEO em páginas públicas** (landing, blog): reavaliar SSR.
- **Performance de bundle além de 200KB gzip**: avaliar code-splitting mais
  agressivo e/ou migrar telas pesadas para islands.
