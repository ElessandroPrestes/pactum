<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ConcurrencyConflictException extends HttpException
{
    public function __construct(string $mensagem = 'Conflito de concorrencia: versao do contrato desatualizada.')
    {
        parent::__construct(statusCode: 409, message: $mensagem);
    }
}
