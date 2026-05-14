<?php

declare(strict_types=1);

namespace App\Enums;

enum DocumentType: string
{
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
}
