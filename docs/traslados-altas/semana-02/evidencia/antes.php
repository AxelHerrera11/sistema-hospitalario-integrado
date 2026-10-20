<?php

declare(strict_types=1);

/*
 * ASII-08 · Semana 2 · EJEMPLO DIDÁCTICO del diseño antes (../solid-lsp.md); no existe en el
 * repositorio. Firmas iguales al diagrama: registrar(episodioId, estanciaEsperadaId, T).
 * Reutiliza el cálculo de cada operación: lo que se evalúa es la jerarquía y su contrato.
 */

namespace Antes;

class AltaHospitalaria
{
    public function __construct(protected \PuertoHospital $puerto) {}

    /** Contrato: ante ExitoConfirmado, la estancia termina en T, el episodio queda closed y sin estancias abiertas. */
    public function registrar(string $episodioId, string $estanciaEsperadaId, \DateTimeImmutable $t): \ResultadoOperacion
    {
        return $this->aplicar($episodioId, $estanciaEsperadaId, fn (array $e) => \calcularAlta($e, new \SolicitudAlta($estanciaEsperadaId), $t));
    }

    /** Aplica el cálculo con el T recibido si la estancia esperada pertenece al episodio indicado. */
    protected function aplicar(string $episodioId, string $estanciaEsperadaId, \Closure $calcular): \ResultadoOperacion
    {
        return \ejecutarCalculo($this->puerto, function (array $estado) use ($episodioId, $estanciaEsperadaId, $calcular) {
            $episodioDeLaEstancia = $estado['estancias'][$estanciaEsperadaId]['episodio'] ?? null;
            return $episodioDeLaEstancia === $episodioId ? $calcular($estado) : 'ESTANCIA_DESACTUALIZADA';
        });
    }
}

/** Herencia incorrecta: sobrescribe registrar() con un traslado, que no cumple las postcondiciones de un alta. */
final class TrasladoInterno extends AltaHospitalaria
{
    public function __construct(\PuertoHospital $puerto, private string $camaDestinoId, private string $reason)
    {
        parent::__construct($puerto);
    }

    public function registrar(string $episodioId, string $estanciaEsperadaId, \DateTimeImmutable $t): \ResultadoOperacion
    {
        $solicitud = new \SolicitudTraslado($estanciaEsperadaId, $this->camaDestinoId, $this->reason);
        return $this->aplicar($episodioId, $estanciaEsperadaId, fn (array $e) => \calcularTraslado($e, $solicitud, $t));
    }
}

final class CierreDeEpisodio
{
    /** Confía en el contrato de AltaHospitalaria: tras el éxito, el episodio quedó cerrado. */
    public function finalizar(AltaHospitalaria $operacion, string $episodioId, string $estanciaEsperadaId, \DateTimeImmutable $t): string
    {
        $resultado = $operacion->registrar($episodioId, $estanciaEsperadaId, $t);
        return $resultado instanceof \ExitoConfirmado ? "episodio $episodioId cerrado, sin cama" : 'sin cierre';
    }
}
