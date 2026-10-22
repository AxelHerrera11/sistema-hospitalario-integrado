<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Domain;

final class RangoFechaInvalidoException extends \RuntimeException {}
final class TransicionInvalidaException extends \RuntimeException {}
final class SolapeCitaException extends \RuntimeException {}
final class ErrorPersistenciaException extends \RuntimeException {}

/**
 * Entidad Cita. Regla central: no puede existir con un rango de fecha invertido.
 */
final class Cita
{
    public const SCHEDULED = 'scheduled';
    public const CONFIRMED = 'confirmed';
    public const CANCELLED = 'cancelled';

    private function __construct(
        public readonly string $id,
        public readonly string $paciente,
        public readonly string $doctor,
        public readonly string $desde,
        public readonly string $hasta,
        private string $estado
    ) {
    }

    public static function crear(string $id, string $paciente, string $doctor, string $desde, string $hasta): self
    {
        if ($desde >= $hasta) {
            throw new RangoFechaInvalidoException('409 cita_rango_invalido');
        }
        return new self($id, $paciente, $doctor, $desde, $hasta, self::SCHEDULED);
    }

    public function estado(): string
    {
        return $this->estado;
    }

    public function confirmar(): void
    {
        if ($this->estado !== self::SCHEDULED) {
            throw new TransicionInvalidaException('409 cita_transicion_invalida');
        }
        $this->estado = self::CONFIRMED;
    }

    public function cancelar(): void
    {
        if (!in_array($this->estado, [self::SCHEDULED, self::CONFIRMED], true)) {
            throw new TransicionInvalidaException('409 cita_transicion_invalida');
        }
        $this->estado = self::CANCELLED;
    }

    public function seSolapaCon(string $paciente, string $doctor, string $desde, string $hasta): bool
    {
        $mismoActor = $this->paciente === $paciente || $this->doctor === $doctor;
        return $mismoActor && $this->desde < $hasta && $desde < $this->hasta;
    }
}
