# 0001 - Usar Repository Pattern com Eloquent

**Status**: Aceita
**Data**: 2026-05-17

## Contexto

Existe debate legítimo na comunidade Laravel sobre usar Repository Pattern sobre o
Eloquent. O Eloquent já é Active Record e abstrai persistência; críticos dizem que o
repositório vira "Eloquent embrulhado em interface" sem ganho real.

Por outro lado, services dependentes diretamente de models concretos ficam difíceis
de testar em unidade (forçam `RefreshDatabase`), espalham eager loading pelo código e
acoplam regras a uma única origem de dados.

## Decisão

Usar Repository Pattern com uma interface por contexto (`ClientRepositoryInterface`,
`ContractRepositoryInterface`, `ServiceRepositoryInterface`) e implementação Eloquent
(`EloquentClientRepository` etc.) registrada via `RepositoryServiceProvider`.

Services dependem da interface, não da implementação.

## Consequências

**Positivas**
- Services testáveis em unidade com `Mockery::mock(InterfaceX::class)`, sem banco.
- Eager loading centralizado (`with(['items.service', 'client'])`) evita N+1 espalhado.
- Permite trocar parte da origem de dados (ex.: API externa) sem mexer em service.
- Inversão de dependência explícita no provider torna o grafo legível.

**Negativas**
- Um arquivo a mais por entidade (interface + implementação).
- Para CRUDs triviais sem regra de negócio, o repositório vira passa-pra-frente.

**Neutras**
- A interface não tenta abstrair a query builder. Métodos retornam paginadores e
  modelos concretos do Eloquent — o contrato é "como obter dados", não "como
  consultar de forma agnóstica de ORM".

## Alternativas consideradas

- **Services chamando Eloquent direto**: descartado por dificultar testes em unidade
  e por espalhar eager loading.
- **Action classes invocáveis por endpoint** (uma classe por operação): descartado
  por dispersar regras compartilhadas (ex.: invariante de "contrato cancelado não
  aceita item") em N classes em vez de centralizar no service.

## Quando reavaliar

Se o projeto crescer com CRUDs triviais sem regra associada, ou se a duplicação
entre repositórios começar a pesar, reavaliar trocando por uma base trait com
operações genéricas — sem descartar a interface.
