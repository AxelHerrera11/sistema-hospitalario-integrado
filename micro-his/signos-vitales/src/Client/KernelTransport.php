<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Client;

use MicroHis\SignosVitales\Presentation\ApiKernel;
use MicroHis\SignosVitales\Presentation\Request;

/** Transporte en proceso: el consumidor llama al ApiKernel del mismo despliegue (monolito modular). */
final class KernelTransport implements HttpTransport
{
    public function __construct(private readonly ApiKernel $api)
    {
    }

    public function send(string $method, string $path, string $actorId, array $body = []): array
    {
        $response = $this->api->handle(new Request($method, $path, ['X-User-ID' => $actorId], $body));
        return ['status' => $response['status'], 'body' => json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR)];
    }
}
