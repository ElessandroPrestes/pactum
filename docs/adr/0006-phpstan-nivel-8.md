# 0006 - PHPStan nível 8

**Status**: Aceita
**Data**: 2026-05-14

## Contexto

PHPStan oferece níveis de 0 a 10. Cada nível adiciona regras incrementais. Os
níveis mais usados na comunidade Laravel são 5 e 6 — "estrito o suficiente" sem o
custo de tipar arrays, generics e PHPDoc detalhado.

Em projetos pequenos com domínio simples, nível 6 captura ~90% dos bugs comuns. Em
projetos com regras de negócio que dependem de tipos exatos (cálculo com BCMath,
enums em casts, IDs entre tabelas), os 10% restantes são exatamente onde o bug
acontece.

## Decisão

Configurar `phpstan.neon` em nível 8 com plugin `larastan/larastan` para entender
macros do Eloquent, FormRequest, Collection genérica etc.

## Consequências

**Positivas**
- `numeric-string` é obrigatório em parâmetros de funções BCMath. Isso forçou o
  `DiscountCalculator` a tratar valores como strings consistentemente — passar
  float por engano seria pego no CI.
- Casts de enum são detectados como `ClientStatus`/`ContractStatus`, não `string`.
- Generics em `LengthAwarePaginator<int, Model>` documentam o contrato real.
- Coverage real de tipo, não só "compila".

**Negativas**
- Mais PHPDoc. Cada método com array tipado precisa de `@param array<...>` /
  `@return array<...>`. Em PRs novos é fricção até criar muscle memory.
- Algumas anotações exigem trabalho extra (ex.: relações Eloquent precisam de
  `@property-read Collection<int, Model>` para o nível 8 entender).

**Neutras**
- O custo cai com prática. Depois de 2-3 fases do projeto, a maioria das anotações
  vem direto da IDE / copy-paste de padrão já estabelecido.

## Alternativas consideradas

- **Nível 6**: descartado por deixar passar exatamente os bugs onde tipos importam
  (BCMath, casts, IDs entre tabelas).
- **Psalm em vez de PHPStan**: descartado por afinidade do ecossistema Laravel com
  Larastan e por simplicidade do output.

## Quando reavaliar

Se o time crescer e o custo de manter anotações se tornar bloqueante, considerar
nível 7 como compromisso. Mas o caminho preferido é treinar o time, não baixar o
gate.
