# 0002 - Config-driven em vez de Strategy Pattern para Descontos

**Status**: Aceita
**Data**: 2026-05-17

## Contexto

A regra de cálculo de contrato precisa aplicar descontos extensíveis: hoje há
desconto progressivo por quantidade de itens e desconto por fidelidade (contrato com
12+ meses). Amanhã podem surgir descontos por sazonalidade, cupom, segmento, etc.

A solução acadêmica seria Strategy Pattern: uma interface `DiscountStrategy`, N
implementações concretas, um resolver que as injeta. Para 2 regras isso é cerimônia
sem retorno — interface + 2 classes + resolver + bindings = 4 arquivos para algo que
caberia em um único service.

## Decisão

Centralizar as regras ativas em `config/contract.php` e aplicá-las em
`DiscountCalculator::apply()`, que itera as entradas da config e despacha para um
método privado por regra (`quantidadeProgressivo`, `fidelidade`).

Adicionar nova regra exige:

1. Entrada na config (`descontos.<nome>`) com `ativo` + parâmetros.
2. Caso no `match` dentro de `apply()` mapeando o nome para um método privado.
3. Método privado implementando a regra.

Configuração viva, código previsível, sem ceremônia.

## Consequências

**Positivas**
- Ligar/desligar regra em produção mexe só na config.
- Parâmetros (faixas, percentuais, meses mínimos) são editáveis por env/config sem
  recompilar nem deploy de código.
- Adicionar regra é uma operação localizada (mesmo arquivo).

**Negativas**
- Não isola cada regra em seu próprio arquivo — quem quiser testar uma regra
  isoladamente precisa montar um contrato fake e chamar via `apply()`.
- O `match` em `apply()` cresce com o número de regras. Em 4-5 regras ainda é
  legível; mais que isso passa a fazer sentido extrair.

**Neutras**
- Ordem de aplicação é a ordem da config. Hoje "progressivo depois fidelidade" é
  intencional e compõe (fidelidade incide sobre o valor já reduzido). Mudar a
  ordem muda o total final.

## Alternativas consideradas

- **Strategy clássico**: descartado pelo over-engineering no escopo atual.
- **Pipeline de jobs/handlers**: descartado por aumentar muito a indireção para
  cálculo síncrono e simples.
- **Regras vivendo em banco**: descartado para o MVP porque adiciona consultas no
  caminho quente do cálculo sem necessidade real.

## Quando reavaliar

A partir de ~5 regras com lógicas radicalmente diferentes (ex.: regras dependendo
de fonte externa, feature flag, ou cupons emitidos), migrar para Strategy formal,
mantendo a config como descriptor das regras ativas.
