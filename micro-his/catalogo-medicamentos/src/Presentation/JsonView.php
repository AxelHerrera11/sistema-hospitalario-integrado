<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

/** Vista JSON: convierte el {status, body} de un controlador en la respuesta que se envía. */
final class JsonView
{
    /** @param array{status: int, body: array} $response */
    public function render(array $response): string
    {
        return json_encode($response['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /** @param array{status: int, body: array} $response */
    public function send(array $response): void
    {
        http_response_code($response['status']);
        header('Content-Type: application/json; charset=utf-8');
        echo $this->render($response);
    }
}
