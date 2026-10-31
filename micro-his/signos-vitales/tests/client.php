<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Client\HttpTransport;
use MicroHis\SignosVitales\Client\KernelTransport;
use MicroHis\SignosVitales\Client\StreamTransport;
use MicroHis\SignosVitales\Client\VitalSignApiClient;
use MicroHis\SignosVitales\Client\VitalSignApiError;
use MicroHis\SignosVitales\Client\VitalSignApiUnavailable;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryPermissionChecker;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;

require __DIR__ . '/../app.php';

function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
}

const TRACE = '11111111-1111-4111-8111-111111111111';
$notifier = new QueuedAlertNotifier();
$api = signos_vitales_api(
    new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, 45)]]),
    new InMemoryVitalSignRepository(),
    $notifier,
    new InMemoryPermissionChecker(['dra.lopez' => ['signos_vitales.registrar', 'signos_vitales.ver'], 'enf.ruiz' => ['signos_vitales.ver']]),
    static fn (): string => TRACE,
);
$client = new VitalSignApiClient(new KernelTransport($api), 'dra.lopez');
$hora = '2026-10-14 08:30:00';

$r = $client->record('ep1', ['heart_rate' => 138, 'oxygen_saturation' => 94], $hora);
check('cliente: registra y recibe la severidad y los hallazgos, sin el trace_id', $r['severity'] === 'critical' && $r['findings'] === ['heart_rate' => 'critical', 'oxygen_saturation' => 'warning'] && !isset($r['trace_id']) && count($notifier->queue) === 1);
$leida = $client->vitalSign($r['vital_sign_id']);
check('cliente: lee la medición con sus valores', $leida['care_episode_id'] === 'ep1' && $leida['values'] == ['heart_rate' => 138, 'oxygen_saturation' => 94]);

try {
    (new VitalSignApiClient(new KernelTransport($api), 'enf.ruiz'))->record('ep1', ['heart_rate' => 80], $hora);
    check('cliente: sin permiso -> VitalSignApiError 403 con código y trace_id del servidor', false);
} catch (VitalSignApiError $e) {
    check('cliente: sin permiso -> VitalSignApiError 403 con código y trace_id del servidor', $e->status === 403 && $e->errorCode === 'FORBIDDEN' && $e->traceId === TRACE);
}
try {
    $client->record('ep1', ['temperature' => 50], $hora);
    check('cliente: temperatura 50 °C -> VitalSignApiError 422 VITAL_SIGN_IMPLAUSIBLE', false);
} catch (VitalSignApiError $e) {
    check('cliente: temperatura 50 °C -> VitalSignApiError 422 VITAL_SIGN_IMPLAUSIBLE', $e->status === 422 && $e->errorCode === 'VITAL_SIGN_IMPLAUSIBLE');
}

/** Transporte que falla las primeras $failures llamadas y después delega; cuenta los intentos. */
$flaky = static fn (int $failures, ?HttpTransport $inner = null): HttpTransport => new class ($failures, $inner) implements HttpTransport {
    public int $calls = 0;

    public function __construct(private int $failures, private readonly ?HttpTransport $inner)
    {
    }

    public function send(string $method, string $path, string $actorId, array $body = []): array
    {
        $this->calls++;
        return $this->failures-- > 0 || $this->inner === null ? throw new VitalSignApiUnavailable() : $this->inner->send($method, $path, $actorId, $body);
    }
};
$once = $flaky(1, new KernelTransport($api));
check('resiliencia: la lectura se reintenta una vez si el servidor no responde', (new VitalSignApiClient($once, 'dra.lopez'))->vitalSign($r['vital_sign_id'])['care_episode_id'] === 'ep1' && $once->calls === 2);
foreach (['lectura' => [static fn (VitalSignApiClient $c) => $c->vitalSign('x'), 2], 'registro' => [static fn (VitalSignApiClient $c) => $c->record('ep1', ['heart_rate' => 80], '2026-10-14 08:30:00'), 1]] as $op => [$call, $expected]) {
    $down = $flaky(PHP_INT_MAX);
    try {
        $call(new VitalSignApiClient($down, 'dra.lopez'));
        check("resiliencia: $op sin servidor -> VitalSignApiUnavailable", false);
    } catch (VitalSignApiUnavailable) {
        check("resiliencia: $op sin servidor -> VitalSignApiUnavailable tras {$down->calls} intento(s), sin reintentos infinitos", $down->calls === $expected);
    }
}
try {
    (new VitalSignApiClient(new StreamTransport('http://127.0.0.1:1', 0.5), 'dra.lopez'))->vitalSign('x');
    check('cliente HTTP real: puerto cerrado -> VitalSignApiUnavailable', false);
} catch (VitalSignApiUnavailable) {
    check('cliente HTTP real: puerto cerrado -> VitalSignApiUnavailable', true);
}
echo 'Todas las pruebas del cliente pasaron.' . PHP_EOL;
