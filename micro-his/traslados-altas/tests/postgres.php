<?php

declare(strict_types=1);

// Pruebas del adaptador PDO contra una base HOSPITAL AISLADA ya migrada (database/migrations/hospital). Uso:
//   TRASLADOS_PG_DSN="pgsql:host=127.0.0.1;port=55433;dbname=his_hospital_traslados_test" TRASLADOS_PG_PASSWORD=... \
//   php micro-his/traslados-altas/tests/postgres.php
// Inserta datos ficticios que no se pueden borrar (bed_stays_delete_protection): úsela solo con una base desechable
// cuyo nombre termine en _test.

use MicroHis\TrasladosAltas\Application\AltaHospitalaria;
use MicroHis\TrasladosAltas\Application\ExitoConfirmado;
use MicroHis\TrasladosAltas\Application\FalloConfirmado;
use MicroHis\TrasladosAltas\Application\Operacion;
use MicroHis\TrasladosAltas\Application\RechazoConfirmado;
use MicroHis\TrasladosAltas\Application\TrasladoInterno;
use MicroHis\TrasladosAltas\Domain\Rechazo;
use MicroHis\TrasladosAltas\Domain\SolicitudAlta;
use MicroHis\TrasladosAltas\Domain\SolicitudTraslado;
use MicroHis\TrasladosAltas\Persistence\AutorizadorReferenciaActiva;
use MicroHis\TrasladosAltas\Persistence\PdoRegistroInternacion;

require __DIR__ . '/harness.php';
require_once __DIR__ . '/RegistroFalso.php';

$dsn = (string) getenv('TRASLADOS_PG_DSN');
if (preg_match('/dbname=\w+_test(;|$)/', $dsn) !== 1) {
    fwrite(STDERR, "TRASLADOS_PG_DSN debe apuntar a una base desechable cuyo nombre termine en _test.\n");
    exit(2);
}
$conectar = static fn (): PDO => new PDO($dsn, getenv('TRASLADOS_PG_USER') ?: 'postgres', (string) getenv('TRASLADOS_PG_PASSWORD'));
$pdo = $conectar();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$valor = static fn (string $sql, array $p = []) => (static function () use ($pdo, $sql, $p) {
    $s = $pdo->prepare($sql);
    $s->execute($p);

    return $s->fetchColumn();
})();

