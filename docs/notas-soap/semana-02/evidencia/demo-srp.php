<?php

declare(strict_types=1);

/*
 * ASII-11 · Semana 2 · Evidencia verificable de SRP. PHP 8.2+, sin framework ni BD; datos ficticios.
 * Ejecutar: php docs/notas-soap/semana-02/evidencia/demo-srp.php
 * Compara el diseño "antes" (un servicio con cinco razones para cambiar) con el "después" (una por clase).
 */

final readonly class Result { public function __construct(public int $status, public string $code = 'OK') {} }

final class Note
{
    public array $diagnoses = []; // list<array{code: string, type: string}>
    public function __construct(public string $id, public string $episode, public string $author, public string $status = 'draft') {}
}

// ANTES: cinco razones para cambiar (acceso, formato, catálogo, reglas y persistencia).
final class SoapNoteServiceAntes
{
    private const CIE10 = ['J18.9', 'I10', 'E11.9']; // catálogo fijo dentro del servicio
    public array $notes = [];

    public function __construct(private array $assignments) {}

    public function registrar(string $user, string $episode, array $soap): Result
    {
        if (!in_array($user, $this->assignments[$episode] ?? [], true)) { return new Result(403, 'NOT_TREATING_PHYSICIAN'); }
        $ok = array_filter(['S', 'O', 'A', 'P'], fn ($k) => is_string($soap[$k] ?? null) && mb_strlen($soap[$k]) <= 4000);
        if (count($ok) !== 4) { return new Result(422, 'SOAP_SECTION_INVALID'); }
        $id = 'n' . (count($this->notes) + 1);
        $this->notes[$id] = new Note($id, $episode, $user);

        return new Result(201, $id);
    }

    public function asociarDiagnostico(string $user, string $noteId, string $code, string $type): Result
    {
        $note = $this->notes[$noteId] ?? null;
        if ($note === null) { return new Result(404, 'NOTE_NOT_FOUND'); }
        if ($note->status !== 'draft') { return new Result(409, 'NOTE_SIGNED'); }
        if ($note->author !== $user || !in_array($user, $this->assignments[$note->episode] ?? [], true)) { return new Result(403, 'NOT_TREATING_PHYSICIAN'); }
        if (!in_array($code, self::CIE10, true)) { return new Result(422, 'DIAGNOSIS_CODE_INVALID'); }
        foreach ($note->diagnoses as $d) {
            if ($d['code'] === $code) { return new Result(409, 'DIAGNOSIS_DUPLICATED'); }
            if ($type === 'primary' && $d['type'] === 'primary') { return new Result(409, 'PRIMARY_DIAGNOSIS_EXISTS'); }
        }
        $note->diagnoses[] = ['code' => $code, 'type' => $type];

        return new Result(201);
    }
}

// DESPUÉS: una responsabilidad por clase. Las interfaces aíslan lo que cambia fuera del módulo.
final class CatalogUnavailable extends RuntimeException {}
interface Icd10Catalog { public function isValid(string $code): bool; }
interface CareAssignmentReader { public function isTreating(string $user, string $episode): bool; }
interface ClinicalNoteRepository { public function find(string $id): ?Note; public function save(Note $n): void; public function nextId(): string; }

final class TreatingPhysicianGuard // acceso: autor y médico tratante
{
    public function __construct(private CareAssignmentReader $assignments) {}
    public function allows(string $user, string $episode, ?string $author = null): bool
    {
        return ($author ?? $user) === $user && $this->assignments->isTreating($user, $episode);
    }
}

final class SoapSectionsValidator // formato de las cuatro secciones
{
    public function isValid(array $soap): bool
    {
        return count(array_filter(['S', 'O', 'A', 'P'], fn ($k) => is_string($soap[$k] ?? null) && mb_strlen($soap[$k]) <= 4000)) === 4;
    }
}

final class DiagnosisPolicy // reglas clínicas: sin duplicados y un solo principal
{
    public function rejection(Note $note, string $code, string $type): ?Result
    {
        foreach ($note->diagnoses as $d) {
            if ($d['code'] === $code) { return new Result(409, 'DIAGNOSIS_DUPLICATED'); }
            if ($type === 'primary' && $d['type'] === 'primary') { return new Result(409, 'PRIMARY_DIAGNOSIS_EXISTS'); }
        }
        return null;
    }
}

final class SoapNoteRegistrar // orquesta UC-SOAP-01
{
    public function __construct(private TreatingPhysicianGuard $guard, private SoapSectionsValidator $validator, private ClinicalNoteRepository $notes) {}
    public function register(string $user, string $episode, array $soap): Result
    {
        if (!$this->guard->allows($user, $episode)) { return new Result(403, 'NOT_TREATING_PHYSICIAN'); }
        if (!$this->validator->isValid($soap)) { return new Result(422, 'SOAP_SECTION_INVALID'); }
        $this->notes->save($note = new Note($this->notes->nextId(), $episode, $user));
        return new Result(201, $note->id);
    }
}

