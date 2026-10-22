<?php

declare(strict_types=1);

namespace MicroHis\TrasladosAltas\Persistence;

use DateTimeImmutable;
use MicroHis\TrasladosAltas\Application\RegistroInternacion;
use MicroHis\TrasladosAltas\Domain\CamaDestino;
use MicroHis\TrasladosAltas\Domain\EstadoInternacion;
use MicroHis\TrasladosAltas\Domain\PlanTraslado;

/**
 * Adaptador en memoria del Repository, para demostraciones y pruebas sin base. La transacción es una instantánea
 * que revertir() restaura. NO ofrece atomicidad entre procesos, concurrencia ni las restricciones de PostgreSQL.
 */
class InMemoryRegistroInternacion implements RegistroInternacion
{
    /** @var array<string, array{tipo: string, estado: string, inicio: DateTimeImmutable, fin: ?DateTimeImmutable}> */
    public array $episodios = [];
    /** @var array<string, array{episodio: string, cama: string, inicio: DateTimeImmutable, fin: ?DateTimeImmutable, actor?: string}> */
    public array $estancias = [];
    /** @var array<string, string> beds.status por cama */
    public array $camas = [];
    private ?array $instantanea = null;

    public function __construct(private readonly ?DateTimeImmutable $relojFijo = null)
    {
    }

    /** @return array{0: array, 1: array, 2: array} estado observable, para comparar antes y después */
    public function estado(): array
    {
        return [$this->episodios, $this->estancias, $this->camas];
    }

    public function iniciar(): void
    {
        $this->antes('iniciar');
        $this->instantanea = $this->estado();
    }

    public function leerPorEstancia(string $estanciaId): ?EstadoInternacion
    {
        $this->antes('leer');
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
        return $this->relojFijo ?? new DateTimeImmutable();
    }

    public function leerCama(string $camaId, DateTimeImmutable $t): ?CamaDestino
    {
        $this->antes('leer');
        if (!isset($this->camas[$camaId])) {
            return null;
        }
        $ocupada = array_filter($this->estancias, static fn (array $s): bool => $s['cama'] === $camaId && ($s['fin'] === null || $s['fin'] > $t));

        return new CamaDestino($camaId, $this->camas[$camaId], $ocupada === []);
    }

    public function cerrarEstancia(string $estanciaId, DateTimeImmutable $t): void
    {
        $this->antes('escribir');
        $this->estancias[$estanciaId]['fin'] = $t;
    }

    public function abrirEstancia(PlanTraslado $plan, string $actorRefId): string
    {
        $this->antes('escribir');
        // uuid v4 aleatorio, con el mismo formato que gen_random_uuid() de HOSPITAL.
        $h = bin2hex(random_bytes(16));
        $id = sprintf('%s-%s-4%s-8%s-%s', substr($h, 0, 8), substr($h, 8, 4), substr($h, 13, 3), substr($h, 17, 3), substr($h, 20, 12));
        $this->estancias[$id] = ['episodio' => $plan->episodioId, 'cama' => $plan->camaDestinoId, 'inicio' => $plan->t, 'fin' => null, 'actor' => $actorRefId];

        return $id;
    }

    public function cerrarEpisodio(string $episodioId, DateTimeImmutable $t): void
    {
        $this->antes('escribir');
        $this->episodios[$episodioId]['estado'] = 'closed';
        $this->episodios[$episodioId]['fin'] = $t;
    }

    public function confirmar(): void
    {
        $this->antes('confirmar');
        $this->instantanea = null;
    }

    public function revertir(): void
    {
        $this->antes('revertir');
        if ($this->instantanea !== null) {
            [$this->episodios, $this->estancias, $this->camas] = $this->instantanea;
            $this->instantanea = null;
        }
    }

    /** Punto de extensión para pruebas (registrar llamadas o simular fallas); para 'revertir' no debe lanzar. */
    protected function antes(string $punto): void
    {
    }
}
