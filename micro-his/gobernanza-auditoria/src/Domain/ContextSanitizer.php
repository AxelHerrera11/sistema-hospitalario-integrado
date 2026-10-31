<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Domain;

/**
 * Regla de dominio: qué contexto adicional puede guardarse en un evento.
 *
 * Solo se aceptan pares clave => escalar (o listas cortas de escalares).
 * Se eliminan las claves que nombran secretos o datos clínicos y los valores
 * con forma de token. Lo eliminado se informa por nombre de clave (nunca por
 * valor) para que la omisión sea visible y no silenciosa.
 */
final class ContextSanitizer
{
    public const MAX_KEYS = 20;
    public const MAX_STRING = 200;
    public const MAX_LIST = 20;

    private const KEY = '/^[a-z][a-z0-9_]{0,39}$/D';
    private const SECRET_KEY = '/pass|token|secret|authorization|cookie|credential|otp|mfa|private_key|api_key|session|signature/i';
    private const CLINICAL_KEY = '/diagnos|nota|note|soap|allerg|alergi|vital|prescri|medica|resultado_|lab_value|clinic|sintoma|symptom|tratamiento|treatment|patient_name|paciente_nombre/i';
    /** Semana 17 (H-GOB-17-01): datos personales identificables; la bitácora usa identificadores, no estos datos. */
    private const PERSONAL_KEY = '/email|correo|telefono|phone|celular|direccion|address|nacimiento|birth|(^|_)(dpi|cui|nit|pasaporte|passport)(_|$)/i';
    private const TOKEN_VALUE = '/^(bearer\s+\S+|ey[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]*|[A-Fa-f0-9]{40,}|[A-Za-z0-9+\/_-]{60,}={0,2})$/';
    private const EMAIL_VALUE = '/^[^@\s]+@[^@\s]+\.[^@\s]+$/';

    /**
     * @param array<mixed> $context
     * @return array{0: array<string, scalar|null|list<scalar|null>>, 1: list<string>}
     *         [contexto permitido, claves eliminadas]
     */
    public static function sanitize(array $context): array
    {
        $clean = [];
        $removed = [];
        foreach ($context as $key => $value) {
            $name = is_string($key) && preg_match(self::KEY, $key) === 1 ? $key : null;
            if ($name === null) {
                $removed[] = '[clave_invalida]';
                continue;
            }
            if (count($clean) >= self::MAX_KEYS
                || preg_match(self::SECRET_KEY, $name) === 1
                || preg_match(self::CLINICAL_KEY, $name) === 1
                || preg_match(self::PERSONAL_KEY, $name) === 1) {
                $removed[] = $name;
                continue;
            }
            $safe = self::safeValue($value);
            if ($safe === false) {
                $removed[] = $name;
                continue;
            }
            $clean[$name] = $safe;
        }
        ksort($clean);

        return [$clean, array_values(array_unique($removed))];
    }

    /** @return scalar|null|list<scalar|null>|false false si el valor no es admisible */
    private static function safeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            if (!array_is_list($value) || count($value) > self::MAX_LIST) {
                return false;
            }
            foreach ($value as $item) {
                if (is_array($item) || self::safeValue($item) === false) {
                    return false;
                }
            }

            return $value;
        }
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }
        if (!is_string($value)
            || mb_strlen($value) > self::MAX_STRING
            || preg_match('/[\x00-\x1F\x7F]/', $value) === 1
            || preg_match(self::TOKEN_VALUE, trim($value)) === 1
            || preg_match(self::EMAIL_VALUE, trim($value)) === 1) {
            return false;
        }

        return $value;
    }
}
