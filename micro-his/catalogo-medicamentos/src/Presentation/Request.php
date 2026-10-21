<?php

declare(strict_types=1);

namespace MicroHis\MedicationCatalog\Presentation;

/** Petición HTTP ya leída: el Router no toca superglobales y se puede probar sin servidor. */
final readonly class Request
{
    /**
     * @param array<string, mixed> $query
     * @param string $rawBody cuerpo sin decodificar; el Router decide si debe ser JSON
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query = [],
        public string $rawBody = '',
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);

        return new self(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')), is_string($path) ? $path : '/', $_GET,
            (string) file_get_contents('php://input'));
    }
}
