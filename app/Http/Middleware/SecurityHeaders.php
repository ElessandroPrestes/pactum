<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-site');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Content-Security-Policy', $this->csp($request, $response));

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    private function csp(Request $request, Response $response): string
    {
        if ($this->ehRespostaDeApi($request, $response)) {
            return "default-src 'none'; frame-ancestors 'none'";
        }

        return "default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none'";
    }

    private function ehRespostaDeApi(Request $request, Response $response): bool
    {
        if ($request->is('api/*')) {
            return true;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'application/json');
    }
}
