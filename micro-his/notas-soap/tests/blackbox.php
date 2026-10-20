<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Domain\Uuid;

require __DIR__ . '/support.php';

$notes = '/api/v1/care-episodes/ep1/soap-notes';
$api = memoryApi(repo: $repo, audit: $audit);
$post = static fn (array $body, string $actor = 'dra.lopez'): array => send($api, 'POST', '/api/v1/care-episodes/ep1/soap-notes', $actor, $body);
$state = static fn (array $r): string => $r['status'] . ' ' . $r['code'];

// Equivalencia y límites del texto (RF-SOAP-01 y RF-SOAP-02). Clase válida, cuatro clases inválidas por sección, y los límites 4000 y 4001.
$r = $post(SOAP);
$noteId = $r['body']['clinical_note_id'] ?? '';
check('B-01', 'Clase válida · cuatro secciones -> 201 en borrador con id UUID', $r['status'] === 201 && $r['body']['status'] === 'draft' && preg_match(Uuid::PATTERN, $noteId) === 1);
$invalid = true;
foreach (array_keys(SOAP) as $field) {
    foreach (['' => 'vacía', '   ' => 'solo espacios'] as $value => $_) {
        $invalid = $invalid && $state($post([$field => $value] + SOAP)) === '422 SOAP_SECTION_INVALID';
    }
    $faltante = SOAP;
    unset($faltante[$field]);
    $invalid = $invalid && $state($post($faltante)) === '422 SOAP_SECTION_INVALID';
}
check('B-02', 'Clases inválidas · cada sección vacía, con espacios o ausente -> 422 SOAP_SECTION_INVALID (12 casos)', $invalid);
check('B-03', 'Límites · 4000 caracteres -> 201; 4001 -> 422; con «ñ», 4000 -> 201', $post(['plan' => str_repeat('a', 4000)] + SOAP)['status'] === 201 && $state($post(['plan' => str_repeat('a', 4001)] + SOAP)) === '422 SOAP_SECTION_INVALID' && $post(['plan' => str_repeat('ñ', 4000)] + SOAP)['status'] === 201);
check('B-04', 'Tipo de dato · sección numérica o nula -> 422 PAYLOAD_INVALID/SOAP_SECTION_INVALID; JSON malformado -> 422 PAYLOAD_INVALID, sin guardar', (function () use ($api, $post, $repo): bool {
    $antes = count($repo->notes);
    $numerica = send($api, 'POST', '/api/v1/care-episodes/ep1/soap-notes', 'dra.lopez', ['plan' => 7] + SOAP);
    $malformado = send($api, 'POST', '/api/v1/care-episodes/ep1/soap-notes', 'dra.lopez', null);
    return $numerica['code'] === 'PAYLOAD_INVALID' && $post(['plan' => null] + SOAP)['code'] === 'SOAP_SECTION_INVALID' && $malformado['code'] === 'PAYLOAD_INVALID' && count($repo->notes) === $antes;
})());

// Permisos (RNF-SOAP-01): identidad, permiso del rol y médico tratante, y RBAC caído.
check('B-05', 'Permisos · sin identidad 401; enfermería 403 FORBIDDEN; Dr. Pérez sin asignación 403 NOT_TREATING_PHYSICIAN', $state(send($api, 'POST', $notes, null, SOAP)) === '401 UNAUTHENTICATED' && $state($post(SOAP, 'enf.ruiz')) === '403 FORBIDDEN' && $state($post(SOAP, 'dr.perez')) === '403 NOT_TREATING_PHYSICIAN');
$rbac = memoryApi(rbacDown: true, repo: $repoRbac);
check('B-06', 'Falla cerrada · RBAC caído -> 503 AUTHORIZATION_UNAVAILABLE y no se guarda nada', $state(send($rbac, 'POST', $notes, 'dra.lopez', SOAP)) === '503 AUTHORIZATION_UNAVAILABLE' && $repoRbac->notes === []);

