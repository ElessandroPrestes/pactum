<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected const ITENS_POR_PAGINA = 15;

    protected function perPagina(Request $request): int
    {
        return $request->integer('per_page', self::ITENS_POR_PAGINA);
    }
}
