<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Presentation;

/** Vista: serializa la respuesta a JSON. No conoce casos de uso ni reglas. */
final class JsonView
{
    /** @return array{status: int, headers: array<string, string>, body: string} */
    public function render(int $status, array $data): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ];
    }
}
