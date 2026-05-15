# 0005 - Tabela de histórico em vez de Event Sourcing

**Status**: Aceita
**Data**: 2026-05-15

## Contexto

Contratos exigem auditoria: registrar quem fez o quê e quando, com payload do evento,
para investigação e compliance. As duas abordagens canônicas são:

1. **Event Sourcing puro**: estado do contrato é uma projeção de uma sequência
   imutável de eventos. Toda mutação vira evento, e o estado atual é reconstruído.
2. **Tabela de auditoria**: o estado vive em `contracts` como sempre, e uma tabela
   `contract_histories` registra cada evento com snapshot JSON.

Event Sourcing é caro: força pensar em todo fluxo como projeção, exige snapshots
para performance, complica leitura, e o rebuild de estado vira preocupação contínua.

## Decisão

Usar tabela `contract_histories` com colunas: `id`, `contract_id`, `evento`,
`payload` (JSON), `usuario_id` (placeholder), `unique_id` (string única), `created_at`.

Job `RegistrarHistoricoContrato` (queue Redis) é disparado em:

- `ContractObserver::created` → `evento = 'created'`
- `ContractObserver::updated` → `evento = 'updated'` (para fluxos via Eloquent)
- `ContractObserver::deleted` → `evento = 'deleted'`
- `EloquentContractRepository::updateWithVersion` → `evento = 'updated'` (porque o
  optimistic lock bypassa o Eloquent e o observer não dispara).

O job é idempotente por `unique_id` — antes de inserir, verifica se já existe linha
com aquela chave. Retentativas não duplicam histórico.

## Consequências

**Positivas**
- Consulta de histórico é uma query simples paginada — `GET /contracts/{id}/history`
  retorna direto.
- Estado atual continua sendo o que está em `contracts` — não há rebuild.
- Payload em JSON permite evolução sem migration (campos novos no payload são
  retrocompatíveis).
- A tabela é insert-only: índice composto `(contract_id, created_at)` torna paginação
  eficiente.

**Negativas**
- Não há "reproduzir o contrato no tempo T" como projeção determinística — só dá
  para inspecionar payloads. Se essa necessidade aparecer, evolui-se.
- Risco: se o observer/repository esquecerem de disparar o job para algum caminho,
  o evento some. Mitigação: cobertura de testes de feature batendo nos quatro
  caminhos (created/updated/deleted via Eloquent + updated via optimistic lock).

**Neutras**
- O job roda na queue Redis, assíncrono. Pequena janela de inconsistência entre
  mutar o contrato e o evento aparecer no histórico (~milissegundos).

## Alternativas consideradas

- **Event Sourcing**: descartado pelo custo desproporcional ao escopo.
- **Pacote `spatie/laravel-activitylog`**: descartado por adicionar dependência
  externa e cast genérico de mudanças. A modelagem própria deixa o payload por
  evento (`changes`, `version_anterior`, `version_nova` em updates) mais expressivo.
- **Logs estruturados como única fonte**: descartado porque logs são feitos para
  observabilidade, não para query transacional.

## Quando reavaliar

Se aparecer requisito formal de replay de estado (ex.: "reconstrua o total de
contratos em 31/dez/2027"), considerar evolução para event sourcing parcial usando
a tabela atual como base.
