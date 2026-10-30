<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Domain;

/**
 * Datos mínimos del paciente que necesita el expediente (columnas de local_patients). El dueño del paciente es el
 * módulo Pacientes: aquí solo se leen, nunca se modifican.
 */
final readonly class PatientSummary
{
    /** Mismo alfabeto que admite un identificador de recurso; 40 caracteres como máximo. */
    public const CODE = '/^[A-Za-z0-9][A-Za-z0-9_-]{0,39}$/D';
    private const STATUSES = ['active', 'inactive', 'deceased'];

    public function __construct(
        public string $id,
        public string $code,
        public string $displayName,
        public string $birthDate,
        public string $sex,
        public string $status,
    ) {
        self::assertCode($code);
        if ($id === '' || !in_array($status, self::STATUSES, true)) {
            throw new ExpedienteRuleViolation('PACIENTE_INCONSISTENTE', 500);
        }
    }

    /** Valida el código recibido en la ruta antes de consultar cualquier almacén. */
    public static function assertCode(string $code): void
    {
        if (preg_match(self::CODE, $code) !== 1) {
            throw new ExpedienteRuleViolation('CODIGO_PACIENTE_INVALIDO', 422);
        }
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
