<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Referencia a una evidencia conservada (reporte JUnit, log, captura, resumen).
 * El contenido vive fuera del módulo (artefacto de CI); aquí se guarda su ruta
 * relativa y su SHA-256 para detectar alteraciones (RF-04, RNF-03).
 */
final readonly class EvidenceRef
{
    public const KINDS = ['junit', 'log', 'screenshot', 'summary'];

    public function __construct(
        public string $kind,
        public string $path,
        public string $sha256,
        public int $bytes,
    ) {
        if (! in_array($kind, self::KINDS, true)) {
            throw new InvalidQaInput('evidencia_tipo_invalido', 'El tipo de evidencia no es válido.');
        }
        // Ruta relativa al paquete de evidencias: sin esquema, sin raíz absoluta y sin "..".
        if (preg_match('#^(?!/)(?![a-zA-Z]:)(?!.*\.\.)(?!.*://)[A-Za-z0-9._/-]{1,200}$#', $path) !== 1) {
            throw new InvalidQaInput('evidencia_ruta_invalida', 'La evidencia debe ser una ruta relativa dentro del paquete de evidencias.');
        }
        if (preg_match('/^[0-9a-f]{64}$/', $sha256) !== 1) {
            throw new InvalidQaInput('evidencia_sin_huella', 'La evidencia necesita su SHA-256 en hexadecimal.');
        }
        if ($bytes < 1) {
            throw new InvalidQaInput('evidencia_vacia', 'Una evidencia vacía no demuestra nada.');
        }
    }

    /** Verifica que el contenido entregado sea exactamente el que se registró. */
    public function matches(string $content): bool
    {
        return strlen($content) === $this->bytes && hash_equals($this->sha256, hash('sha256', $content));
    }
}
