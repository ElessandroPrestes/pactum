<?php

declare(strict_types=1);

use App\Models\Contract;
use App\Models\ContractItem;
use App\Services\DiscountCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

uses(TestCase::class);

function montarContrato(string $dataInicio, ?string $dataFim, int ...$quantidades): Contract
{
    $contract = new Contract;
    $contract->setRawAttributes([
        'id' => 1,
        'client_id' => 1,
        'data_inicio' => $dataInicio,
        'data_fim' => $dataFim,
        'status' => 'ativo',
        'version' => 1,
    ]);
    $contract->syncOriginal();

    $itens = new Collection;
    foreach ($quantidades as $quantidade) {
        $item = new ContractItem(['quantidade' => $quantidade, 'valor_unitario' => '0.00']);
        $item->quantidade = $quantidade;
        $itens->push($item);
    }
    $contract->setRelation('items', $itens);

    return $contract;
}

beforeEach(function () {
    $this->calculator = new DiscountCalculator;

    Config::set('contract.descontos', [
        'quantidade_progressivo' => [
            'ativo' => true,
            'faixas' => [
                ['min_itens' => 5, 'percentual' => 5.0],
                ['min_itens' => 10, 'percentual' => 10.0],
            ],
        ],
        'fidelidade' => [
            'ativo' => true,
            'meses_minimos' => 12,
            'percentual' => 7.0,
        ],
    ]);
});

describe('DiscountCalculator', function () {
    it('nao aplica desconto abaixo de qualquer faixa e sem fidelidade', function () {
        $contract = montarContrato('2026-01-01', '2026-06-30', 1, 2);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('1000.00');
    });

    it('aplica faixa de cinco por cento entre 5 e 9 itens', function () {
        $contract = montarContrato('2026-01-01', '2026-06-30', 3, 3);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('950.00');
    });

    it('aplica a maior faixa elegivel quando ha sobreposicao', function () {
        $contract = montarContrato('2026-01-01', '2026-06-30', 6, 5);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('900.00');
    });

    it('aplica fidelidade quando contrato tem doze meses ou mais', function () {
        $contract = montarContrato('2026-01-01', '2027-01-01', 1);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('930.00');
    });

    it('compoe progressivo e fidelidade aplicando o segundo sobre o primeiro', function () {
        $contract = montarContrato('2026-01-01', '2027-01-01', 5);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('883.50');
    });

    it('usa data atual quando contrato nao tem data fim', function () {
        Carbon::setTestNow('2027-02-01');

        $contract = montarContrato('2026-01-01', null, 1);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('930.00');

        Carbon::setTestNow();
    });

    it('ignora regras inativas na config', function () {
        Config::set('contract.descontos.quantidade_progressivo.ativo', false);
        Config::set('contract.descontos.fidelidade.ativo', false);

        $contract = montarContrato('2026-01-01', '2027-01-01', 20);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('1000.00');
    });

    it('retorna valor com duas casas decimais sempre', function () {
        $contract = montarContrato('2026-01-01', '2026-02-01', 1);

        $resultado = $this->calculator->apply($contract, '199.99');

        expect($resultado)->toMatch('/^\d+\.\d{2}$/');
    });

    it('nao aplica fidelidade abaixo dos meses minimos', function () {
        $contract = montarContrato('2026-01-01', '2026-12-15', 1);

        expect($this->calculator->apply($contract, '1000.00'))->toBe('1000.00');
    });

    it('retorna zero quando subtotal e zero', function () {
        $contract = montarContrato('2026-01-01', '2027-01-01', 20);

        expect($this->calculator->apply($contract, '0.00'))->toBe('0.00');
    });

    it('nao quebra quando contrato nao tem itens', function () {
        $contract = montarContrato('2026-01-01', '2026-06-30');

        expect($this->calculator->apply($contract, '500.00'))->toBe('500.00');
    });
});
