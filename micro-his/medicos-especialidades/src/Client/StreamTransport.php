<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Client;

/** Transporte por HTTP con streams de PHP (sin extensiones). Sin respuesta o con cuerpo no JSON: DoctorApiUnavailable. */
final class StreamTransport implements HttpTransport
{
    public function __construct(private readonly string $baseUrl, private readonly float $timeoutSeconds = 3.0)
    {
    }

    public function send(string $method, string $path, string $actorId, array $query = [], ?array $body = null): array
    {
        $url = rtrim($this->baseUrl, '/') . $path . ($query === [] ? '' : '?' . http_build_query($query));
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => "Content-Type: application/json\r\nX-User-ID: $actorId\r\n",
            'content' => $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR),
            'timeout' => $this->timeoutSeconds,
            'ignore_errors' => true, // los 4xx/5xx también traen cuerpo JSON
        ]]);
        $raw = @file_get_contents($url, false, $context);
        $status = (int) (explode(' ', $http_response_header[0] ?? '')[1] ?? 0);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if ($status === 0 || !is_array($data)) {
            throw new DoctorApiUnavailable("Sin respuesta válida de $url");
        }
        return ['status' => $status, 'body' => $data];
    }
}
