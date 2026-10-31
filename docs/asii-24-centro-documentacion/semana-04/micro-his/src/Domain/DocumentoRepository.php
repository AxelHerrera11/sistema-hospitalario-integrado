<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Domain;

// Interfaz Repository en el limite del Dominio (patron Repository).
interface DocumentoRepository
{
    public function todos(): array;

    public function guardar(Documento $documento): void;
}
