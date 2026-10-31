<?php

declare(strict_types=1);

namespace MicroHis\DashboardBase\Application;

use MicroHis\DashboardBase\Domain\Indicador;

/**
 * Read model de la pantalla de inicio. Cada tarjeta exige su permiso: sin permisos no hay tarjetas
 * (falla cerrada) y la interfaz muestra el estado vacío. SRP: solo compone; no lee tablas ni HTTP.
 */
final class ResumenTablero
{
    private const META_CANCELACIONES = 10.0;

    public function __construct(private readonly CitasDelDia $citas)
    {
    }

    /**
     * @param list<string> $permisos permisos resueltos por el servidor para la sesión
     * @return array{fecha: string, tarjetas: list<array<string, mixed>>}
     */
    public function ejecutar(string $fecha, array $permisos): array
    {
        $tarjetas = [];
        if (in_array('citas.ver', $permisos, true)) {
            $porEstado = $this->citas->porEstado($fecha);
            $total = array_sum($porEstado);
            $cancelaciones = new Indicador(
                'cancelaciones', 'Citas canceladas del dia (%)',
                $total === 0 ? 0.0 : 100 * ($porEstado['cancelled'] ?? 0) / $total,
                self::META_CANCELACIONES, Indicador::MENOR_MEJOR,
            );
            $tarjetas[] = [
                'clave' => 'citas_hoy', 'titulo' => 'Citas del dia', 'ruta' => '/citas',
                'total' => $total, 'por_estado' => $porEstado, 'indicador' => $cancelaciones->aArreglo(),
            ];
        }

        return ['fecha' => $fecha, 'tarjetas' => $tarjetas];
    }
}
