<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Client;

/** Cómo viaja la petición al servidor: en proceso o por red. El cliente no cambia si el módulo se separa. */
interface HttpTransport
{
    /**
     * @return array{status: int, body: array<string, mixed>}
     * @throws SoapNoteApiUnavailable si el servidor no responde
     */
    public function send(string $method, string $path, string $actorId, array $body = []): array;
}
