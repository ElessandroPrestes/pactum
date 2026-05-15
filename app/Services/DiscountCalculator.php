<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use Illuminate\Support\Carbon;

class DiscountCalculator
{
    private const SCALE = 2;

    private const INTERNAL_SCALE = 6;

    public function apply(Contract $contract, string $subtotal): string
    {
        /** @var array<string, mixed> $regras */
        $regras = (array) config('contract.descontos', []);

        /** @var numeric-string $valor */
        $valor = $this->normalizar($subtotal);

        foreach ($regras as $nome => $configuracao) {
            if (! is_array($configuracao) || ($configuracao['ativo'] ?? false) !== true) {
                continue;
            }

            $valor = match ($nome) {
                'quantidade_progressivo' => $this->quantidadeProgressivo($contract, $valor, $configuracao),
                'fidelidade' => $this->fidelidade($contract, $valor, $configuracao),
                default => $valor,
            };
        }

        return bcadd($valor, '0', self::SCALE);
    }

    /**
     * @param  numeric-string  $subtotal
     * @param  array<string, mixed>  $configuracao
     * @return numeric-string
     */
    private function quantidadeProgressivo(Contract $contract, string $subtotal, array $configuracao): string
    {
        /** @var list<array{min_itens: int, percentual: float|int}> $faixas */
        $faixas = $configuracao['faixas'] ?? [];

        if ($faixas === []) {
            return $subtotal;
        }

        $totalItens = 0;
        foreach ($contract->items as $item) {
            $totalItens += $item->quantidade;
        }

        usort($faixas, fn (array $a, array $b): int => $b['min_itens'] <=> $a['min_itens']);

        foreach ($faixas as $faixa) {
            if ($totalItens >= $faixa['min_itens']) {
                /** @var numeric-string $percentual */
                $percentual = (string) $faixa['percentual'];

                return $this->aplicarPercentual($subtotal, $percentual);
            }
        }

        return $subtotal;
    }

    /**
     * @param  numeric-string  $subtotal
     * @param  array<string, mixed>  $configuracao
     * @return numeric-string
     */
    private function fidelidade(Contract $contract, string $subtotal, array $configuracao): string
    {
        $mesesMinimos = (int) ($configuracao['meses_minimos'] ?? 0);
        /** @var numeric-string $percentual */
        $percentual = (string) ($configuracao['percentual'] ?? 0);

        $inicio = $contract->data_inicio;
        $fim = $contract->data_fim ?? Carbon::now();

        $meses = (int) $inicio->diffInMonths($fim);

        if ($meses < $mesesMinimos) {
            return $subtotal;
        }

        return $this->aplicarPercentual($subtotal, $percentual);
    }

    /**
     * @param  numeric-string  $valor
     * @param  numeric-string  $percentual
     * @return numeric-string
     */
    private function aplicarPercentual(string $valor, string $percentual): string
    {
        /** @var numeric-string $fator */
        $fator = bcdiv(bcsub('100', $percentual, self::INTERNAL_SCALE), '100', self::INTERNAL_SCALE);

        return bcmul($valor, $fator, self::INTERNAL_SCALE);
    }

    /**
     * @return numeric-string
     */
    private function normalizar(string $valor): string
    {
        /** @var numeric-string $numerico */
        $numerico = is_numeric($valor) ? $valor : '0';

        return bcadd($numerico, '0', self::INTERNAL_SCALE);
    }
}
