# Decisões técnicas

Sumário executivo das ADRs do projeto. Cada decisão tem um ADR completo em
[`docs/adr/`](adr/) com contexto, consequências e alternativas consideradas.

| ADR | Decisão | Alternativa rejeitada |
|-----|---------|----------------------|
| [0001](adr/0001-usar-repository-pattern-com-eloquent.md) | Repository Pattern com interface + implementação Eloquent | Services chamando Eloquent direto |
| [0002](adr/0002-config-driven-em-vez-de-strategy.md) | Regras de desconto em `config/contract.php` aplicadas por `DiscountCalculator` | Strategy Pattern com N classes |
| [0003](adr/0003-optimistic-locking-em-contratos.md) | Coluna `version` + `UPDATE WHERE version = :expected` retornando 409 em conflito | `SELECT FOR UPDATE` (pessimistic) |
| [0004](adr/0004-idempotency-key-em-post.md) | Middleware `IdempotencyKey` em POSTs sensíveis com cache Redis (24h) | Unique constraint só em banco |
| [0005](adr/0005-tabela-de-historico-em-vez-de-event-sourcing.md) | Tabela `contract_histories` com payload JSON + job idempotente | Event Sourcing puro |
| [0006](adr/0006-phpstan-nivel-8.md) | PHPStan nível 8 + Larastan | Nível 6 (mais permissivo) |

## Como propor nova decisão

1. Identifique se a mudança é estrutural. Refactor sem mudança de comportamento ou
   simples bugfix não pede ADR.
2. Copie a ADR mais recente, incremente o número (`NNNN`), preserve o template
   (`Status`, `Data`, `Contexto`, `Decisão`, `Consequências`, `Alternativas`,
   `Quando reavaliar`).
3. Abra um PR isolado contendo apenas a ADR — discussão e aprovação acontecem
   antes da implementação.
4. Quando uma decisão for substituída, marque a ADR original como
   `Status: Substituída por ADR-XXXX` e crie a nova ADR referenciando-a.