// Datos ficticios: dos episodios hospitalarios abiertos; E1 en la cama A y E2 en la cama D; B libre.
$sufijo = bin2hex(random_bytes(4));
$actor = $valor("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, synced_at)
    VALUES (gen_random_uuid(), gen_random_uuid(), 'Enfermera de prueba', 'enfermeria', now()) RETURNING local_user_ref_id");
$sala = $valor('INSERT INTO wards (code, name, location) VALUES (:c, :n, :l) RETURNING ward_id', ['c' => "S-{$sufijo}", 'n' => 'Sala de prueba', 'l' => 'Piso 1']);
$cama = static fn (string $codigo) => $valor('INSERT INTO beds (ward_id, code, type) VALUES (:w, :c, :t) RETURNING bed_id', ['w' => $sala, 'c' => $codigo, 't' => 'general']);
[$camaA, $camaB, $camaD] = [$cama('A'), $cama('B'), $cama('D')];
$internar = static function (string $camaId, string $codigo) use ($valor, $actor): array {
    $paciente = $valor("INSERT INTO local_patients (global_id, code, given_name, family_name, birth_date, sex)
        VALUES (gen_random_uuid(), :c, 'Paciente', 'Ficticio', '1990-01-01', 'unknown') RETURNING local_patient_id", ['c' => $codigo]);
    $episodio = $valor("INSERT INTO care_episodes (local_patient_id, type, started_at, reason) VALUES (:p, 'inpatient', now() - interval '2 days', 'Ingreso de prueba')
        RETURNING care_episode_id", ['p' => $paciente]);
    $estancia = $valor("INSERT INTO bed_stays (care_episode_id, bed_id, assigned_by_user_ref_id, started_at, reason)
        VALUES (:e, :b, :a, now() - interval '1 day', 'Ingreso') RETURNING bed_stay_id", ['e' => $episodio, 'b' => $camaId, 'a' => $actor]);

    return [$episodio, $estancia];
};
[$episodio1, $estancia1] = $internar($camaA, "P1-{$sufijo}");
[$episodio2] = $internar($camaD, "P2-{$sufijo}");
$registro = new PdoRegistroInternacion($conectar());
$permitir = new AutorizadorFijo(true);
$abiertas = static fn (string $episodio) => (int) $valor('SELECT count(*) FROM bed_stays WHERE care_episode_id = :e AND ended_at IS NULL', ['e' => $episodio]);

// Error de persistencia real: el actor no existe en local_user_refs (FK) y falla el INSERT después del UPDATE del origen.
$r = (new TrasladoInterno(new SolicitudTraslado($estancia1, $camaB, 'Aislamiento'), '00000000-0000-4000-8000-000000000000', $registro, $permitir))->ejecutar();
check('FK violada al abrir el destino → FalloConfirmado', $r instanceof FalloConfirmado);
check('PostgreSQL revirtió también el cierre del origen: E1 sigue abierta en A', $valor('SELECT ended_at IS NULL FROM bed_stays WHERE bed_stay_id = :s', ['s' => $estancia1]) === true);

$r = (new TrasladoInterno(new SolicitudTraslado($estancia1, $camaD, 'Aislamiento'), $actor, $registro, $permitir))->ejecutar();
check('destino con estancia abierta de otro episodio → DESTINO_OCUPADO', $r instanceof RechazoConfirmado && $r->motivo === Rechazo::DestinoOcupado);

$r = (new TrasladoInterno(new SolicitudTraslado($estancia1, $camaB, 'Aislamiento'), $actor, $registro, $permitir))->ejecutar();
$destino = $r instanceof ExitoConfirmado ? $r->liberacion->estanciaDestinoId : null;
check('traslado a B → ExitoConfirmado y persistido', $destino !== null && $abiertas($episodio1) === 1);
check('origen [inicio, T) y destino [T, ∞) contiguos en el mismo episodio', $valor('SELECT o.ended_at = d.started_at AND d.care_episode_id = o.care_episode_id
    AND d.bed_id = :b FROM bed_stays o, bed_stays d WHERE o.bed_stay_id = :o AND d.bed_stay_id = :d', ['o' => $estancia1, 'd' => $destino, 'b' => $camaB]) === true);
check('el episodio sigue abierto y beds.status no cambió', $valor('SELECT status FROM care_episodes WHERE care_episode_id = :e', ['e' => $episodio1]) === 'open'
    && (int) $valor("SELECT count(*) FROM beds WHERE ward_id = :w AND status <> 'available'", ['w' => $sala]) === 0);

$r = (new AltaHospitalaria(new SolicitudAlta($estancia1), $actor, $registro, $permitir))->ejecutar();
check('alta con la estancia ya cerrada → ESTANCIA_NO_VIGENTE', $r instanceof RechazoConfirmado && $r->motivo === Rechazo::EstanciaNoVigente);
$r = (new AltaHospitalaria(new SolicitudAlta((string) $destino), $actor, $registro, $permitir))->ejecutar();
check('alta → ExitoConfirmado; estancia y episodio cerrados con el mismo T', $r instanceof ExitoConfirmado && $valor("SELECT s.ended_at = e.ended_at AND e.status = 'closed'
    FROM bed_stays s JOIN care_episodes e USING (care_episode_id) WHERE s.bed_stay_id = :s", ['s' => $destino]) === true && $abiertas($episodio1) === 0);
check('el otro episodio no se tocó', $abiertas($episodio2) === 1);

// Autorización mínima del micro-HIS: actor activo sí; revocado, inexistente o con id inválido no (falla cerrada).
$autorizador = new AutorizadorReferenciaActiva($conectar());
$revocado = $valor("INSERT INTO local_user_refs (central_user_id, membership_id, display_name, role_code, status, synced_at)
    VALUES (gen_random_uuid(), gen_random_uuid(), 'Usuario revocado', 'enfermeria', 'revoked', now()) RETURNING local_user_ref_id");
check('actor activo → autorizado', $autorizador->permite($actor, Operacion::Traslado));
check('actor revocado, inexistente o con id inválido → no autorizado', !$autorizador->permite($revocado, Operacion::Alta)
    && !$autorizador->permite('00000000-0000-4000-8000-000000000000', Operacion::Alta) && !$autorizador->permite('no-es-uuid', Operacion::Alta));
$r = (new AltaHospitalaria(new SolicitudAlta($estancia1), $revocado, $registro, $autorizador))->ejecutar();
check('alta pedida por un actor revocado → NO_AUTORIZADO', $r instanceof RechazoConfirmado && $r->motivo === Rechazo::NoAutorizado);

// El mismo contrato del Repository que cumple el adaptador InMemory, ahora contra PostgreSQL, con un escenario nuevo.
require_once __DIR__ . '/contrato-registro.php';
contratoRegistro('PDO', static function () use ($valor, $actor, $internar, $conectar): array {
    $sala = $valor('INSERT INTO wards (code, name, location) VALUES (:c, :n, :l) RETURNING ward_id',
        ['c' => 'C-' . bin2hex(random_bytes(4)), 'n' => 'Sala del contrato', 'l' => 'Piso 2']);
    $cama = static fn (string $codigo, string $estado) => $valor('INSERT INTO beds (ward_id, code, type, status) VALUES (:w, :c, :t, :s) RETURNING bed_id',
        ['w' => $sala, 'c' => $codigo, 't' => 'general', 's' => $estado]);
    [$a, $b, $c, $d] = [$cama('A', 'available'), $cama('B', 'available'), $cama('C', 'maintenance'), $cama('D', 'available')];
    [$episodio, $estancia] = $internar($a, 'K1-' . bin2hex(random_bytes(4)));
    $internar($d, 'K2-' . bin2hex(random_bytes(4)));

    return [new PdoRegistroInternacion($conectar()), ['estancia' => $estancia, 'episodio' => $episodio, 'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d, 'actor' => $actor]];
});

finish();
