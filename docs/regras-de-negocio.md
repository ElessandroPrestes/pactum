# Regras de negócio

Este documento descreve as regras que governam o cálculo do total de um contrato,
as invariantes que protegem a integridade do agregado e exemplos numéricos para
cada caminho.

## Cálculo do total

O total de um contrato é calculado por `ContractService::calculateTotal(Contract)`
em três passos:

1. **Subtotal** = `Σ (item.quantidade × item.valor_unitario)` para todos os itens.
   Operação feita em BCMath com 6 casas decimais internas, nunca em `float`.
2. **`DiscountCalculator::apply($contract, $subtotal)`** itera as regras ativas em
   `config/contract.php` e aplica cada uma compondo sobre o resultado da anterior.
3. **Resultado** é arredondado para 2 casas decimais e devolvido como string
   (`'1234.56'`).

O total fica em cache em `contract:{id}:total` (Redis, TTL 1h). Mutações invalidam
o cache explicitamente — TTL é apenas fallback.

## Regras de desconto

Definidas em `config/contract.php` sob a chave `descontos`. Cada regra tem um
campo `ativo` que liga/desliga sem mexer em código.

### 1. Desconto progressivo por quantidade

```php
'quantidade_progressivo' => [
    'ativo' => true,
    'faixas' => [
        ['min_itens' => 5,  'percentual' => 5.0],
        ['min_itens' => 10, 'percentual' => 10.0],
    ],
],
```

- Soma `quantidade` de todos os itens do contrato.
- Aplica o `percentual` da **maior faixa elegível** (não cumula entre faixas).
- Abaixo da menor faixa, não aplica desconto.

### 2. Desconto por fidelidade

```php
'fidelidade' => [
    'ativo' => true,
    'meses_minimos' => 12,
    'percentual' => 7.0,
],
```

- Calcula a diferença em meses entre `data_inicio` e `data_fim` (ou `today()` se
  `data_fim` for nulo).
- Se diferença `>= meses_minimos`, aplica `percentual`.

### Ordem de composição

As regras são aplicadas na ordem da config: **primeiro progressivo, depois
fidelidade**. A fidelidade incide sobre o valor já reduzido pela primeira regra,
não sobre o subtotal cru. Mudar a ordem na config muda o total final.

## Exemplos numéricos

### Caso 1 — Contrato sem desconto

- 2 itens, ambos `quantidade=1`, `valor_unitario=R$ 200,00`.
- Contrato de 6 meses.

```
subtotal              = 2 × 200,00            = 400,00
progressivo (Σ=2 < 5) =                       sem desconto
fidelidade (6 < 12)   =                       sem desconto
total                                          R$ 400,00
```

### Caso 2 — Só desconto progressivo (faixa de 5%)

- 1 item, `quantidade=5`, `valor_unitario=R$ 200,00`.
- Contrato de 6 meses.

```
subtotal              = 5 × 200,00            = 1000,00
progressivo (Σ=5)     = 1000,00 × 0,95        = 950,00
fidelidade (6 < 12)   =                       sem desconto
total                                          R$ 950,00
```

### Caso 3 — Só desconto de fidelidade

- 2 itens, ambos `quantidade=1`, `valor_unitario=R$ 500,00`.
- Contrato de `2026-01-01` a `2027-01-01` (12 meses).

```
subtotal              = 2 × 500,00            = 1000,00
progressivo (Σ=2 < 5) =                       sem desconto
fidelidade (12 = 12)  = 1000,00 × 0,93        = 930,00
total                                          R$ 930,00
```

### Caso 4 — Progressivo + fidelidade compostos

- 1 item, `quantidade=5`, `valor_unitario=R$ 200,00`.
- Contrato de `2026-01-01` a `2027-01-01` (12 meses).

```
subtotal              = 5 × 200,00            = 1000,00
progressivo (Σ=5)     = 1000,00 × 0,95        =  950,00
fidelidade (12 = 12)  =  950,00 × 0,93        =  883,50
total                                          R$ 883,50
```

### Caso 5 — Maior faixa progressiva (10%)

- 1 item, `quantidade=12`, `valor_unitario=R$ 100,00`.
- Contrato de 6 meses.

```
subtotal              = 12 × 100,00           = 1200,00
progressivo (Σ=12≥10) = 1200,00 × 0,90        = 1080,00
fidelidade (6 < 12)   =                       sem desconto
total                                          R$ 1080,00
```

### Caso 6 — Contrato sem `data_fim`

A duração é calculada usando `today()` como fim.

- 1 item, `quantidade=1`, `valor_unitario=R$ 100,00`.
- `data_inicio = 2026-01-01`, `data_fim = null`, consulta feita em `2027-02-01`.

```
subtotal              = 1 × 100,00            =  100,00
progressivo (Σ=1 < 5) =                       sem desconto
fidelidade (13 >= 12) =  100,00 × 0,93        =   93,00
total                                          R$ 93,00
```

## Invariantes do contrato

Aplicadas em `ContractService` antes da mutação, lançando exceção 422 se violadas.

| Invariante | Onde | Exceção / status |
|------------|------|------------------|
| Contrato cancelado não aceita mutação (update, cancel, addItem, removeItem) | `ContractService::garantirEditavel` | `ContractCannotBeEditedException` → 422 |
| Cliente inativo não pode receber novo contrato | `ContractService::create` | `InactiveClientException` → 422 |
| `data_fim >= data_inicio` | `StoreContractRequest`, `UpdateContractRequest` | `ValidationException` → 422 |
| `quantidade >= 1` em itens | `StoreContractItemRequest` | `ValidationException` → 422 |
| Versão divergente em update/cancel | `EloquentContractRepository::updateWithVersion` | `ConcurrencyConflictException` → 409 |
| Item não pertence ao contrato em removeItem | `ContractService::removeItem` | `ContractCannotBeEditedException` → 422 |

## Auditoria — quando o histórico é gerado

`RegistrarHistoricoContrato` é enfileirado por:

- `ContractObserver::created` → `evento = 'created'`, payload = atributos do contrato.
- `ContractObserver::updated` → `evento = 'updated'`, payload com `changes` e `original`.
- `ContractObserver::deleted` → `evento = 'deleted'`, payload = atributos.
- `EloquentContractRepository::updateWithVersion` → `evento = 'updated'`, payload
  com `changes`, `version_anterior`, `version_nova`.

O job é idempotente por `unique_id` — retentativas com a mesma chave não duplicam
linha em `contract_histories`.

## Como adicionar nova regra de desconto

Ver [como-adicionar-regra-de-desconto.md](como-adicionar-regra-de-desconto.md).
