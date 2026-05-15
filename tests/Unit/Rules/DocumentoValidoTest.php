<?php

declare(strict_types=1);

use App\Rules\DocumentoValido;

function validarDocumento(mixed $valor): bool
{
    $valido = true;

    (new DocumentoValido)->validate(
        'documento',
        $valor,
        function () use (&$valido): void {
            $valido = false;
        }
    );

    return $valido;
}

describe('DocumentoValido', function () {
    it('aceita cpf valido', function (string $cpf) {
        expect(validarDocumento($cpf))->toBeTrue();
    })->with([
        'sem formatacao' => '11144477735',
        'formatado' => '111.444.777-35',
        'outro valido' => '52998224725',
    ]);

    it('aceita cnpj valido', function (string $cnpj) {
        expect(validarDocumento($cnpj))->toBeTrue();
    })->with([
        'sem formatacao' => '11222333000181',
        'formatado' => '11.222.333/0001-81',
    ]);

    it('rejeita cpf com digito verificador invalido', function () {
        expect(validarDocumento('12345678901'))->toBeFalse();
    });

    it('rejeita cnpj com digito verificador invalido', function () {
        expect(validarDocumento('11222333000100'))->toBeFalse();
    });

    it('rejeita documento com todos os digitos iguais', function (string $documento) {
        expect(validarDocumento($documento))->toBeFalse();
    })->with([
        'cpf repetido' => '11111111111',
        'cnpj repetido' => '00000000000000',
    ]);

    it('rejeita documento com quantidade de digitos invalida', function (string $documento) {
        expect(validarDocumento($documento))->toBeFalse();
    })->with([
        'curto' => '123',
        'vazio' => '',
        'longo' => '123456789012345',
    ]);

    it('rejeita valor que nao e string', function (mixed $valor) {
        expect(validarDocumento($valor))->toBeFalse();
    })->with([
        'inteiro' => 12345678901,
        'nulo' => null,
        'array' => [['11144477735']],
    ]);
});
