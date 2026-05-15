<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ContractCannotBeEditedException extends HttpException
{
    public function __construct(string $mensagem = 'Contrato cancelado nao pode ser editado.')
    {
        parent::__construct(statusCode: 422, message: $mensagem);
    }
}
