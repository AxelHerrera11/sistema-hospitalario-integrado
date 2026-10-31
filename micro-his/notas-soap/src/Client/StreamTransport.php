<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Client;

/** Transporte HTTP/JSON con timeout acotado: si no hay respuesta, SoapNoteApiUnavailable; nunca espera indefinidamente. */
final class StreamTransport implements HttpTransport
{
    public function __construct(private readonly string $baseUrl, private readonly float $timeoutSeconds = 2.0)
    {
    }

    public function send(string $method, string $path, string $actorId, array $body = []): array
    {
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\nX-User-ID: $actorId\r\n",
            'content' => $method === 'GET' ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'timeout' => $this->timeoutSeconds,
            'ignore_errors' => true, // 4xx y 5xx también traen cuerpo JSON con code y trace_id
        ]]);
        $raw = @file_get_contents(rtrim($this->baseUrl, '/') . $path, false, $context);
        if ($raw === false || !isset($http_response_header[0]) || preg_match('#^HTTP/\S+ (\d{3})#', $http_response_header[0], $m) !== 1) {
            throw new SoapNoteApiUnavailable('servidor_no_disponible');
        }
        return ['status' => (int) $m[1], 'body' => json_decode($raw, true) ?? []];
    }
}
