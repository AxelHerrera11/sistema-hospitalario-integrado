<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Domain;

/** Prueba solicitada dentro de una orden; guarda una instantánea del catálogo para no depender de él al leer. */
final readonly class LabOrderItem
{
    public function __construct(
        public string $labTestId,
        public string $code,
        public string $name,
    ) {
    }

    /** @return array{lab_test_id: string, code: string, name: string} */
    public function toArray(): array
    {
        return ['lab_test_id' => $this->labTestId, 'code' => $this->code, 'name' => $this->name];
    }
}
