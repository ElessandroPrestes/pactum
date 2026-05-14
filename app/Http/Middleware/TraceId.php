<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TraceId
{
    public const HEADER = 'X-Trace-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->header(self::HEADER) ?: (string) Str::uuid();

        $request->headers->set(self::HEADER, $traceId);

        Log::withContext(['trace_id' => $traceId]);

        $response = $next($request);

        $response->headers->set(self::HEADER, $traceId);

        return $response;
    }
}
