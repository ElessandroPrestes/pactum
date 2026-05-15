<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InactiveClientException extends HttpException
{
    public function __construct(string $mensagem = 'Cliente inativo nao pode receber novos contratos.')
    {
        parent::__construct(statusCode: 422, message: $mensagem);
    }
}
