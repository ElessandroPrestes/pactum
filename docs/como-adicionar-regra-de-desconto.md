# Como adicionar nova regra de desconto

Este guia mostra como estender o cálculo do total do contrato com uma nova regra
de desconto sem alterar regras existentes. O caminho é **config-driven**: nova
regra = nova entrada em `config/contract.php` + novo método privado em
`DiscountCalculator`.

## Exemplo: desconto sazonal

Vamos adicionar um desconto de **3%** quando o contrato começa em dezembro
(promoção de fim de ano).

### 1. Declare a regra na config

`config/contract.php`:

```php
'descontos' => [
    'quantidade_progressivo' => [
        'ativo' => true,
        'faixas' => [
            ['min_itens' => 5,  'percentual' => 5.0],
            ['min_itens' => 10, 'percentual' => 10.0],
        ],
    ],

    'fidelidade' => [
        'ativo' => true,
        'meses_minimos' => 12,
        'percentual' => 7.0,
    ],

    // Nova regra
    'sazonal_dezembro' => [
        'ativo' => env('CONTRACT_DESCONTO_SAZONAL', true),
        'mes' => 12,
        'percentual' => 3.0,
    ],
],
```

Pontos importantes:

- O nome da chave (`sazonal_dezembro`) é o **identificador** da regra. Não use
  espaços nem caracteres especiais.
- `ativo` deve ser bool. Use `env(...)` se quiser liga/desliga por ambiente.
- Coloque parâmetros (`mes`, `percentual`) com nomes descritivos. Eles serão
  acessados no método.

### 2. Adicione o caso no match

`app/Services/DiscountCalculator.php`, dentro de `apply()`:

```php
$valor = match ($nome) {
    'quantidade_progressivo' => $this->quantidadeProgressivo($contract, $valor, $configuracao),
    'fidelidade'             => $this->fidelidade($contract, $valor, $configuracao),
    'sazonal_dezembro'       => $this->sazonalDezembro($contract, $valor, $configuracao),
    default                  => $valor,
};
```

### 3. Implemente o método privado

Mesmo arquivo, abaixo dos outros métodos:

```php
/**
 * @param  numeric-string  $subtotal
 * @param  array<string, mixed>  $configuracao
 * @return numeric-string
 */
private function sazonalDezembro(Contract $contract, string $subtotal, array $configuracao): string
{
    $mesAlvo = (int) ($configuracao['mes'] ?? 0);
    /** @var numeric-string $percentual */
    $percentual = (string) ($configuracao['percentual'] ?? 0);

    if ($contract->data_inicio->month !== $mesAlvo) {
        return $subtotal;
    }

    return $this->aplicarPercentual($subtotal, $percentual);
}
```

Aproveite `aplicarPercentual()` para manter o cálculo BCMath consistente —
nunca multiplique por `float`.

### 4. Cubra com teste unitário

`tests/Unit/Services/DiscountCalculatorTest.php`:

```php
it('aplica desconto sazonal quando contrato comeca em dezembro', function () {
    Config::set('contract.descontos', [
        'sazonal_dezembro' => [
            'ativo' => true,
            'mes' => 12,
            'percentual' => 3.0,
        ],
    ]);

    $contract = montarContrato('2026-12-15', '2027-12-15', 1);

    expect($this->calculator->apply($contract, '1000.00'))->toBe('970.00');
});

it('nao aplica desconto sazonal em outros meses', function () {
    Config::set('contract.descontos', [
        'sazonal_dezembro' => [
            'ativo' => true,
            'mes' => 12,
            'percentual' => 3.0,
        ],
    ]);

    $contract = montarContrato('2026-06-15', '2027-06-15', 1);

    expect($this->calculator->apply($contract, '1000.00'))->toBe('1000.00');
});
```

### 5. Atualize a documentação de regras

Adicione a nova regra em [`docs/regras-de-negocio.md`](regras-de-negocio.md) na
seção "Regras de desconto" com um exemplo numérico — futuros leitores entendem
sem precisar abrir o código.

### 6. Verifique a ordem de composição

Regras são aplicadas **na ordem da config**. Cada regra recebe o valor já
modificado pelas anteriores. Pense em onde encaixar a nova:

- Antes de `fidelidade`? A fidelidade incidirá sobre o valor já com sazonal.
- Depois de `fidelidade`? Sazonal incidirá sobre o valor já com fidelidade.

Documente a decisão no PR — não há resposta universalmente "certa".

## O que **não** fazer

- Não criar um service novo só para uma regra simples. O `DiscountCalculator`
  centraliza a iteração de regras e o uso de BCMath. Espalhar quebra a forma
  consistente de testar.
- Não aplicar o percentual com `*` em `float`. BCMath é obrigatório para
  evitar erros de arredondamento em valores monetários.
- Não ler do banco dentro da regra sem cache. Se a regra precisa de dados
  dinâmicos, traga-os por dependência (injeção) e cacheie em camada acima.
- Não esquecer de pôr `ativo => true` no teste — esquecer faz o teste passar
  por motivo errado (regra ignorada).

## Quando o config-driven deixa de fazer sentido

Quando você tiver 5+ regras com lógicas radicalmente diferentes (ex.: regras
dependendo de fonte externa, cupons emitidos, segmentos de cliente), considere
migrar para Strategy Pattern formal. O caminho está descrito em
[ADR-0002](adr/0002-config-driven-em-vez-de-strategy.md), na seção "Quando
reavaliar".
