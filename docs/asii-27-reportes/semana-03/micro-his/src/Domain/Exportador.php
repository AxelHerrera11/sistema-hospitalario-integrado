<?php

declare(strict_types=1);

namespace MicroHIS\Reportes\Domain;

/**
 * Contrato de exportacion. Aplicando OCP, se agregan formatos nuevos implementando esta
 * interfaz sin modificar el caso de uso ni los formatos existentes.
 */
interface Exportador
{
    public function formato(): string;

    /**
     * @param list<array<string, scalar>> $filas
     */
    public function exportar(array $filas): string;
}
