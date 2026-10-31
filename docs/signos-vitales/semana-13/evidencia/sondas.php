<?php

declare(strict_types=1);

/**
 * Evidencia de la Semana 13: sondas que reproducen, contra el código real del micro-HIS, los puntos de atención que el autor
 * declara antes de la revisión técnica (P-1 a P-8 del checklist). No corrige nada: documenta el comportamiento actual.
 * Cada línea indica ítem, observación y resultado. Uso: php docs/signos-vitales/semana-13/evidencia/sondas.php
 */

use MicroHis\SignosVitales\Application\AlertNotifier;
use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Domain\Assessment;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryPermissionChecker;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\Request;

$raiz = __DIR__ . '/../../../../micro-his/signos-vitales';
require $raiz . '/app.php';

/** @return array{0: \MicroHis\SignosVitales\Presentation\ApiKernel, 1: InMemoryVitalSignRepository} */
function servidor(?AlertNotifier $notifier = null): array
{
    $repo = new InMemoryVitalSignRepository();
    $episodios = new InMemoryCareEpisodeReader(['ep1' => ['dra.lopez' => new EpisodeContext(true, 45)]]);
    $permisos = new InMemoryPermissionChecker(['dra.lopez' => ['signos_vitales.registrar', 'signos_vitales.ver'], 'enf.ruiz' => ['signos_vitales.ver']]);
    return [signos_vitales_api($episodios, $repo, $notifier ?? new QueuedAlertNotifier(), $permisos), $repo];
}

function enviar(\MicroHis\SignosVitales\Presentation\ApiKernel $api, string $metodo, string $ruta, string $usuario, array $cuerpo = []): array
{
    $r = $api->handle(new Request($metodo, $ruta, ['X-User-ID' => $usuario], $cuerpo));
    return [$r['status'], json_decode($r['body'], true)];
}

function sonda(string $id, string $item, string $texto): void
{
    printf("%s  %-8s %s%s", $id, $item, $texto, PHP_EOL);
}

$hora = ['measured_at' => '2026-10-14 08:30:00'];
$ruta = '/api/v1/care-episodes/ep1/vital-signs';

// P-1 · C-10: aislamiento por tenant.
$coincidencias = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz . '/src')) as $archivo) {
    $archivo->isFile() && $coincidencias += substr_count(strtolower((string) file_get_contents($archivo->getPathname())), 'tenant');
}
sonda('P-1', 'C-10', "la palabra «tenant» aparece {$coincidencias} veces en src/ (RNF-SV-01 exige X-Tenant-ID)");

// P-2 · C-11: un fallo del canal de notificación, ya confirmada la medición.
$falla = new class implements AlertNotifier {
    public function notify(string $vitalSignId, Assessment $assessment): void
    {
        throw new \RuntimeException('canal_caido');
    }
};
[$api, $repo] = servidor($falla);
[$estado, $cuerpo] = enviar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 138] + $hora);
sonda('P-2', 'C-11', "canal de notificación caído: respuesta {$estado} {$cuerpo['code']}, pero la medición quedó guardada ({$repo->audits[0]['event']}) y auditada: " . count($repo->audits) . ' eventos');

// P-3 · C-04: fecha de medición futura o muy antigua.
[$api] = servidor();
foreach (['2099-01-01 00:00:00' => 'futura', '1900-01-01 00:00:00' => 'de 1900'] as $fecha => $nombre) {
    [$estado, $cuerpo] = enviar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80, 'measured_at' => $fecha]);
    sonda('P-3', 'C-04', "measured_at {$nombre}: {$estado} " . ($cuerpo['code'] ?? 'creada'));
}

// P-4 · C-08: la lectura distingue «no existe» de «no atiende».
[$estado, $cuerpo] = enviar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 80] + $hora);
$id = $cuerpo['vital_sign_id'];
[$noExiste] = enviar($api, 'GET', '/api/v1/vital-signs/00000000-0000-4000-8000-000000000000', 'enf.ruiz');
[$noAtiende] = enviar($api, 'GET', "/api/v1/vital-signs/{$id}", 'enf.ruiz');
sonda('P-4', 'C-08', "GET de un id inexistente: {$noExiste}; de uno existente sin asignación: {$noAtiende} (distinguibles: permite enumerar ids)");

// P-5 · C-09: la identidad es una cabecera sin firmar.
[$estado] = enviar($api, 'GET', "/api/v1/vital-signs/{$id}", 'dra.lopez');
sonda('P-5', 'C-09', "cualquier cliente que envíe X-User-ID: dra.lopez lee la medición: {$estado} (no hay JWT ni firma en el prototipo)");

// P-6 · C-12: el esquema local frente a la migración 000401.
$esquema = (string) preg_replace('/^--.*$/m', '', (string) file_get_contents($raiz . '/database/schema.sql')); // sin los comentarios que ya declaran las divergencias
$divergencias = (int) str_contains($esquema, 'recorded_by TEXT') + (int) !str_contains($esquema, 'medical_record_id') + (int) str_contains($esquema, "DEFAULT 'validated'");
sonda('P-6', 'C-12', "schema.sql difiere de 000401 en {$divergencias} puntos comprobados (recorded_by, medical_record_id ausente, estado inicial 'validated'); está declarado como esquema simplificado");

// P-7 · C-13: unidades implícitas.
[$api] = servidor();
[$estado, $cuerpo] = enviar($api, 'POST', $ruta, 'dra.lopez', ['glucose' => 5.5] + $hora);
sonda('P-7', 'C-13', "glucosa 5,5 (un valor normal en mmol/L) se evalúa como mg/dL: {$estado} {$cuerpo['code']} {$cuerpo['severity']} (alerta crítica falsa)");

// P-8 · C-01: diferencias de tipo con la base.
[$estado, $cuerpo] = enviar($api, 'POST', $ruta, 'dra.lopez', ['heart_rate' => 138.5] + $hora);
$decimal = $estado === 201 ? 'se acepta' : 'se rechaza';
[$estadoSpo2, $cuerpoSpo2] = enviar($api, 'POST', $ruta, 'dra.lopez', ['oxygen_saturation' => 0] + $hora);
sonda('P-8', 'C-01', "FC 138,5 {$decimal} (la columna heart_rate es integer); SpO2 0 se rechaza: {$estadoSpo2} {$cuerpoSpo2['code']} (la base lo admite)");
