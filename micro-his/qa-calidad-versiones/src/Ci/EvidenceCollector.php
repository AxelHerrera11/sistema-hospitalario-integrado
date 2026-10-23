<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

use MicroHis\QaCalidad\Domain\EvidenceRef;

/** Calcula la huella SHA-256 de archivos dentro del paquete de evidencias. */
final class EvidenceCollector
{
    private readonly string $root;

    public function __construct(string $root)
    {
        $real = realpath($root);
        if ($real === false || ! is_dir($real)) {
            throw new \RuntimeException("La carpeta de evidencias no existe: {$root}");
        }
        $this->root = rtrim(str_replace('\\', '/', $real), '/');
    }

    public function exists(string $relative): bool
    {
        return $this->resolve($relative) !== null;
    }

    public function collect(string $kind, string $relative): EvidenceRef
    {
        $full = $this->resolve($relative);
        if ($full === null) {
            throw new \RuntimeException("Evidencia fuera del paquete o inexistente: {$relative}");
        }

        $path = (string) preg_replace('#^(\./)+#', '', str_replace('\\', '/', $relative));

        return new EvidenceRef($kind, $path, hash_file('sha256', $full), (int) filesize($full));
    }

    /** Ruta absoluta solo si el archivo existe y está dentro de la raíz (sin escapar con ".."). */
    public function resolve(string $relative): ?string
    {
        $full = realpath($this->root.'/'.$relative);
        if ($full === false || ! is_file($full)) {
            return null;
        }
        $full = str_replace('\\', '/', $full);

        return str_starts_with($full, $this->root.'/') ? $full : null;
    }
}
