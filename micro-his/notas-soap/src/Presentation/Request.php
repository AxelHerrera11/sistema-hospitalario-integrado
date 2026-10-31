<?php

declare(strict_types=1);

namespace MicroHis\NotasSoap\Presentation;

use MicroHis\NotasSoap\Domain\SoapRuleViolation;

/** Petición HTTP ya decodificada; el front controller la arma desde las superglobales. Un campo con tipo incorrecto es 422 antes de llegar al caso de uso. */
final readonly class Request
{
    public function __construct(public string $method, public string $path, public array $headers = [], public array $body = [])
    {
    }

    public function actor(): string
    {
        return self::string($this->headers, 'X-User-ID');
    }

    /** Campo de texto del cuerpo: ausente equivale a cadena vacía para que el dominio lo rechace. */
    public function text(string $field): string
    {
        return self::string($this->body, $field);
    }

    private static function string(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : throw new SoapRuleViolation('PAYLOAD_INVALID', 422);
    }
}
