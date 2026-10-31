<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\AlertNotifier;
use MicroHis\SignosVitales\Application\CareEpisodeReader;
use MicroHis\SignosVitales\Application\GetVitalSign;
use MicroHis\SignosVitales\Application\PermissionChecker;
use MicroHis\SignosVitales\Application\RecordVitalSigns;
use MicroHis\SignosVitales\Application\VitalSignRepository;
use MicroHis\SignosVitales\Domain\GlucoseRule;
use MicroHis\SignosVitales\Domain\HeartRateRule;
use MicroHis\SignosVitales\Domain\NoAlertRule;
use MicroHis\SignosVitales\Domain\OxygenSaturationRule;
use MicroHis\SignosVitales\Domain\RespiratoryRateRule;
use MicroHis\SignosVitales\Domain\ThresholdEvaluator;
use MicroHis\SignosVitales\Presentation\ApiKernel;
use MicroHis\SignosVitales\Presentation\JsonView;
use MicroHis\SignosVitales\Presentation\Router;
use MicroHis\SignosVitales\Presentation\VitalSignController;

require __DIR__ . '/bootstrap.php';

/** Raíz de composición: el mismo MVC funciona con cualquier adaptador del Repository (PDO o InMemory). Reglas del perfil ilustrativo adulto-2026.1. */
function signos_vitales_router(CareEpisodeReader $episodes, VitalSignRepository $vitalSigns, AlertNotifier $notifier): Router
{
    $view = new JsonView();
    $evaluator = new ThresholdEvaluator([new HeartRateRule(), new OxygenSaturationRule(), new GlucoseRule(), new RespiratoryRateRule(), new NoAlertRule('weight'), new NoAlertRule('height')]);
    return new Router(new VitalSignController(new RecordVitalSigns($episodes, $evaluator, $vitalSigns, $notifier), new GetVitalSign($episodes, $vitalSigns), $view), $view);
}

/** Servidor API v1 (Semana 5): el mismo Router detrás de autenticación, permisos RBAC y trace_id. */
function signos_vitales_api(CareEpisodeReader $episodes, VitalSignRepository $vitalSigns, AlertNotifier $notifier, PermissionChecker $permissions, ?Closure $traceIds = null, ?Closure $log = null): ApiKernel
{
    return new ApiKernel(signos_vitales_router($episodes, $vitalSigns, $notifier), $permissions, new JsonView(), $traceIds, $log);
}
