<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Domain;

final class RangoFechaInvalidoException extends \RuntimeException {}
final class SolapeCitaException extends \RuntimeException {}
final class ErrorPersistenciaException extends \RuntimeException {}

/**
 * Entidad Cita (modelo del dominio). Regla central: no admite rango de fecha invertido.
 */
final class Cita
{
    public const SCHEDULED = 'scheduled';

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
        if ($desde >= $hasta) {
            throw new RangoFechaInvalidoException('409 cita_rango_invalido');
        }
        return new self($id, $paciente, $doctor, $desde, $hasta, self::SCHEDULED);
    }

    public function seSolapaCon(string $paciente, string $doctor, string $desde, string $hasta): bool
    {
        $mismoActor = $this->paciente === $paciente || $this->doctor === $doctor;
        return $mismoActor && $this->desde < $hasta && $desde < $this->hasta;
    }
}
