<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Client;

/** Cómo viaja la petición: por red (StreamTransport) o en proceso (KernelTransport, pruebas). Si no hay respuesta, lanza DoctorApiUnavailable. */
interface HttpTransport
{
    /** @return array{status: int, body: array<string, mixed>} */
    public function send(string $method, string $path, string $actorId, array $query = [], ?array $body = null): array;
}
