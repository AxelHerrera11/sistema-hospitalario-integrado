<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Presentation;

/**
 * Vista: serializa la respuesta a JSON. No conoce casos de uso ni reglas. Cache-Control: no-store porque la
 * respuesta puede llevar datos clínicos que no deben quedar en cachés intermedias ni del navegador.
 */
final class JsonView
{
    /** @return array{status: int, headers: array<string, string>, body: string} */
    public function render(int $status, array $data): array
    {
        return [
            'status' => $status,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Cache-Control' => 'no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ];
    }
}
