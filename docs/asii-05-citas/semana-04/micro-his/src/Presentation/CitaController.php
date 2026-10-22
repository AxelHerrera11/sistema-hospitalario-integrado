<?php

declare(strict_types=1);

namespace MicroHIS\Citas\Presentation;

use MicroHIS\Citas\Application\AgendarCita;
use MicroHIS\Citas\Domain\RangoFechaInvalidoException;
use MicroHIS\Citas\Domain\SolapeCitaException;

/**
 * Controlador MVC. No contiene SQL ni reglas de negocio: orquesta el caso de uso y
 * traduce errores de dominio a respuestas HTTP.
 */
final class CitaController
{
    public function __construct(private readonly AgendarCita $agendar)
    {
    }

    /**
     * @param array<string, string> $entrada
     * @return array{status:int, body:array<string, mixed>}
     */
    public function agendar(array $entrada): array
    {
        try {
            $cita = $this->agendar->ejecutar(
                (string) ($entrada['paciente'] ?? ''),
                (string) ($entrada['doctor'] ?? ''),
                (string) ($entrada['desde'] ?? ''),
                (string) ($entrada['hasta'] ?? '')
            );
        } catch (RangoFechaInvalidoException | SolapeCitaException $e) {
            return ['status' => (int) substr($e->getMessage(), 0, 3), 'body' => ['error' => substr($e->getMessage(), 4)]];
        }
        return ['status' => 201, 'body' => ['id' => $cita->id, 'estado' => $cita->estado]];
    }
}
