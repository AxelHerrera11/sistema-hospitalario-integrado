<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;
use MicroHis\NotasSoap\Persistence\InMemoryPermissionChecker;
use MicroHis\NotasSoap\Presentation\ApiKernel;
use MicroHis\NotasSoap\Presentation\Request;

require __DIR__ . '/../app.php';

const SOAP = ['subjective' => 'Tos de 3 días', 'objective' => 'T 38.4 °C', 'assessment' => 'Neumonía', 'plan' => 'Amoxicilina'];
const GRANTS = ['dra.lopez' => ['notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.firmar', 'notas_soap.corregir', 'notas_soap.ver'], 'dr.perez' => ['notas_soap.crear', 'notas_soap.asociar_diagnostico', 'notas_soap.ver'], 'enf.ruiz' => ['notas_soap.ver']];
$tally = ['ok' => 0, 'fail' => 0, 'defects' => 0];

/** Prueba que debe pasar: si falla, la suite termina con código 1. */
function check(string $id, string $name, bool $ok): void
{
    global $tally;
    $tally[$ok ? 'ok' : 'fail']++;
    echo ($ok ? 'OK    ' : 'FALLO ') . "[$id] $name" . PHP_EOL;
}

/** Prueba que expone un defecto conocido: no rompe la suite, pero queda registrado como abierto hasta que se corrija. */
function defect(string $defect, string $id, string $name, bool $ok): void
{
    global $tally;
    $ok || $tally['defects']++;
    $tally['ok'] += (int) $ok;
    echo ($ok ? "OK    [$id] $name (defecto $defect corregido)" : "ABIERTO $defect [$id] $name") . PHP_EOL;
}

function summary(): never
{
    global $tally;
    echo "Resumen: {$tally['ok']} OK, {$tally['fail']} fallos, {$tally['defects']} defectos abiertos" . PHP_EOL;
    exit($tally['fail'] > 0 ? 1 : 0);
}

/** API sobre adaptadores en memoria: la Dra. López atiende ep1, el Dr. Pérez no. El catálogo trae J18.9, I10 y A00 (inactivo). */
function memoryApi(bool $catalogDown = false, bool $rbacDown = false, ?InMemoryClinicalNoteRepository &$repo = null, ?InMemoryAuditTrail &$audit = null): ApiKernel
{
    $audit = new InMemoryAuditTrail();
    $repo = new InMemoryClinicalNoteRepository($audit);
    $catalog = new InMemoryIcd10Catalog(['J18.9' => ['Neumonía, no especificada', true], 'I10' => ['Hipertensión esencial', true], 'A00' => ['Cólera', false]], $catalogDown);
    return notas_soap_api($repo, new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']]), $catalog, new InMemoryPermissionChecker(GRANTS, $rbacDown));
}

/** @return array{status: int, code: string, body: array} */
function send(ApiKernel $api, string $method, string $path, ?string $actor, ?array $body = []): array
{
    $r = $api->handle(new Request($method, $path, $actor === null ? [] : ['X-User-ID' => $actor], $body ?? []), $body !== null);
    $data = json_decode($r['body'], true);
    return ['status' => $r['status'], 'code' => $data['code'] ?? '', 'body' => $data];
}
