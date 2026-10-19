<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Presentation;

/** Petición HTTP ya decodificada; el front controller la arma desde las superglobales. */
final readonly class Request
{
    public function __construct(public string $method, public string $path, public array $headers = [], public array $query = [], public array $body = [])
    {
    }

    public function actor(): string
    {
        return (string) ($this->headers['X-User-ID'] ?? '');
    }

    /** Campo del cuerpo como texto; un valor no escalar queda vacío para que el caso de uso lo rechace. */
    public function text(string $field): string
    {
        $value = $this->body[$field] ?? '';
        return is_scalar($value) ? (string) $value : '';
    }
}
