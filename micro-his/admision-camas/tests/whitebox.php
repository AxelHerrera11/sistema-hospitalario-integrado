<?php

declare(strict_types=1);

use MicroHis\Admision\Application\AdmissionRepository;
use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Domain\Admission;
use MicroHis\Admision\Domain\DomainRuleViolation;
use MicroHis\Admision\Persistence\InMemoryAdmissionRepository;

require __DIR__ . '/../bootstrap.php';

/**
 * Caja blanca: un caso por rama de Admission::admit (B1–B6) y de AdmitPatient (B7–B9), incluida la carrera
 * por la misma cama entre la lectura y el guardado. La tabla de ramas está en docs/admision-camas/semana-15/README.md.
 */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function rule(array $bed = [], ?string $type = 'adult', string $reason = 'Neumonía', bool $admitted = false): ?string
{
    try {
        Admission::admit('E-1', 'S-1', 'P-1', array_merge(['bed_id' => 'B-1', 'type' => 'adult', 'status' => 'available'], $bed), $type, $reason, $admitted, '2026-10-18T09:00:00-06:00');
        return null;
    } catch (DomainRuleViolation $v) {
        return "{$v->rule}:{$v->field}";
    }
}
check('B1 motivo solo con espacios → REASON_REQUIRED', rule(reason: '   ') === 'REASON_REQUIRED:reason');
check('B1 motivo de 501 caracteres → TEXT_TOO_LONG; 500 con acentos es válido', rule(reason: str_repeat('x', 501)) === 'TEXT_TOO_LONG:reason' && rule(reason: str_repeat('ó', 500)) === null);
check('B2 paciente ya admitido → PATIENT_ALREADY_ADMITTED (antes que la cama)', rule(['status' => 'occupied'], admitted: true) === 'PATIENT_ALREADY_ADMITTED:patient_id');
foreach (['occupied', 'maintenance', 'inactive'] as $status) {
    check("B3 cama {$status} → BED_NOT_AVAILABLE", rule(['status' => $status]) === 'BED_NOT_AVAILABLE:bed_id');
}
check('B4 tipo pedido distinto del de la cama → BED_TYPE_MISMATCH', rule(['type' => 'pediatric']) === 'BED_TYPE_MISMATCH:bed_type');
check('B5 sin tipo pedido → acepta cualquier tipo', rule(['type' => 'pediatric'], type: null) === null);
$a = Admission::admit('E-1', 'S-1', 'P-1', ['bed_id' => 'B-1', 'type' => 'adult', 'status' => 'available'], 'adult', '  Neumonía  ', false, 'T');
check('B6 válida: recorta el motivo y conserva cama y hora', $a->reason === 'Neumonía' && $a->bedId === 'B-1' && $a->startedAt === 'T');

$beds = ['B-1' => ['bed_id' => 'B-1', 'code' => 'MED-101', 'type' => 'adult', 'status' => 'available']];
$admit = fn (AdmissionRepository $r, array $data) => (new AdmitPatient($r, fn () => 'T', fn () => uniqid()))->execute($data + ['patient_id' => 'P-1', 'bed_id' => 'B-1', 'reason' => 'x'], 'admision1');
$raises = function (Closure $call): ?string { try { $call(); return null; } catch (DomainRuleViolation $v) { return $v->rule; } };
check('B7 paciente inexistente → PATIENT_NOT_FOUND (antes de mirar la cama)', $raises(fn () => $admit(new InMemoryAdmissionRepository([], $beds), [])) === 'PATIENT_NOT_FOUND');
check('B8 cama inexistente → BED_NOT_FOUND', $raises(fn () => $admit(new InMemoryAdmissionRepository(['P-1'], $beds), ['bed_id' => 'B-9'])) === 'BED_NOT_FOUND');
// B9: la lectura ve la cama libre, pero otra admisión la ocupa antes del guardado. El adaptador rechaza (duplicado) y
// el caso de uso lo traduce a BED_NOT_AVAILABLE, sin dejar estancia.
$stale = new class (new InMemoryAdmissionRepository(['P-1', 'P-2'], $beds)) implements AdmissionRepository {
    public function __construct(public InMemoryAdmissionRepository $inner) {}
    public function patientExists(string $p): bool { return $this->inner->patientExists($p); }
    public function hasOpenInpatientEpisode(string $p): bool { return $this->inner->hasOpenInpatientEpisode($p); }
    public function bed(string $id): ?array { return ['bed_id' => $id, 'type' => 'adult', 'status' => 'available']; } // lectura vieja
    public function save(Admission $a, string $actor): void { $this->inner->save($a, $actor); }
    public function availableBeds(string $type): array { return $this->inner->availableBeds($type); }
};
$admit($stale, ['patient_id' => 'P-2']);
check('B9 carrera por la misma cama → BED_NOT_AVAILABLE y una sola estancia', $raises(fn () => $admit($stale, [])) === 'BED_NOT_AVAILABLE' && count($stale->inner->admissions) === 1);
echo 'Todas las pruebas de caja blanca pasaron.' . PHP_EOL;
