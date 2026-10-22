<?php

declare(strict_types=1);

namespace MicroHis\Gobernanza\Application;

use MicroHis\Gobernanza\Domain\AuditEvent;
use MicroHis\Gobernanza\Domain\AuditFilter;
use MicroHis\Gobernanza\Domain\AuditOutcome;

/**
 * Convierte los parámetros públicos de consulta en un AuditFilter válido.
 *
 * Parámetros: usuario, modulo, accion, desde, hasta, resultado, correlacion,
 * pagina, por_pagina. Cualquier otro parámetro se rechaza (422) para que un
 * filtro mal escrito no devuelva resultados sin filtrar. Los valores vacíos se
 * tratan como ausentes. Fechas: "AAAA-MM-DD" (día completo en la zona horaria
 * del servidor) o "AAAA-MM-DDTHH:MM[:SS][Z|±HH:MM]"; "hasta" es inclusivo.
 */
final class AuditFilterParser
{
    public const PARAMETERS = ['usuario', 'modulo', 'accion', 'desde', 'hasta', 'resultado', 'correlacion', 'pagina', 'por_pagina'];

    private const DATE = '/^\d{4}-\d{2}-\d{2}$/D';
    private const DATETIME = '/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2})(:\d{2})?(Z|[+-]\d{2}:\d{2})?$/D';

    private readonly \DateTimeZone $zone;

    public function __construct(string $timezone = 'America/Guatemala')
    {
        $this->zone = new \DateTimeZone($timezone);
    }

    /**
     * @param array<mixed> $input
     * @throws InvalidAuditFilter
     */
    public function parse(array $input): AuditFilter
    {
        $errors = [];
        foreach (array_keys($input) as $key) {
            if (!in_array($key, self::PARAMETERS, true)) {
                // H-GOB-12: el nombre recibido se sanea antes de devolverlo al cliente.
                $errors[is_string($key) ? substr((string) preg_replace('/[^a-z0-9_]/', '_', strtolower($key)), 0, 40) : '[clave]'] = 'parametro_no_permitido';
            }
        }
        $value = static function (string $key) use ($input, &$errors): ?string {
            if (!array_key_exists($key, $input) || $input[$key] === null || $input[$key] === '') {
                return null;
            }
            if (!is_string($input[$key]) && !is_int($input[$key])) {
                $errors[$key] = 'tipo_invalido';

                return null;
            }

            return (string) $input[$key];
        };

        $actor = $this->match($value('usuario'), AuditEvent::IDENTIFIER, 'usuario', $errors);
        $module = $this->match($value('modulo'), AuditEvent::MODULE, 'modulo', $errors);
        $action = $value('accion');
        if ($action !== null && (preg_match(AuditEvent::ACTION, $action) !== 1 || strlen($action) > 80)) {
            $errors['accion'] = 'formato_invalido';
            $action = null;
        }
        $correlation = $this->match($value('correlacion'), AuditEvent::CORRELATION, 'correlacion', $errors);
        $outcomeRaw = $value('resultado');
        $outcome = null;
        if ($outcomeRaw !== null && in_array($outcomeRaw, AuditOutcome::recordable(), true)) {
            $outcome = AuditOutcome::from($outcomeRaw);
        } elseif ($outcomeRaw !== null) {
            $errors['resultado'] = 'valor_no_permitido';
        }
        $from = $this->instant($value('desde'), false, 'desde', $errors);
        $until = $this->instant($value('hasta'), true, 'hasta', $errors);
        if ($from !== null && $until !== null && $from >= $until) {
            $errors['hasta'] = 'anterior_a_desde';
        }
        $page = $this->integer($value('pagina'), 1, 1, AuditFilter::MAX_PAGE, 'pagina', $errors);
        $perPage = $this->integer($value('por_pagina'), AuditFilter::DEFAULT_PER_PAGE, 1, AuditFilter::MAX_PER_PAGE, 'por_pagina', $errors);

        if ($errors !== []) {
            ksort($errors);
            throw new InvalidAuditFilter($errors);
        }

        return new AuditFilter($actor, $module, $action, $from, $until, $outcome, $correlation, $page, $perPage);
    }

    /** @param array<string, string> $errors */
    private function match(?string $value, string $pattern, string $key, array &$errors): ?string
    {
        if ($value !== null && preg_match($pattern, $value) !== 1) {
            $errors[$key] = 'formato_invalido';

            return null;
        }

        return $value;
    }

    /** @param array<string, string> $errors */
    private function integer(?string $value, int $default, int $min, int $max, string $key, array &$errors): int
    {
        if ($value === null) {
            return $default;
        }
        if (preg_match('/^[1-9]\d{0,5}$/D', $value) !== 1 || (int) $value < $min || (int) $value > $max) {
            $errors[$key] = 'fuera_de_rango';

            return $default;
        }

        return (int) $value;
    }

    /**
     * Devuelve el instante en UTC. Para "hasta" devuelve el límite exclusivo
     * (fin del día, minuto o segundo indicado + 1).
     *
     * @param array<string, string> $errors
     */
    private function instant(?string $value, bool $upper, string $key, array &$errors): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        $utc = new \DateTimeZone('UTC');
        if (preg_match(self::DATE, $value) === 1) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, $this->zone);
            if ($date === false || $date->format('Y-m-d') !== $value) {
                $errors[$key] = 'fecha_invalida';

                return null;
            }

            return ($upper ? $date->modify('+1 day') : $date)->setTimezone($utc);
        }
        if (preg_match(self::DATETIME, $value, $m) === 1) {
            $seconds = ($m[3] ?? '') !== '';
            $zone = ($m[4] ?? '') === '' ? $this->zone : new \DateTimeZone($m[4] === 'Z' ? 'UTC' : $m[4]);
            $text = $m[1] . 'T' . $m[2] . ($seconds ? $m[3] : ':00');
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s', $text, $zone);
            if ($date === false || $date->format('Y-m-d\TH:i:s') !== $text) {
                $errors[$key] = 'fecha_invalida';

                return null;
            }

            return ($upper ? $date->modify($seconds ? '+1 second' : '+1 minute') : $date)->setTimezone($utc);
        }
        $errors[$key] = 'fecha_invalida';

        return null;
    }
}
