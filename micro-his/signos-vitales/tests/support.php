<?php

declare(strict_types=1);

use MicroHis\SignosVitales\Application\AlertNotifier;
use MicroHis\SignosVitales\Application\EpisodeContext;
use MicroHis\SignosVitales\Persistence\InMemoryCareEpisodeReader;
use MicroHis\SignosVitales\Persistence\InMemoryPermissionChecker;
use MicroHis\SignosVitales\Persistence\InMemoryVitalSignRepository;
use MicroHis\SignosVitales\Persistence\QueuedAlertNotifier;
use MicroHis\SignosVitales\Presentation\ApiKernel;
use MicroHis\SignosVitales\Presentation\Request;

require __DIR__ . '/../app.php';

/** Contadores de la suite: pruebas que pasan y defectos conocidos todavía abiertos. */
$GLOBALS['stats'] = ['ok' => 0, 'abierto' => 0];

/** Una prueba que falla termina la suite con código 1. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": $name" . PHP_EOL;
    $ok || exit(1);
    $GLOBALS['stats']['ok']++;
}

/** Prueba de un defecto registrado: «ABIERTO» no rompe la suite y pasa a «OK» cuando el defecto se corrige. */
function defecto(string $id, string $name, bool $corregido): void
{
    echo ($corregido ? 'OK' : "ABIERTO $id") . ": $name" . PHP_EOL;
    $GLOBALS['stats'][$corregido ? 'ok' : 'abierto']++;
}

function resumen(string $suite): void
{
    printf("%s: %d pruebas, %d OK, %d defectos abiertos%s", $suite, array_sum($GLOBALS['stats']), $GLOBALS['stats']['ok'], $GLOBALS['stats']['abierto'], PHP_EOL);
}

/**
 * Servidor API con dobles en memoria de lo ajeno (episodios, RBAC, canal). dra.lopez registra y ve; enf.ruiz solo ve.
 * dr.perez tiene los permisos pero ninguna asignación. Episodios: ep1 adulto abierto, ep17 y ep18 en el límite de edad,
 * ep2 niño de 9 años y ep3 cerrado; solo dra.lopez los atiende.
 *
 * @return array{0: ApiKernel, 1: InMemoryVitalSignRepository, 2: AlertNotifier}
 */
function servidor(bool $rbacCaido = false, ?AlertNotifier $canal = null, bool $baseCaida = false, ?Closure $reloj = null): array
{
    $contextos = ['ep1' => new EpisodeContext(true, 45), 'ep17' => new EpisodeContext(true, 17), 'ep18' => new EpisodeContext(true, 18), 'ep2' => new EpisodeContext(true, 9), 'ep3' => new EpisodeContext(false, 60)];
    $repo = new InMemoryVitalSignRepository(!$baseCaida);
    $canal ??= new QueuedAlertNotifier();
    $permisos = new InMemoryPermissionChecker(['dra.lopez' => ['signos_vitales.registrar', 'signos_vitales.ver'], 'enf.ruiz' => ['signos_vitales.ver'], 'dr.perez' => ['signos_vitales.registrar', 'signos_vitales.ver']], $rbacCaido);
    $episodios = new InMemoryCareEpisodeReader(array_map(static fn (EpisodeContext $c): array => ['dra.lopez' => $c], $contextos));
    return [signos_vitales_api($episodios, $repo, $canal, $permisos, clock: $reloj), $repo, $canal];
}

/** @return array{status: int, json: array, headers: array<string, string>} */
function llamar(ApiKernel $api, string $metodo, string $ruta, string $actor = 'dra.lopez', array $cuerpo = [], array $cabeceras = []): array
{
    $r = $api->handle(new Request($metodo, $ruta, ($actor === '' ? [] : ['X-User-ID' => $actor]) + $cabeceras, $cuerpo));
    return ['status' => $r['status'], 'json' => json_decode($r['body'], true), 'headers' => $r['headers']];
}

/** Registra una medición por la API y devuelve estado y `code` en una cadena, p. ej. «201 ALERT_GENERATED». */
function registrar(ApiKernel $api, array $signos, string $episodio = 'ep1', string $hora = '2026-10-14 08:30:00', string $actor = 'dra.lopez'): string
{
    $r = llamar($api, 'POST', "/api/v1/care-episodes/{$episodio}/vital-signs", $actor, $signos + ['measured_at' => $hora]);
    return $r['status'] . ' ' . $r['json']['code'] . (isset($r['json']['severity']) ? '/' . $r['json']['severity'] : '');
}
