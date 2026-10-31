<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Application;

use MicroHIS\Citas\Domain\Cita;
use MicroHIS\Citas\Domain\CitaRepository;

/** Caso de uso de lectura (Semana 5) para `GET /citas`; depende solo de la interfaz del Dominio (DIP). */
final class ListarCitas
{
    public function __construct(private readonly CitaRepository $repositorio)
    {
    }

    /** @return list<array<string, string>> */
    public function ejecutar(string $paciente = ''): array
    {
        $citas = array_filter($this->repositorio->todas(), static fn (Cita $c): bool => $paciente === '' || $c->paciente === $paciente);
        return array_values(array_map(static fn (Cita $c): array => [
            'id' => $c->id, 'paciente' => $c->paciente, 'doctor' => $c->doctor,
            'desde' => $c->desde, 'hasta' => $c->hasta, 'estado' => $c->estado,
        ], $citas));
    }
}
