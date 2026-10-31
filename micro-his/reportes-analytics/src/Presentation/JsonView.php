<?php

declare(strict_types=1);

namespace MicroHis\Reportes\Presentation;

/** Vista JSON sencilla del Micro-HIS: respuestas consuetudinarias y estables. */
final class JsonView
{
    /** @param array<string, mixed> $payload */
    public function render(int $status, array $payload): array
    {
        return [
            'status' => $status,
            'headers' => ['Content-Type' => 'application/json; charset=UTF-8'],
            'body' => $payload,
        ];
    }
}
