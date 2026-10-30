<?php

declare(strict_types=1);

use MicroHis\ExpedienteMedico\Domain\CareEpisode;
use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;
use MicroHis\ExpedienteMedico\Domain\LongitudinalRecord;
use MicroHis\ExpedienteMedico\Domain\MedicalRecord;
use MicroHis\ExpedienteMedico\Domain\PatientSummary;

/** Pruebas UNITARIAS de las reglas de dominio (sin adaptadores). Trazas: RF-EXP-01, RF-EXP-03, RNF-EXP-02. */
echo PHP_EOL . '== Unitarias (dominio)' . PHP_EOL;

function violation(callable $action): ?string
{
    try {
        $action();
    } catch (ExpedienteRuleViolation $e) {
        return $e->status . ' ' . $e->getMessage();
    }

    return null;
}

$active = new PatientSummary(P1, 'P-0001', 'Ana Gómez', '1980-05-14', 'female', 'active');
$deceased = new PatientSummary(P3, 'P-0003', 'Marta Ríos', '1941-02-20', 'female', 'deceased');
$now = date_utc('2026-10-23 12:00:00+00:00');

// U-01 (RF-EXP-01): apertura de un paciente activo → expediente abierto, sin cierre, número EXP-<código>.
$opened = MedicalRecord::open('rec-1', $active, $now);
check('U-01 apertura de paciente activo: abierto, sin cierre y numerado', $opened->status === 'open' && $opened->closedAt === null && $opened->recordNumber === 'EXP-P-0001');

// U-02 (RF-EXP-01): un paciente no activo no recibe expediente nuevo.
check('U-02 paciente fallecido no recibe expediente (409)', violation(fn () => MedicalRecord::open('rec-2', $deceased, $now)) === '409 PACIENTE_NO_ACTIVO');

// U-03 (RNF-EXP-02): restricción medical_records_closure_check replicada en el dominio.
check('U-03 expediente cerrado sin fecha de cierre es inconsistente', violation(fn () => new MedicalRecord('r', P1, 'EXP-1', $now, 'closed')) === '500 EXPEDIENTE_INCONSISTENTE');
check('U-03b expediente cerrado antes de abrirse es inconsistente', violation(fn () => new MedicalRecord('r', P1, 'EXP-1', $now, 'closed', $now->modify('-1 day'))) === '500 EXPEDIENTE_INCONSISTENTE');
check('U-03c expediente abierto con fecha de cierre es inconsistente', violation(fn () => new MedicalRecord('r', P1, 'EXP-1', $now, 'open', $now)) === '500 EXPEDIENTE_INCONSISTENTE');

// U-04 (RNF-EXP-02): período del episodio (care_episodes_status_period_check).
check('U-04 episodio cerrado que termina antes de empezar es inconsistente', violation(fn () => new CareEpisode('e', P1, 'emergency', 'closed', $now, $now, 'Motivo', null)) === '500 EPISODIO_INCONSISTENTE');
check('U-04b episodio de tipo desconocido es inconsistente', violation(fn () => new CareEpisode('e', P1, 'domicilio', 'open', $now, null, 'Motivo', null)) === '500 EPISODIO_INCONSISTENTE');

// U-05 (RF-EXP-03): la vista longitudinal ordena del episodio más reciente al más antiguo y cuenta los abiertos.
$old = new CareEpisode('e-old', P1, 'outpatient', 'closed', $now->modify('-200 days'), $now->modify('-199 days'), 'Control', null);
$new = new CareEpisode('e-new', P1, 'inpatient', 'open', $now->modify('-2 days'), null, 'Ingreso', 'MED-INT');
$view = new LongitudinalRecord($opened, $active, [$old, $new]);
check('U-05 línea de tiempo ordenada (reciente primero) y 1 episodio abierto', $view->episodes[0]->id === 'e-new' && $view->openEpisodes() === 1);

// U-06 (RNF-EXP-02, integridad): un episodio de otro paciente nunca se mezcla en el expediente.
$foreign = new CareEpisode('e-x', P2, 'outpatient', 'open', $now, null, 'Consulta', null);
check('U-06 episodio de otro paciente rechaza toda la vista (500)', violation(fn () => new LongitudinalRecord($opened, $active, [$new, $foreign])) === '500 EPISODIO_DE_OTRO_PACIENTE');
check('U-06b expediente de otro paciente rechaza la vista (500)', violation(fn () => new LongitudinalRecord($opened, $deceased, [])) === '500 EXPEDIENTE_DE_OTRO_PACIENTE');

// U-07 (RNF-EXP-04): el código de paciente admite 1..40 caracteres seguros; fuera de eso, 422 antes de consultar.
check('U-07 código de 40 caracteres es válido', violation(fn () => PatientSummary::assertCode(str_repeat('A', 40))) === null);
check('U-07b código de 41 caracteres es inválido (422)', violation(fn () => PatientSummary::assertCode(str_repeat('A', 41))) === '422 CODIGO_PACIENTE_INVALIDO');
check("U-07c código con comilla (inyección) es inválido (422)", violation(fn () => PatientSummary::assertCode("P-1' OR '1'='1")) === '422 CODIGO_PACIENTE_INVALIDO');