final class DiagnosisAssociator // orquesta UC-SOAP-02, 03 y 04
{
    public function __construct(private TreatingPhysicianGuard $guard, private Icd10Catalog $catalog, private DiagnosisPolicy $policy, private ClinicalNoteRepository $notes) {}
    public function associate(string $user, string $noteId, string $code, string $type): Result
    {
        $note = $this->notes->find($noteId);
        if ($note === null) { return new Result(404, 'NOTE_NOT_FOUND'); }
        if ($note->status !== 'draft') { return new Result(409, 'NOTE_SIGNED'); }
        if (!$this->guard->allows($user, $note->episode, $note->author)) { return new Result(403, 'NOT_TREATING_PHYSICIAN'); }
        try { $valid = $this->catalog->isValid($code); } catch (CatalogUnavailable) { return new Result(503, 'ICD10_UNAVAILABLE'); } // falla segura
        if (!$valid) { return new Result(422, 'DIAGNOSIS_CODE_INVALID'); }
        if ($rejection = $this->policy->rejection($note, $code, $type)) { return $rejection; }
        $note->diagnoses[] = ['code' => $code, 'type' => $type];
        $this->notes->save($note);
        return new Result(201);
    }
}

// Adaptadores en memoria (en Laravel: Eloquent y el origen real del catálogo).
final class InMemoryNotes implements ClinicalNoteRepository
{
    private array $rows = [];
    public function find(string $id): ?Note { return $this->rows[$id] ?? null; }
    public function save(Note $n): void { $this->rows[$n->id] = $n; }
    public function nextId(): string { return 'n' . (count($this->rows) + 1); }
}
final class ArrayAssignments implements CareAssignmentReader
{
    public function __construct(private array $map) {} public function isTreating(string $u, string $e): bool { return in_array($u, $this->map[$e] ?? [], true); }
}
final class ListCatalog implements Icd10Catalog { public function isValid(string $code): bool { return in_array($code, ['J18.9', 'I10', 'E11.9'], true); } }
final class DownCatalog implements Icd10Catalog { public function isValid(string $code): bool { throw new CatalogUnavailable(); } }

// Aserciones
$failures = 0;
function check(string $label, bool $ok): void { global $failures; echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n"; $failures += $ok ? 0 : 1; }
function build(Icd10Catalog $catalog): array
{
    [$guard, $notes] = [new TreatingPhysicianGuard(new ArrayAssignments(['ep1' => ['dra.lopez']])), new InMemoryNotes()];
    return [new SoapNoteRegistrar($guard, new SoapSectionsValidator(), $notes), new DiagnosisAssociator($guard, $catalog, new DiagnosisPolicy(), $notes), $notes];
}

$soap = ['S' => 'Tos 3 días', 'O' => 'T 38.4 °C', 'A' => 'Neumonía', 'P' => 'Amoxicilina'];
$antes = new SoapNoteServiceAntes(['ep1' => ['dra.lopez']]);
[$reg, $dx, $notas] = build(new ListCatalog());
$casos = [ // [etiqueta, método, argumentos, estado esperado]
    ['registrar nota válida → 201', 'r', ['dra.lopez', 'ep1', $soap], 201],
    ['faltan las secciones O, A y P → 422', 'r', ['dra.lopez', 'ep1', ['S' => 'x']], 422],
    ['médico no tratante → 403', 'r', ['dr.perez', 'ep1', $soap], 403],
    ['diagnóstico principal J18.9 → 201', 'd', ['dra.lopez', 'n1', 'J18.9', 'primary'], 201],
    ['código inexistente → 422', 'd', ['dra.lopez', 'n1', 'ZZZ', 'secondary'], 422],
    ['mismo código otra vez → 409', 'd', ['dra.lopez', 'n1', 'J18.9', 'secondary'], 409],
    ['segundo principal → 409', 'd', ['dra.lopez', 'n1', 'I10', 'primary'], 409],
    ['otro médico en una nota ajena → 403', 'd', ['dr.perez', 'n1', 'I10', 'secondary'], 403],
];

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ($casos as [$label, $m, $args, $esperado]) {
    $a = $m === 'r' ? $antes->registrar(...$args) : $antes->asociarDiagnostico(...$args);
    $b = $m === 'r' ? $reg->register(...$args) : $dx->associate(...$args);
    check($label, $a->status === $esperado && $b->status === $esperado && $a->code === $b->code);
}
$antes->notes['n1']->status = $notas->find('n1')->status = 'signed';
check('nota firmada no acepta diagnósticos → 409 en ambos', $antes->asociarDiagnostico('dra.lopez', 'n1', 'I10', 'secondary')->status === 409
    && $dx->associate('dra.lopez', 'n1', 'I10', 'secondary')->status === 409);

echo "\n== SRP: cada cambio toca una sola clase ==\n";
[$reg2, $dx2] = build(new DownCatalog());
$reg2->register('dra.lopez', 'ep1', $soap);
check('catálogo caído → 503 cambiando solo el Icd10Catalog', $dx2->associate('dra.lopez', 'n1', 'J18.9', 'primary')->code === 'ICD10_UNAVAILABLE');
check('el diseño antes guarda el catálogo como constante interna', (new ReflectionClass(SoapNoteServiceAntes::class))->hasConstant('CIE10'));
$deps = fn (string $c) => array_map(fn ($p) => (string) $p->getType(), (new ReflectionClass($c))->getConstructor()?->getParameters() ?? []);
check('SoapNoteRegistrar no depende del catálogo CIE-10', !in_array('Icd10Catalog', $deps(SoapNoteRegistrar::class), true));
check('DiagnosisPolicy no tiene dependencias', $deps(DiagnosisPolicy::class) === []);
$nota = new Note('x', 'ep1', 'dra.lopez');
$nota->diagnoses[] = ['code' => 'I10', 'type' => 'primary'];
check('DiagnosisPolicy se prueba sola: rechaza un segundo principal', (new DiagnosisPolicy())->rejection($nota, 'E11.9', 'primary')?->code === 'PRIMARY_DIAGNOSIS_EXISTS');

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
