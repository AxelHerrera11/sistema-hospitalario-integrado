<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Domain\Uuid;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryPermissionChecker;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\ApiKernel;
use MicroHis\SignosVitales\Presentation\Request;

require __DIR__ . '/../app.php';

/** Servidor API v1 con adaptadores InMemory. dra.lopez registra y ve; enf.ruiz solo ve; ep1 es de un adulto y solo dra.lopez lo atiende. */
function api(bool $rbacDown = false, array &$accessLog = []): array
{
    $permissions = new InMemoryPermissionChecker(['dra.lopez' => ['signos_vitales.registrar', 'signos_vitales.ver'], 'enf.ruiz' => ['signos_vitales.ver']], $rbacDown);
    $episodes = new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, 45)]]);
    $repository = new InMemoryVitalSignRepository();
    $notifier = new QueuedAlertNotifier();
    $log = static function (array $entry) use (&$accessLog): void {
        $accessLog[] = $entry;
    };
    return [signos_vitales_api($episodes, $repository, $notifier, $permissions, log: $log), $notifier, $repository];
}

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

/** @return array{status: int, headers: array<string, string>, json: array} */
function call(ApiKernel $api, string $method, string $path, string $actor = 'dra.lopez', array $body = []): array
{
    $r = $api->handle(new Request($method, $path, $actor === '' ? [] : ['X-User-ID' => $actor], $body));
    return $r + ['json' => json_decode($r['body'], true)];
}

$vitals = ['heart_rate' => 138, 'oxygen_saturation' => 94, 'measured_at' => '2026-10-14 08:30:00'];
$entries = [];
[$api, $notifier, $repository] = api(accessLog: $entries);

$r = call($api, 'POST', '/api/v1/care-episodes/ep1/vital-signs', body: $vitals);
check('servidor: POST con signos_vitales.registrar -> 201 con trace_id en cuerpo y encabezado', $r['status'] === 201 && $r['json']['severity'] === 'critical'
    && preg_match(Uuid::PATTERN, $r['json']['trace_id']) === 1 && $r['headers']['X-Trace-Id'] === $r['json']['trace_id'] && count($notifier->queue) === 1);
check('observabilidad: el id de la medición es UUID v4', preg_match(Uuid::PATTERN, $r['json']['vital_sign_id']) === 1);
$id = $r['json']['vital_sign_id'];

$g = call($api, 'GET', "/api/v1/vital-signs/$id");
check('servidor: GET con signos_vitales.ver y asignación activa -> 200 con los signos', $g['status'] === 200 && $g['json']['values'] == ['heart_rate' => 138, 'oxygen_saturation' => 94] && $g['json']['care_episode_id'] === 'ep1');
check('seguridad: GET de quien no atiende el episodio -> 403 NOT_TREATING_PHYSICIAN (enfermería tiene el permiso, no la asignación)', call($api, 'GET', "/api/v1/vital-signs/$id", 'enf.ruiz')['json']['code'] === 'NOT_TREATING_PHYSICIAN');
check('servidor: GET de una medición inexistente -> 404 VITAL_SIGN_NOT_FOUND', call($api, 'GET', '/api/v1/vital-signs/' . Uuid::v4())['json']['code'] === 'VITAL_SIGN_NOT_FOUND');

check('seguridad: sin X-User-ID -> 401 UNAUTHENTICATED con trace_id', ($u = call($api, 'POST', '/api/v1/care-episodes/ep1/vital-signs', '', $vitals))['status'] === 401 && $u['json']['code'] === 'UNAUTHENTICATED' && isset($u['json']['trace_id']));
$f = call($api, 'POST', '/api/v1/care-episodes/ep1/vital-signs', 'enf.ruiz', $vitals);
check('seguridad: enfermería sin signos_vitales.registrar -> 403 FORBIDDEN con el permiso faltante', $f['status'] === 403 && $f['json']['code'] === 'FORBIDDEN' && $f['json']['permission'] === 'signos_vitales.registrar');
check('seguridad: usuario sin permisos -> 403 en GET', call($api, 'GET', "/api/v1/vital-signs/$id", 'intruso')['status'] === 403);
check('seguridad: dra.lopez en un episodio ajeno -> 403 NOT_TREATING_PHYSICIAN', call($api, 'POST', '/api/v1/care-episodes/ep9/vital-signs', body: $vitals)['json']['code'] === 'NOT_TREATING_PHYSICIAN');

check('versionado: ruta sin /api/v1 -> 404 ROUTE_NOT_FOUND', call($api, 'POST', '/care-episodes/ep1/vital-signs', body: $vitals)['status'] === 404);
check('seguridad: ruta sin permiso configurado (DELETE) no se expone -> 404', call($api, 'DELETE', "/api/v1/vital-signs/$id")['status'] === 404);
$j = $api->handle(new Request('POST', '/api/v1/care-episodes/ep1/vital-signs', ['X-User-ID' => 'dra.lopez']), false);
check('validación: JSON malformado -> 422 PAYLOAD_INVALID con trace_id', $j['status'] === 422 && json_decode($j['body'], true)['code'] === 'PAYLOAD_INVALID' && isset($j['headers']['X-Trace-Id']));

[$caido] = api(true);
$cola = count($notifier->queue);
check('resiliencia: RBAC caído -> 503 AUTHORIZATION_UNAVAILABLE (falla cerrada, sin guardar)', ($c = call($caido, 'POST', '/api/v1/care-episodes/ep1/vital-signs', body: $vitals))['status'] === 503 && $c['json']['code'] === 'AUTHORIZATION_UNAVAILABLE' && count($notifier->queue) === $cola);

check('observabilidad: la bitácora registra trace_id, actor, método, ruta y estado, sin el cuerpo', count($entries) >= 8 && array_keys($entries[0]) === ['trace_id', 'actor', 'method', 'path', 'status']
    && !str_contains(json_encode($entries), 'heart_rate') && !str_contains(json_encode($entries), '138') && $entries[0]['trace_id'] === $r['json']['trace_id']);
echo 'Todas las pruebas pasaron.' . PHP_EOL;
