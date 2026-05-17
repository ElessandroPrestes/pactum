# 0004 - Idempotency-Key em POSTs sensíveis

**Status**: Aceita
**Data**: 2026-05-17

## Contexto

Requests POST sob latência alta ou retentadas por proxies/clientes geram duplicação
silenciosa em endpoints que criam recursos. Em fluxos financeiros ou que disparam
side-effects (job de histórico, cache, notificação), a duplicação não é só barulho —
é incidente.

A solução de mercado é exigir `Idempotency-Key` (RFC draft do IETF) e armazenar a
resposta da primeira execução para devolvê-la em retries com a mesma chave.

## Decisão

Middleware `IdempotencyKey` aplicado em POST de `/clients`, `/contracts` e
`/contracts/{contract}/items` (não em `cancel` — esse já é naturalmente idempotente
via `version`).

Regras:

- Header `Idempotency-Key` obrigatório. Ausência → 400.
- Formato validado: `^[A-Za-z0-9_-]+$`, máximo 128 caracteres. Inválido → 400.
- Chave + path + identificador do ator (`user_id` ou IP) compõem a chave do cache
  Redis. Hash SHA-256 disso evita colisão e mantém tamanho fixo.
- Primeira execução: o middleware deixa passar, captura status/body/headers
  relevantes (`content-type`, `location`) e guarda em Redis por 24h.
- Repetição com **mesmo body**: devolve a resposta original (mesmos status, body,
  headers). Sem reprocessamento.
- Repetição com **body diferente**: 409 + mensagem explícita. Cliente provavelmente
  reaproveitou a chave por engano — sinalizar é mais seguro que silenciosamente
  criar recurso novo.

## Consequências

**Positivas**
- Cliente pode retentar POSTs com segurança sem duplicar recursos.
- Side-effects (histórico, cache) não disparam duas vezes.
- 24h de janela cobre praticamente qualquer cenário de retry razoável.

**Negativas**
- POSTs ficam dependentes de Redis para correção. Falha do Redis derruba o caminho
  de criação. Mitigação: o middleware responde 500 com mensagem clara — não cria o
  recurso "no escuro".
- Requer disciplina do cliente: gerar UUID novo por intenção de criação, persistir
  para o caso de retry. SDKs costumam abstrair isso.

**Neutras**
- O middleware guarda apenas alguns headers da resposta original (`content-type`,
  `location`). Headers gerados por outros middlewares (`X-Trace-Id`, security
  headers) são reaplicados na resposta atual, não congelados na primeira chamada.

## Alternativas consideradas

- **Unique constraint no banco** (ex.: `contract_id + service_id` em items):
  funciona para alguns casos mas não para `clients` (documento já é unique, mas o
  cliente pode mudar campos não-unique entre tentativas).
- **Idempotência só na queue**: insuficiente, o impacto é antes de chegar na queue.
- **Janela curta (5min)**: descartada — retries de fila/job podem demorar muito mais.

## Quando reavaliar

Se aparecer pressão por janela maior que 24h (ex.: workflows assíncronos com SLA de
dias), considerar persistir o registro de idempotência em banco com índice próprio.
