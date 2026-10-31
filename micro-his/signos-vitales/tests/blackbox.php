<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\AlertNotifier;
use MicroHis\SignosVitales\Domain\Assessment;

// Caja negra: solo la API v1 (ApiKernel), sin mirar el código. Equivalencia, límites, permisos y errores. Lo ajeno (episodios, RBAC, canal) son dobles.
require __DIR__ . '/support.php';

[$api, $repo] = servidor();
$ruta = '/api/v1/care-episodes/ep1/vital-signs';
$respuestas = [];
/** Registra una medición, guarda la respuesta para la prueba de trace_id y devuelve «estado code/severidad». */
$reg = static function (array $signos, string $episodio = 'ep1', string $hora = '2026-10-14 08:30:00') use (&$respuestas): string {
    [$api] = servidor();
    $r = llamar($api, 'POST', "/api/v1/care-episodes/{$episodio}/vital-signs", 'dra.lopez', $signos + ['measured_at' => $hora]);
    $respuestas[] = $r;
    return $r['status'] . ' ' . $r['json']['code'] . (isset($r['json']['severity']) ? '/' . $r['json']['severity'] : '');
};
/** Compara una lista de entradas con los resultados esperados y devuelve verdadero si todos coinciden. */
$todos = static function (string $signo, array $esperados) use ($reg): bool {
    foreach ($esperados as $valor => $esperado) {
        if ($reg([$signo => $valor + 0.0]) !== $esperado) {
            return false;
        }
    }
    return true;
};

// Clases de equivalencia válidas.
$r = llamar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 138, 'oxygen_saturation' => 94, 'measured_at' => '2026-10-14 08:30:00']);
check('B-01 RF-SV-01, RF-SV-04: FC 138 + SpO2 94 -> 201 ALERT_GENERATED critical con dos hallazgos', $r['status'] === 201 && $r['json']['severity'] === 'critical' && $r['json']['findings'] === ['heart_rate' => 'critical', 'oxygen_saturation' => 'warning'] && $r['json']['notification'] === 'queued');
check('B-02 RF-SV-01: un solo signo, varios signos y los nueve signos válidos -> 201', $reg(['heart_rate' => 80]) === '201 OK/none' && $reg(['heart_rate' => 80, 'weight' => 70, 'height' => 170]) === '201 OK/none'
    && str_starts_with($reg(['temperature' => 36.8, 'heart_rate' => 80, 'respiratory_rate' => 16, 'systolic_bp' => 120, 'diastolic_bp' => 80, 'oxygen_saturation' => 98, 'weight' => 70, 'height' => 170, 'glucose' => 100]), '201 OK'));
// Clases de equivalencia inválidas: signos y fecha.
$invalidos = ['sin signos' => [], 'signo desconocido' => ['pulso' => 80], 'texto' => ['heart_rate' => '80'], 'booleano' => ['heart_rate' => true], 'nulo' => ['heart_rate' => null], 'arreglo' => ['heart_rate' => [80]]];
$ok = true;
foreach ($invalidos as $signos) {
    $ok = $ok && str_starts_with($reg($signos), '422 VITAL_SIGNS_INVALID');
}
check('B-03 RF-SV-02: seis clases de signos inválidos (vacío, desconocido, texto, booleano, nulo, arreglo) -> 422 VITAL_SIGNS_INVALID', $ok);
$ok = true;
foreach (['14/10/2026', '', '2026-10-14T08:30:00', '2026-02-30 08:30:00', '2026-10-14 08:30'] as $fecha) {
    $ok = $ok && $reg(['heart_rate' => 80], 'ep1', $fecha) === '422 MEASURED_AT_INVALID';
}
check('B-04 RF-SV-01: cinco clases de measured_at inválida (formato, vacía, con T, 30 de febrero, sin segundos) -> 422 MEASURED_AT_INVALID', $ok);
$sin = llamar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80]);
$num = llamar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80, 'measured_at' => 20261014]);
check('B-05 RF-SV-01: measured_at ausente -> 422 MEASURED_AT_INVALID; no textual -> 422 PAYLOAD_INVALID', $sin['json']['code'] === 'MEASURED_AT_INVALID' && $num['json']['code'] === 'PAYLOAD_INVALID');
// Análisis de valores límite: plausibilidad.
check('B-06 RF-SV-02: límites de plausibilidad de temperatura (25, 25,1, 45, 45,1) y FC (0, 300, 300,1)', $todos('temperature', ['25' => '422 VITAL_SIGN_IMPLAUSIBLE', '25.1' => '201 OK/none', '45' => '201 OK/none','45.1' => '422 VITAL_SIGN_IMPLAUSIBLE'])
    && $todos('heart_rate', ['0' => '422 VITAL_SIGN_IMPLAUSIBLE', '300' => '201 ALERT_GENERATED/critical', '300.1' => '422 VITAL_SIGN_IMPLAUSIBLE']));
