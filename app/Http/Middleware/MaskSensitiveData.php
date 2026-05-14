<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MaskSensitiveData
{
    /**
     * Campos cujo conteudo e dado pessoal sob LGPD e deve ser mascarado nos logs.
     *
     * @var list<string>
     */
    private const SENSITIVE_FIELDS = ['documento', 'cpf', 'cnpj', 'email', 'nome'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::info('http.request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'payload' => $this->mask($request->all()),
        ]);

        return $response;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function mask(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->mask($value);

                continue;
            }

            if (is_string($value) && in_array(mb_strtolower((string) $key), self::SENSITIVE_FIELDS, true)) {
                $data[$key] = $this->maskValue(mb_strtolower((string) $key), $value);
            }
        }

        return $data;
    }

    private function maskValue(string $field, string $value): string
    {
        return match ($field) {
            'email' => $this->maskEmail($value),
            'documento', 'cpf', 'cnpj' => $this->maskDocument($value),
            default => '***',
        };
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2 || $parts[0] === '') {
            return '***';
        }

        return $parts[0][0].'***@'.$parts[1];
    }

    private function maskDocument(string $document): string
    {
        $digits = preg_replace('/\D/', '', $document) ?? '';

        if (mb_strlen($digits) < 5) {
            return '***';
        }

        return mb_substr($digits, 0, 3)
            .str_repeat('*', mb_strlen($digits) - 5)
            .mb_substr($digits, -2);
    }
}
