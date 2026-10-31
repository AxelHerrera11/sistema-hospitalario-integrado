<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Domain;

final class DatosCitaInvalidosException extends \RuntimeException {}
final class RangoFechaInvalidoException extends \RuntimeException {}
final class SolapeCitaException extends \RuntimeException {}
final class ErrorPersistenciaException extends \RuntimeException {}

/** Entidad Cita: exige actores, fechas DATE_ATOM y rango no invertido; guarda las fechas en UTC de ancho fijo. */
final class Cita
{
    public const SCHEDULED = 'scheduled';
    private const FORMATO_UTC = 'Y-m-d\TH:i:s\Z';

    private function __construct(
        public readonly string $id,
        public readonly string $paciente,
        public readonly string $doctor,
        public readonly string $desde,
        public readonly string $hasta,
        public readonly string $estado
    ) {
    }

    public static function agendar(string $id, string $paciente, string $doctor, string $desde, string $hasta): self
    {
        if (trim($paciente) === '' || trim($doctor) === '') {
            throw new DatosCitaInvalidosException('422 cita_datos_invalidos');
        }
        [$inicio, $fin] = [self::enUtc($desde), self::enUtc($hasta)];
        if ($inicio >= $fin) {
            throw new RangoFechaInvalidoException('409 cita_rango_invalido');
        }
        return new self($id, $paciente, $doctor, $inicio, $fin, self::SCHEDULED);
    }

    /** Convierte una fecha DATE_ATOM (`2026-10-27T10:00:00-06:00` o `...Z`) a UTC; cualquier otra cosa es 422. */
    public static function enUtc(string $valor): string
    {
        $fecha = \DateTimeImmutable::createFromFormat('!' . DATE_ATOM, $valor);
        $errores = \DateTimeImmutable::getLastErrors();
        if ($fecha === false || ($errores !== false && $errores['warning_count'] > 0)) {
            throw new DatosCitaInvalidosException('422 cita_fecha_invalida');
        }
        return $fecha->setTimezone(new \DateTimeZone('UTC'))->format(self::FORMATO_UTC);
    }

    public function seSolapaCon(string $paciente, string $doctor, string $desde, string $hasta): bool
    {
        $mismoActor = $this->paciente === $paciente || $this->doctor === $doctor;
        return $mismoActor && $this->desde < $hasta && $desde < $this->hasta;
    }
}