check('B-07 RF-SV-02: SpO2 100 -> 201, 100,1 -> 422, 0 -> 422; sistólica menor que la diastólica -> 422', $todos('oxygen_saturation', ['100' => '201 OK/none', '100.1' => '422 VITAL_SIGN_IMPLAUSIBLE', '0' => '422 VITAL_SIGN_IMPLAUSIBLE']) && $reg(['systolic_bp' => 80, 'diastolic_bp' => 90]) === '422 VITAL_SIGN_IMPLAUSIBLE');
// Análisis de valores límite: umbrales.
$a = '201 ALERT_GENERATED/critical';
$w = '201 ALERT_GENERATED/warning';
$n = '201 OK/none';
check('B-08 RF-SV-03: bandas de FC en 40, 41, 50, 51, 90, 91, 130, 131', $todos('heart_rate', [40 => $a, 41 => $w, 50 => $w, 51 => $n, 90 => $n, 91 => $w, 130 => $w, 131 => $a]));
check('B-09 RF-SV-03: bandas de glucosa (mg/dL) en 53, 54, 69, 70, 180, 181, 399, 400', $todos('glucose', [53 => $a, 54 => $w, 69 => $w, 70 => $n, 180 => $n, 181 => $w, 399 => $w, 400 => $a]));
check('B-10 RF-SV-03: bandas de FR en 8, 9, 11, 12, 20, 21, 24, 25 y de SpO2 en 91, 92, 95, 96', $todos('respiratory_rate', [8 => $a, 9 => $w, 11 => $w, 12 => $n, 20 => $n, 21 => $w, 24 => $w, 25 => $a]) && $todos('oxygen_saturation', [91 => $a, 92 => $w, 95 => $w, 96 => $n]));
check('B-11 RF-SV-06: 17 años -> THRESHOLDS_NOT_APPLICABLE; 18 años -> se evalúa; 9 años con FC 138 sin alerta', $reg(['heart_rate' => 138], 'ep17') === '201 THRESHOLDS_NOT_APPLICABLE/none' && $reg(['heart_rate' => 138], 'ep18') === $a && $reg(['heart_rate' => 138], 'ep2') === '201 THRESHOLDS_NOT_APPLICABLE/none');
// Permisos.
$p = [llamar($api, 'POST', $ruta, '')['status'], llamar($api, 'POST', $ruta, 'enf.ruiz', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'])['status'], llamar($api, 'POST', $ruta, 'dr.perez', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'])['status'], llamar($api, 'POST', $ruta, 'intruso', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'])['status']];
check('B-12 RNF-SV-01: sin identidad 401; enfermería sin registrar 403; médico sin asignación 403; usuario sin permisos 403', $p === [401, 403, 403, 403]);
check('B-13 RNF-SV-01: un episodio ajeno y uno inexistente responden igual para quien no atiende (NOT_TREATING_PHYSICIAN), sin revelar si existe', llamar($api, 'POST', '/api/v1/care-episodes/ep9/vital-signs', 'dra.lopez', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'])['json']['code'] === 'NOT_TREATING_PHYSICIAN'
    && llamar($api, 'POST', $ruta, 'dr.perez', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'])['json']['code'] === 'NOT_TREATING_PHYSICIAN');
// Errores y falla segura.
$caida = llamar(($s = servidor(false, null, true))[0], 'POST', $ruta, 'dra.lopez', ['heart_rate' => 138, 'measured_at' => '2026-10-14 08:30:00']);
check('B-14 RNF-SV-03: episodio cerrado 409; base caída 500 VITAL_SIGN_PERSISTENCE_ERROR sin guardar ni notificar; JSON malformado 422', $reg(['heart_rate' => 80], 'ep3') === '409 EPISODE_CLOSED' && $caida['status'] === 500 && $caida['json']['code'] === 'VITAL_SIGN_PERSISTENCE_ERROR' && $s[1]->audits === [] && $s[2]->queue === []
    && $api->handle(new MicroHis\SignosVitales\Presentation\Request('POST', $ruta, ['X-User-ID' => 'dra.lopez']), false)['status'] === 422);
$canalCaido = new class implements AlertNotifier {
    public function notify(string $vitalSignId, Assessment $assessment): void
    {
        throw new RuntimeException('canal_caido');
    }
};
$c = llamar(($s = servidor(false, $canalCaido))[0], 'POST', $ruta, 'dra.lopez', ['heart_rate' => 138, 'measured_at' => '2026-10-14 08:30:00']);
check('B-15 RF-SV-07: canal caído -> 201 ALERT_GENERATED con notification failed y la medición guardada (no 500)', $c['status'] === 201 && $c['json']['notification'] === 'failed' && count($s[1]->audits) === 2);
check('B-16 RF-SV-07: toda respuesta lleva trace_id igual a X-Trace-Id y ninguna respuesta de error contiene valores clínicos', count(array_filter($respuestas, static fn (array $x): bool => isset($x['json']['trace_id']) && $x['headers']['X-Trace-Id'] === $x['json']['trace_id'])) === count($respuestas)
    && !str_contains(json_encode(array_filter($respuestas, static fn (array $x): bool => $x['status'] >= 400)), '"heart_rate"'));
// Consulta.
$id = llamar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'])['json']['vital_sign_id'];
check('B-17 RNF-SV-01: GET con asignación 200 con los signos; sin asignación 403; inexistente 404; sin permiso 403 FORBIDDEN', llamar($api, 'GET', "/api/v1/vital-signs/{$id}")['json']['values'] == ['heart_rate' => 80]
    && llamar($api, 'GET', "/api/v1/vital-signs/{$id}", 'dr.perez')['status'] === 403 && llamar($api, 'GET', '/api/v1/vital-signs/inexistente')['status'] === 404 && llamar($api, 'GET', "/api/v1/vital-signs/{$id}", 'intruso')['json']['code'] === 'FORBIDDEN');

// Defectos registrados: pasan a OK cuando se corrigen.
$alerta = static fn (array $signos): bool => str_starts_with($reg($signos), '201 ALERT_GENERATED');
defecto('D-01', 'B-18 RF-SV-03: temperatura 41 °C y presión 230/130 deben generar alerta (Anexo B); hoy no hay regla para esos signos', $alerta(['temperature' => 41]) && $alerta(['systolic_bp' => 230, 'diastolic_bp' => 130]) && $alerta(['temperature' => 34]) && $alerta(['systolic_bp' => 85]));
defecto('D-02', 'B-19 RF-SV-03: glucosa 53,5 mg/dL es crítica (Anexo B: < 54); la regla usa ≤ 53 y da warning', $reg(['glucose' => 53.5]) === $a);
defecto('D-03', 'B-20 RF-SV-02: FC 138,5 debe rechazarse (la columna heart_rate es integer); hoy se acepta', str_starts_with($reg(['heart_rate' => 138.5]), '422'));
defecto('D-04', 'B-21 RNF-SV-01: un id inexistente y uno ajeno deben responder igual a quien no atiende; hoy 404 frente a 403', llamar($api, 'GET', '/api/v1/vital-signs/inexistente', 'dr.perez')['status'] === llamar($api, 'GET', "/api/v1/vital-signs/{$id}", 'dr.perez')['status']);
defecto('D-05', 'B-22 RNF-SV-01: una petición con X-Tenant-ID ajeno debe rechazarse; hoy el módulo ignora el tenant', llamar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80, 'measured_at' => '2026-10-14 08:30:00'], ['X-Tenant-ID' => 'otro-hospital'])['status'] >= 400);
resumen('Caja negra');
