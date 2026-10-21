<?php

declare(strict_types=1);

use MicroHis\TrasladosAltas\Persistence\InMemoryRegistroInternacion;

require_once __DIR__ . '/contrato-registro.php';

// El adaptador InMemory cumple el mismo contrato que el PDO (tests/postgres.php ejecuta contratoRegistro con PostgreSQL).
contratoRegistro('InMemory', static function (): array {
    $r = new InMemoryRegistroInternacion();
    $inicio = new DateTimeImmutable('-1 day');
    $r->episodios = [EPISODIO => ['tipo' => 'inpatient', 'estado' => 'open', 'inicio' => new DateTimeImmutable('-2 days'), 'fin' => null],
        'otro' => ['tipo' => 'inpatient', 'estado' => 'open', 'inicio' => new DateTimeImmutable('-2 days'), 'fin' => null]];
    $r->estancias = [ESTANCIA => ['episodio' => EPISODIO, 'cama' => CAMA_A, 'inicio' => $inicio, 'fin' => null],
        'estancia-d' => ['episodio' => 'otro', 'cama' => CAMA_D, 'inicio' => $inicio, 'fin' => null]];
    $r->camas = [CAMA_A => 'available', CAMA_B => 'available', CAMA_C => 'maintenance', CAMA_D => 'available'];

    return [$r, ['estancia' => ESTANCIA, 'episodio' => EPISODIO, 'a' => CAMA_A, 'b' => CAMA_B, 'c' => CAMA_C, 'd' => CAMA_D, 'actor' => ACTOR]];
});