// Diagnósticos (RF-SOAP-04 a RF-SOAP-06): una prueba por clase de equivalencia.
$dx = static fn (string $code, string $type, string $actor = 'dra.lopez'): array => send($api, 'POST', "/api/v1/soap-notes/$noteId/diagnoses", $actor, ['code' => $code, 'type' => $type]);
check('B-07', 'Diagnóstico válido principal J18.9 -> 201; secundario I10 -> 201', $dx('J18.9', 'primary')['status'] === 201 && $dx('I10', 'secondary')['status'] === 201);
check('B-08', 'Duplicado -> 409 DIAGNOSIS_DUPLICATED; segundo principal -> 409 PRIMARY_DIAGNOSIS_EXISTS', $state($dx('J18.9', 'secondary')) === '409 DIAGNOSIS_DUPLICATED' && $state($dx('A00', 'primary')) === '409 PRIMARY_DIAGNOSIS_EXISTS');
check('B-09', 'Inválidos · tipo fuera de la lista y código vacío -> 422 DIAGNOSIS_INVALID; código inactivo o inexistente -> 422 DIAGNOSIS_CODE_INVALID', $state($dx('J18.9', 'principal')) === '422 DIAGNOSIS_INVALID' && $state($dx('', 'secondary')) === '422 DIAGNOSIS_INVALID' && $state($dx('A00', 'secondary')) === '422 DIAGNOSIS_CODE_INVALID' && $state($dx('Z99', 'secondary')) === '422 DIAGNOSIS_CODE_INVALID');
$caido = memoryApi(catalogDown: true, repo: $repoCaido);
$otra = send($caido, 'POST', $notes, 'dra.lopez', SOAP)['body']['clinical_note_id'];
check('B-10', 'Catálogo caído -> 503 ICD10_UNAVAILABLE y la nota queda sin diagnósticos', $state(send($caido, 'POST', "/api/v1/soap-notes/$otra/diagnoses", 'dra.lopez', ['code' => 'J18.9', 'type' => 'primary'])) === '503 ICD10_UNAVAILABLE' && ($repoCaido->diagnoses[$otra] ?? []) === []);
check('B-11', 'Dr. Pérez no asocia diagnósticos a la nota ajena -> 403; nota inexistente -> 404 NOTE_NOT_FOUND', $state($dx('I10', 'secondary', 'dr.perez')) === '403 NOT_TREATING_PHYSICIAN' && $state(send($api, 'POST', '/api/v1/soap-notes/no-existe/diagnoses', 'dra.lopez', ['code' => 'I10', 'type' => 'secondary'])) === '404 NOTE_NOT_FOUND');

// Lectura (notas_soap.ver) y contrato.
$get = static fn (string $id, string $actor): array => send($api, 'GET', "/api/v1/soap-notes/$id", $actor, []);
$leida = $get($noteId, 'dra.lopez');
check('B-12', 'Lectura · el tratante recibe secciones y 2 diagnósticos; enfermería sin asignación -> 403; id inexistente -> 404', $leida['status'] === 200 && $leida['body']['sections']['plan'] === 'Amoxicilina' && count($leida['body']['diagnoses']) === 2 && $state($get($noteId, 'enf.ruiz')) === '403 NOT_TREATING_PHYSICIAN' && $state($get('no-existe', 'dra.lopez')) === '404 NOTE_NOT_FOUND');
check('B-13', 'Contrato · verbo o ruta no previstos -> 404 ROUTE_NOT_FOUND', $state(send($api, 'PUT', "/api/v1/soap-notes/$noteId", 'dra.lopez', [])) === '404 ROUTE_NOT_FOUND' && $state(send($api, 'GET', '/api/v2/soap-notes/x', 'dra.lopez', [])) === '404 ROUTE_NOT_FOUND');
$trace = true;
foreach ([$post(SOAP), $post([], 'enf.ruiz'), $dx('J18.9', 'secondary')] as $respuesta) {
    $trace = $trace && preg_match(Uuid::PATTERN, $respuesta['body']['trace_id'] ?? '') === 1;
}
check('B-14', 'Trazabilidad · éxitos y errores llevan un trace_id UUID en el cuerpo', $trace);
check('B-15', 'Privacidad · la auditoría guarda identificadores y no el texto clínico (RNF-SOAP-05)', $audit->events !== [] && !str_contains(json_encode($audit->events, JSON_UNESCAPED_UNICODE), 'Tos de 3 días'));

// Defectos conocidos.
defect('D-01', 'B-16', 'Una sección con solo espacio duro (U+00A0) se rechaza como vacía', $state($post(['subjective' => "\u{00A0}"] + SOAP)) === '422 SOAP_SECTION_INVALID');
defect('D-03', 'B-17', 'Quien no atiende el episodio recibe la misma respuesta ante una nota existente y una inexistente', $state($get($noteId, 'enf.ruiz')) === $state($get('no-existe', 'enf.ruiz')));

summary();
