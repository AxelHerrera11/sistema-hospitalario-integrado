<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Application\Autorizador;
use MicroHis\TrasladosAltas\Application\ConfirmacionIncierta;
use MicroHis\TrasladosAltas\Application\FalloPersistencia;
use MicroHis\TrasladosAltas\Application\Operacion;
use MicroHis\TrasladosAltas\Persistence\InMemoryRegistroInternacion;

/**
 * Doble de prueba de HOSPITAL: el adaptador en memoria más un registro de llamadas y fallas inyectables. Sirve para
 * probar el flujo y los resultados; NO demuestra atomicidad ni concurrencia, que se prueban contra PostgreSQL.
 */
final class RegistroFalso extends InMemoryRegistroInternacion
{
    /** @var list<string> */
    public array $llamadas = [];
    /** Punto donde simular la falla: iniciar, leer, escribir (primera escritura), escribir2 (segunda), confirmar o incierto. */
    public ?string $fallarEn = null;
    private int $escrituras = 0;

    protected function antes(string $punto): void
    {
        $this->llamadas[] = $punto;
        if ($punto === 'revertir') {
            return;
        }
        if ($punto === 'confirmar' && $this->fallarEn === 'incierto') {
            throw new ConfirmacionIncierta('sin respuesta al COMMIT');
        }
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
