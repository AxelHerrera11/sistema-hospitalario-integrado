<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\AlertNotifier;
use MicroHis\SignosVitales\Application\CareEpisodeReader;
use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Application\RecordVitalSigns;
use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Domain\GlucoseRule;
use MicroHis\SignosVitales\Domain\HeartRateRule;
use MicroHis\SignosVitales\Domain\NoAlertRule;
use MicroHis\SignosVitales\Domain\OxygenSaturationRule;
use MicroHis\SignosVitales\Domain\RespiratoryRateRule;
use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Domain\VitalSignViolation;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;

// Caja blanca: cada rama y cada condición de RecordVitalSigns::execute y de ApiKernel::route, con espías sobre las colaboraciones.
require __DIR__ . '/support.php';

$espiaEpisodios = new class implements CareEpisodeReader {
    public int $llamadas = 0;
    public ?EpisodeContext $contexto = null;

    public function contextFor(string $userId, string $episodeId): ?EpisodeContext
    {
        $this->llamadas++;
        return $this->contexto;
    }
};
$canal = new class implements AlertNotifier {
    public int $avisos = 0;
    public bool $falla = false;

    public function notify(string $vitalSignId, Assessment $assessment): void
    {
        $this->avisos++;
        $this->falla && throw new RuntimeException('canal_caido');
    }
};
$reloj = static fn (): DateTimeImmutable => new DateTimeImmutable('2026-10-14 08:30:00');
$fallos = 0;
$repo = null;
/** Arma el caso de uso con los espías y devuelve el código de la violación o el resultado. */
$ejecutar = static function (array $signos, string $hora, ?EpisodeContext $contexto, bool $conManejador = true, bool $canalFalla = false) use ($espiaEpisodios, $canal, $reloj, &$fallos, &$repo): array|string {
    $repo = new InMemoryVitalSignRepository();
    [$espiaEpisodios->llamadas, $espiaEpisodios->contexto, $canal->avisos, $canal->falla] = [0, $contexto, 0, $canalFalla];
    $evaluador = new ThresholdEvaluator([new HeartRateRule(), new OxygenSaturationRule(), new GlucoseRule(), new RespiratoryRateRule(), new NoAlertRule('weight'), new NoAlertRule('height')]);
    $caso = new RecordVitalSigns($espiaEpisodios, $evaluador, $repo, $canal, $reloj, $conManejador ? static function () use (&$fallos): void {
        $fallos++;
    } : null);
    try {
        return $caso->execute('dra.lopez', 'ep1', $signos, $hora);
    } catch (VitalSignViolation $e) {
        return $e->getMessage() . ' ' . $e->status;
    }
};
$hora = '2026-10-14 08:30:00';
$adulto = new EpisodeContext(true, 45);

