<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\ConfirmacionIncierta;
use MicroHis\TrasladosAltas\Application\FalloPersistencia;
use MicroHis\TrasladosAltas\Application\Operacion;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Domain\CamaDestino;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;

/**
 * Doble de prueba de HOSPITAL: la transacción es una instantánea que revertir() restaura. Sirve para probar el flujo y
 * los resultados; NO demuestra atomicidad ni concurrencia, que se prueban contra PostgreSQL.
 */
final class RegistroFalso implements RegistroInternacion
{
    public array $episodios = [];
    public array $estancias = [];
    public array $camas = [];
    /** @var list<string> */
    public array $llamadas = [];
    /** Punto donde simular la falla: iniciar, leer, escribir (primera escritura), escribir2 (segunda), confirmar o incierto. */
    public ?string $fallarEn = null;
    private ?array $instantanea = null;
    private int $escrituras = 0;

    public function __construct(private readonly DateTimeImmutable $t)
    {
    }

    public function estado(): array
    {
        return [$this->episodios, $this->estancias, $this->camas];
    }

    public function iniciar(): void
    {
        $this->paso('iniciar');
        $this->instantanea = $this->estado();
    }

    public function leerPorEstancia(string $estanciaId): ?EstadoInternacion
    {
        $this->paso('leer');
        $episodioId = $this->estancias[$estanciaId]['episodio'] ?? null;
        if ($episodioId === null) {
            return null;
        }
        $e = $this->episodios[$episodioId];
        $abierta = array_key_first(array_filter($this->estancias, static fn (array $s): bool => $s['episodio'] === $episodioId && $s['fin'] === null));

        return new EstadoInternacion($episodioId, $e['tipo'], $e['estado'], $e['inicio'], $abierta,
            $abierta === null ? null : $this->estancias[$abierta]['cama'], $abierta === null ? null : $this->estancias[$abierta]['inicio']);
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->t;
    }

    public function leerCama(string $camaId, DateTimeImmutable $t): ?CamaDestino
    {
        $this->paso('leer');
        if (!isset($this->camas[$camaId])) {
            return null;
        }
        $ocupada = array_filter($this->estancias, static fn (array $s): bool => $s['cama'] === $camaId && ($s['fin'] === null || $s['fin'] > $t));

        return new CamaDestino($camaId, $this->camas[$camaId], $ocupada === []);
    }

    public function cerrarEstancia(string $estanciaId, DateTimeImmutable $t): void
    {
        $this->paso('escribir');
        $this->estancias[$estanciaId]['fin'] = $t;
    }

    public function abrirEstancia(PlanTraslado $plan, string $actorRefId): string
    {
        $this->paso('escribir');
        $id = sprintf('00000000-0000-4000-8000-%012d', count($this->estancias) + 900);
        $this->estancias[$id] = ['episodio' => $plan->episodioId, 'cama' => $plan->camaDestinoId, 'inicio' => $plan->t, 'fin' => null, 'actor' => $actorRefId];

        return $id;
    }

    public function cerrarEpisodio(string $episodioId, DateTimeImmutable $t): void
    {
        $this->paso('escribir');
        $this->episodios[$episodioId]['estado'] = 'closed';
        $this->episodios[$episodioId]['fin'] = $t;
    }

    public function confirmar(): void
    {
        $this->llamadas[] = 'confirmar';
        if ($this->fallarEn === 'incierto') {
            throw new ConfirmacionIncierta('sin respuesta al COMMIT');
        }
        $this->paso('confirmar');
        $this->instantanea = null;
    }

    public function revertir(): void
    {
        $this->llamadas[] = 'revertir';
        if ($this->instantanea !== null) {
            [$this->episodios, $this->estancias, $this->camas] = $this->instantanea;
            $this->instantanea = null;
        }
    }

    private function paso(string $punto): void
    {
        $this->llamadas[] = $punto;
        $segunda = $punto === 'escribir' && ++$this->escrituras === 2 && $this->fallarEn === 'escribir2';
        if ($this->fallarEn === $punto || $segunda) {
            throw new FalloPersistencia("falla simulada en {$punto}");
        }
    }
}

/** Autorizador de prueba con una decisión fija; registra qué operación se consultó. */
final class AutorizadorFijo implements Autorizador
{
    public ?Operacion $consultada = null;

    public function __construct(private readonly bool $permite)
    {
    }

    public function permite(string $actorRefId, Operacion $operacion): bool
    {
        $this->consultada = $operacion;

        return $this->permite;
    }
}
