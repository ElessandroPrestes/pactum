<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class DocumentoValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('O campo :attribute deve ser um cpf ou cnpj valido.');

            return;
        }

        $digitos = preg_replace('/\D/', '', $value) ?? '';

        $valido = match (strlen($digitos)) {
            11 => $this->cpfValido($digitos),
            14 => $this->cnpjValido($digitos),
            default => false,
        };

        if (! $valido) {
            $fail('O campo :attribute deve ser um cpf ou cnpj valido.');
        }
    }

    private function cpfValido(string $cpf): bool
    {
        if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($posicao = 9; $posicao < 11; $posicao++) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cpf[$i] * (($posicao + 1) - $i);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ((int) $cpf[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }

    private function cnpjValido(string $cnpj): bool
    {
        if (preg_match('/^(\d)\1{13}$/', $cnpj) === 1) {
            return false;
        }

        $pesos = [
            12 => [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
            13 => [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2],
        ];

        foreach ($pesos as $posicao => $multiplicadores) {
            $soma = 0;

            for ($i = 0; $i < $posicao; $i++) {
                $soma += (int) $cnpj[$i] * $multiplicadores[$i];
            }

            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;

            if ((int) $cnpj[$posicao] !== $digito) {
                return false;
            }
        }

        return true;
    }
}
