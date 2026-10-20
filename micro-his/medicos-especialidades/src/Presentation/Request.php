<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Presentation;

use MicroHis\Medicos\Domain\DoctorRuleViolation;

/** Petición HTTP ya decodificada; el front controller la arma desde las superglobales. Un campo con tipo incorrecto es 422 antes de llegar al caso de uso. */
final readonly class Request
{
    public function __construct(public string $method, public string $path, public array $headers = [], public array $query = [], public array $body = [])
    {
    }

    public function actor(): string
    {
        return self::string($this->headers, 'X-User-ID');
    }

    /** Campo de texto del cuerpo: ausente equivale a cadena vacía para que el caso de uso lo rechace. */
    public function text(string $field): string
    {
        return self::string($this->body, $field);
    }

    /** Campo de texto opcional del cuerpo: ausente o null. */
    public function optionalText(string $field): ?string
    {
        return ($this->body[$field] ?? null) === null ? null : self::string($this->body, $field);
    }

    /** Campo booleano del cuerpo: ausente es false; la cadena "false" no es un booleano. */
    public function flag(string $field): bool
    {
        $value = $this->body[$field] ?? false;
        return is_bool($value) ? $value : throw new DoctorRuleViolation('PAYLOAD_INVALID', 422);
    }

    public function queryText(string $field): string
    {
        return self::string($this->query, $field);
    }

    private static function string(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : throw new DoctorRuleViolation('PAYLOAD_INVALID', 422);
    }
}
