<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\VitalSignRepository;
use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\GlucoseRule;
use MicroHis\SignosVitales\Domain\HeartRateRule;
use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Domain\VitalSigns;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\PdoVitalSignRepository;

require __DIR__ . '/../bootstrap.php';

/** Contrato del Repository: la misma batería corre contra cada adaptador a través de la interfaz. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

/** @param Closure(): list<string> $auditEvents eventos de auditoría guardados, leídos por la vía propia de cada adaptador */
function verifyContract(string $adapter, VitalSignRepository $repo, Closure $auditEvents): void
{
    $vitals = new VitalSigns(['heart_rate' => 138.0, 'weight' => 70.0]);
    $alert = (new ThresholdEvaluator([new HeartRateRule(), new GlucoseRule()]))->evaluate($vitals);
    $id = $repo->record('ep1', 'dra.lopez', $vitals, '2026-10-14 08:30:00', $alert);
    $stored = $repo->find($id);
    check("$adapter: guarda y recupera la medición con sus valores", $stored?->episodeId === 'ep1' && $stored->recordedBy === 'dra.lopez' && $stored->vitals->values === $vitals->values);
    check("$adapter: una alerta deja registrado y alerta_generada", $auditEvents() === ['signo_vital.registrado', 'signo_vital.alerta_generada']);
    $repo->record('ep1', 'dra.lopez', new VitalSigns(['weight' => 70.0]), '2026-10-14 09:00:00', new Assessment(\MicroHis\SignosVitales\Domain\Severity::None, []));
    $repo->record('ep1', 'dra.lopez', $vitals, '2026-10-14 09:30:00', null);
    check("$adapter: sin alerta solo registra; sin perfil audita sin_evaluacion", array_slice($auditEvents(), 2) === ['signo_vital.registrado', 'signo_vital.registrado', 'signo_vital.sin_evaluacion']);
    check("$adapter: un id desconocido devuelve null", $repo->find('sv-inexistente') === null);
}

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
$pdo->exec("INSERT INTO care_episodes VALUES ('ep1', 'open', '1980-05-10')");
verifyContract('PDO', new PdoVitalSignRepository($pdo), static fn (): array => $pdo->query('SELECT event FROM local_access_audits')->fetchAll(PDO::FETCH_COLUMN));

$memory = new InMemoryVitalSignRepository();
verifyContract('InMemory', $memory, static fn (): array => array_column($memory->audits, 'event'));

$caido = new InMemoryVitalSignRepository(false);
try {
    $caido->record('ep1', 'dra.lopez', new VitalSigns(['weight' => 70.0]), '2026-10-14 08:30:00', null);
    check('InMemory: persistencia no disponible lanza RuntimeException', false);
} catch (RuntimeException) {
    check('InMemory: persistencia no disponible lanza RuntimeException y no guarda', $caido->audits === []);
}
echo 'Todas las pruebas de contrato pasaron.' . PHP_EOL;
