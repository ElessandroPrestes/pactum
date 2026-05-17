# 0003 - Optimistic Locking em Contratos

**Status**: Aceita
**Data**: 2026-05-17

## Contexto

Contrato é um agregado mutável com regras de invariante (não aceita item se cancelado,
total recalculado a cada mudança, histórico de auditoria). Em ambiente concorrente —
dois operadores editando o mesmo contrato, ou um job batch rodando junto com edição
manual — atualizações sobrepostas podem corromper o estado.

Pessimistic locking via `SELECT ... FOR UPDATE` resolve, mas custa: a row fica
bloqueada por toda a transação, serializa edições, complica timeout e adiciona risco
de deadlock entre item-cascade e contrato.

## Decisão

Usar Optimistic Locking com coluna `version` (`unsignedInteger`, default 1) em
`contracts`. Toda mutação que muda estado do contrato — update, cancel — passa por
`updateWithVersion(Contract $contract, array $dados, int $expectedVersion)` no
repositório, que executa:

```sql
UPDATE contracts
   SET ..., version = :expected_version + 1
 WHERE id = :id
   AND version = :expected_version
   AND deleted_at IS NULL
```

Se `rows affected = 0`, o repositório lança `ConcurrencyConflictException` (HTTP 409).
O cliente da API precisa enviar a `version` corrente em PUT e POST de cancel.

## Consequências

**Positivas**
- Sem bloqueio explícito no banco — escrita atômica via constraint na cláusula WHERE.
- Resposta HTTP 409 dá ao cliente sinal claro para recarregar e tentar de novo.
- Funciona bem com retries em camada de cliente (mobile/web reabre o registro).

**Negativas**
- `DB::update()` ignora eventos do Eloquent — observers `updated` não disparam
  automaticamente para esse caminho.
- Mitigação: invalidação de cache e dispatch de job de histórico são feitos
  **explicitamente** dentro do método `updateWithVersion()` do repositório.

**Neutras**
- A coluna `version` é exposta na API (resource expõe `version` no contrato). Faz
  parte do contrato público: cliente lê e devolve.

## Alternativas consideradas

- **Pessimistic locking (`SELECT FOR UPDATE`)**: descartado pela serialização forte
  e pelo risco de deadlock em fluxos com itens em cascata.
- **Last-write-wins** (sobrescrever silenciosamente): descartado por corromper
  histórico e total cacheado sem aviso ao operador.
- **MVCC/CRDT em camada de aplicação**: over-engineering para o escopo.

## Quando reavaliar

Se aparecerem fluxos onde 409 é frequente e o cliente não consegue reconciliar
sozinho (ex.: editor colaborativo), considerar event sourcing ou OT/CRDT.
