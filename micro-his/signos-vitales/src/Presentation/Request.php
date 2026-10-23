<?php

declare(strict_types=1);

namespace MicroHis\SignosVitales\Presentation;

use MicroHis\SignosVitales\Domain\VitalSignViolation;

/** Petición HTTP ya decodificada; el front controller la arma desde las superglobales. Un campo con tipo incorrecto es 422 antes de llegar al caso de uso. */
final readonly class Request
{
    /** Único campo del cuerpo que no es un signo vital. */
    private const MEASURED_AT = 'measured_at';

    public function __construct(public string $method, public string $path, public array $headers = [], public array $body = [])
    {
    }

    public function actor(): string
    {
        return self::string($this->headers, 'X-User-ID');
    }

    public function measuredAt(): string
    {
        return self::string($this->body, self::MEASURED_AT);
    }

    /** @return array<string, float> los signos enviados; qué signos y valores son válidos lo decide el dominio */
    public function measurements(): array
    {
        $values = array_diff_key($this->body, [self::MEASURED_AT => true]);
        if (array_filter($values, static fn ($v): bool => !is_int($v) && !is_float($v)) !== []) {
            throw new VitalSignViolation('VITAL_SIGNS_INVALID', 422);
        }
        return array_map('floatval', $values);
    }

    /** Campo de texto: ausente equivale a cadena vacía para que el caso de uso lo rechace; cualquier otro tipo es 422. */
    private static function string(array $source, string $key): string
    {
        $value = $source[$key] ?? '';
        return is_string($value) ? $value : throw new VitalSignViolation('PAYLOAD_INVALID', 422);
    }
}