// RecordVitalSigns::execute — rama 1: el dominio rechaza antes de mirar la fecha o el episodio.
check('W-01 execute: signos imposibles -> VITAL_SIGN_IMPLAUSIBLE y no se consulta el episodio', $ejecutar(['temperature' => 50.0], $hora, $adulto) === 'VITAL_SIGN_IMPLAUSIBLE 422' && $espiaEpisodios->llamadas === 0);
// Rama 2 y condiciones 1, 2 y 3 del `if` de la fecha: formato imposible, desbordamiento y futura.
check('W-02 execute, condición 1: fecha con formato inválido -> MEASURED_AT_INVALID y sin consultar el episodio', $ejecutar(['heart_rate' => 80.0], '14/10/2026', $adulto) === 'MEASURED_AT_INVALID 422' && $espiaEpisodios->llamadas === 0);
check('W-03 execute, condición 2: fecha que se desborda (30 de febrero) -> MEASURED_AT_INVALID', $ejecutar(['heart_rate' => 80.0], '2026-02-30 08:30:00', $adulto) === 'MEASURED_AT_INVALID 422');
check('W-04 execute, condición 3: un segundo después de la tolerancia -> MEASURED_AT_INVALID; justo en el límite se acepta', $ejecutar(['heart_rate' => 80.0], '2026-10-14 08:35:01', $adulto) === 'MEASURED_AT_INVALID 422' && is_array($ejecutar(['heart_rate' => 80.0], '2026-10-14 08:35:00', $adulto)));
// Ramas 3 y 4: episodio y asignación.
check('W-05 execute: sin asignación activa -> NOT_TREATING_PHYSICIAN y no se guarda nada', $ejecutar(['heart_rate' => 80.0], $hora, null) === 'NOT_TREATING_PHYSICIAN 403' && $repo->audits === []);
check('W-06 execute: episodio cerrado -> EPISODE_CLOSED y no se guarda nada', $ejecutar(['heart_rate' => 80.0], $hora, new EpisodeContext(false, 45)) === 'EPISODE_CLOSED 409' && $repo->audits === []);
// Rama 5 y límite de la condición `patientAge >= ADULT_AGE`.
$r17 = $ejecutar(['heart_rate' => 138.0], $hora, new EpisodeContext(true, 17));
$r18 = $ejecutar(['heart_rate' => 138.0], $hora, new EpisodeContext(true, 18));
check('W-07 execute, límite de edad: 17 años -> THRESHOLDS_NOT_APPLICABLE sin aviso; 18 años -> se evalúa y alerta', $r17['code'] === 'THRESHOLDS_NOT_APPLICABLE' && $r17['notification'] === 'not_required' && $r18['code'] === 'ALERT_GENERATED');
// Ramas 6 a 9: la notificación.
$sinAlerta = $ejecutar(['heart_rate' => 80.0], $hora, $adulto);
check('W-08 execute: sin alerta -> not_required y el canal no se llama', $sinAlerta['notification'] === 'not_required' && $canal->avisos === 0);
$conAlerta = $ejecutar(['heart_rate' => 138.0], $hora, $adulto);
check('W-09 execute: con alerta y canal sano -> queued y el canal se llama una vez', $conAlerta['notification'] === 'queued' && $canal->avisos === 1);
$caido = $ejecutar(['heart_rate' => 138.0], $hora, $adulto, true, true);
check('W-10 execute: canal caído con manejador -> failed, se registra una vez y la medición queda guardada', $caido['notification'] === 'failed' && $fallos === 1 && count($repo->audits) === 2);
$sinManejador = $ejecutar(['heart_rate' => 138.0], $hora, $adulto, false, true);
check('W-11 execute: canal caído sin manejador -> failed igualmente, sin lanzar', $sinManejador['notification'] === 'failed' && $fallos === 1);
check('W-12 execute: el orden de los pasos es dominio, fecha, episodio, evaluación y guardado (la violación de dominio gana a la de fecha)', $ejecutar(['temperature' => 50.0], '14/10/2026', $adulto) === 'VITAL_SIGN_IMPLAUSIBLE 422');

// ApiKernel::route — una prueba por salida.
[$api] = servidor();
$ruta = '/api/v1/care-episodes/ep1/vital-signs';
check('W-13 route: ruta fuera de /api/v1 -> 404 ROUTE_NOT_FOUND, sin mirar la identidad', llamar($api, 'POST', '/care-episodes/ep1/vital-signs', '')['json']['code'] === 'ROUTE_NOT_FOUND');
check('W-14 route: identidad ausente, en blanco o no textual -> 401 UNAUTHENTICATED', llamar($api, 'POST', $ruta, '')['status'] === 401 && llamar($api, 'POST', $ruta, '   ')['status'] === 401
    && $api->handle(new MicroHis\SignosVitales\Presentation\Request('POST', $ruta, ['X-User-ID' => 7], []))['status'] === 401);
check('W-15 route: método o ruta sin permiso configurado -> 404 aunque haya identidad', llamar($api, 'PUT', $ruta)['status'] === 404 && llamar($api, 'GET', '/api/v1/care-episodes/ep1/vital-signs')['status'] === 404);
check('W-16 route: RBAC caído -> 503 AUTHORIZATION_UNAVAILABLE y no se guarda nada', ($c = llamar(($s = servidor(true))[0], 'POST', $ruta, 'dra.lopez', ['heart_rate' => 138, 'measured_at' => $hora]))['status'] === 503 && $s[1]->audits === [] && $c['json']['code'] === 'AUTHORIZATION_UNAVAILABLE');
check('W-17 route: permiso faltante -> 403 FORBIDDEN con el permiso; permiso presente -> llega al dominio', ($f = llamar($api, 'POST', $ruta, 'enf.ruiz', ['heart_rate' => 80, 'measured_at' => $hora]))['status'] === 403 && $f['json']['permission'] === 'signos_vitales.registrar'
    && llamar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80, 'measured_at' => $hora])['status'] === 201);
resumen('Caja blanca');
