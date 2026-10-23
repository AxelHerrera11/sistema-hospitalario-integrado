<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Client;

use MicroHis\Medicos\Presentation\ApiKernel;
use MicroHis\Medicos\Presentation\Request;

/** Transporte en proceso: el cliente habla con el ApiKernel sin red. Lo usan las pruebas y una integración dentro del mismo despliegue. */
final class KernelTransport implements HttpTransport
{
    public function __construct(private readonly ApiKernel $kernel)
    {
    }

    public function send(string $method, string $path, string $actorId, array $query = [], ?array $body = null): array
    {
        $response = $this->kernel->handle(new Request($method, $path, ['X-User-ID' => $actorId], $query, $body ?? []));
        return ['status' => $response['status'], 'body' => json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR)];
    }
}
