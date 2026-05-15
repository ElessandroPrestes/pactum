<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    public const HEADER = 'Idempotency-Key';

    private const TTL_SEGUNDOS = 86400;

    private const TAMANHO_MAXIMO = 128;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $chave = $request->header(self::HEADER);

        if ($chave === null || $chave === '') {
            return new JsonResponse([
                'message' => 'Header Idempotency-Key e obrigatorio neste endpoint.',
                'trace_id' => $request->header(TraceId::HEADER),
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($chave) > self::TAMANHO_MAXIMO || ! preg_match('/^[A-Za-z0-9_\-]+$/', $chave)) {
            return new JsonResponse([
                'message' => 'Idempotency-Key invalida.',
                'trace_id' => $request->header(TraceId::HEADER),
            ], Response::HTTP_BAD_REQUEST);
        }

        $hashBody = hash('sha256', (string) $request->getContent());
        $cacheKey = $this->cacheKey($request, $chave);

        /** @var array{hash: string, status: int, body: string, headers: array<string, list<string>>}|null $registrado */
        $registrado = Cache::get($cacheKey);

        if (is_array($registrado)) {
            if ($registrado['hash'] !== $hashBody) {
                return new JsonResponse([
                    'message' => 'Idempotency-Key ja usada com payload diferente.',
                    'trace_id' => $request->header(TraceId::HEADER),
                ], Response::HTTP_CONFLICT);
            }

            return $this->reconstruir($registrado);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 500) {
            Cache::put($cacheKey, [
                'hash' => $hashBody,
                'status' => $response->getStatusCode(),
                'body' => (string) $response->getContent(),
                'headers' => $this->cabecalhosArmazenaveis($response),
            ], self::TTL_SEGUNDOS);
        }

        return $response;
    }

    private function cacheKey(Request $request, string $chave): string
    {
        $usuario = $request->user();
        $escopo = $usuario?->getAuthIdentifier() ?? $request->ip();

        return 'idempotency:'.hash('sha256', $escopo.'|'.$request->path().'|'.$chave);
    }

    /**
     * @param  array{hash: string, status: int, body: string, headers: array<string, list<string>>}  $registrado
     */
    private function reconstruir(array $registrado): Response
    {
        $response = new Response($registrado['body'], $registrado['status']);

        foreach ($registrado['headers'] as $nome => $valores) {
            $response->headers->set($nome, $valores);
        }

        return $response;
    }

    /**
     * @return array<string, list<string>>
     */
    private function cabecalhosArmazenaveis(Response $response): array
    {
        $permitidos = ['content-type', 'location'];
        $resultado = [];

        foreach ($response->headers->all() as $nome => $valores) {
            if (in_array(strtolower($nome), $permitidos, true)) {
                $resultado[$nome] = array_values(array_filter($valores, fn ($v) => $v !== null));
            }
        }

        return $resultado;
    }
}
