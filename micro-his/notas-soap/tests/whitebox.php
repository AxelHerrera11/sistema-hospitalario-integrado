<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Application\AssociateDiagnosis;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Application\SignSoapNote;
use MicroHis\NotasSoap\Application\TreatingPhysicianGuard;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\DiagnosisPolicy;
use MicroHis\NotasSoap\Domain\SignaturePolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;
use MicroHis\NotasSoap\Persistence\InMemoryAuditTrail;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;
use MicroHis\NotasSoap\Persistence\InMemoryClinicalNoteRepository;
use MicroHis\NotasSoap\Persistence\InMemoryIcd10Catalog;

require __DIR__ . '/support.php';

/** @return array{0: AssociateDiagnosis, 1: InMemoryClinicalNoteRepository, 2: string} caso de uso, repositorio e id de una nota en borrador de la Dra. López. */
function flow(bool $catalogDown = false): array
{
    $repo = new InMemoryClinicalNoteRepository(new InMemoryAuditTrail());
    $catalog = new InMemoryIcd10Catalog(['J18.9' => ['Neumonía', true], 'I10' => ['Hipertensión', true], 'A00' => ['Cólera', false]], $catalogDown);
    $noteId = $repo->add('ep1', 'dra.lopez', new SoapSections('s', 'o', 'a', 'p'));
    return [new AssociateDiagnosis(new TreatingPhysicianGuard(new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez', 'dr.ruiz']])), $catalog, new DiagnosisPolicy(), $repo), $repo, $noteId];
}

function outcome(callable $fn): string
{
    try {
        $fn();
        return 'ok';
    } catch (SoapRuleViolation $e) {
        return $e->getMessage();
    }
}

// AssociateDiagnosis::execute tiene seis salidas: una por rama. Se recorren todas (cobertura de ramas 6/6).
[$associate, $repo, $id] = flow();
check('W-01', 'Rama 1 · nota inexistente -> NOTE_NOT_FOUND, antes de cualquier otra comprobación', outcome(fn () => $associate->execute('dra.lopez', 'no-existe', new Diagnosis('J18.9', 'primary'))) === 'NOTE_NOT_FOUND');
check('W-02', 'Rama 2 · el guardia falla -> NOT_TREATING_PHYSICIAN, sin guardar nada', outcome(fn () => $associate->execute('dr.ruiz', $id, new Diagnosis('J18.9', 'primary'))) === 'NOT_TREATING_PHYSICIAN' && ($repo->diagnoses[$id] ?? []) === []);
check('W-03', 'Rama 3 · la política falla -> NOTE_SIGNED, sin guardar', (function () use ($associate, $repo, $id): bool {
    $repo->markSigned($id);
    return outcome(fn () => $associate->execute('dra.lopez', $id, new Diagnosis('J18.9', 'primary'))) === 'NOTE_SIGNED' && ($repo->diagnoses[$id] ?? []) === [];
})());
[$associate, $repo, $id] = flow(catalogDown: true);
check('W-04', 'Rama 4 · catálogo caído -> ICD10_UNAVAILABLE, la nota queda sin diagnósticos', outcome(fn () => $associate->execute('dra.lopez', $id, new Diagnosis('J18.9', 'primary'))) === 'ICD10_UNAVAILABLE' && ($repo->diagnoses[$id] ?? []) === []);
[$associate, $repo, $id] = flow();
check('W-05', 'Rama 5 · código inactivo o inexistente -> DIAGNOSIS_CODE_INVALID', outcome(fn () => $associate->execute('dra.lopez', $id, new Diagnosis('A00', 'primary'))) === 'DIAGNOSIS_CODE_INVALID' && outcome(fn () => $associate->execute('dra.lopez', $id, new Diagnosis('Z99', 'primary'))) === 'DIAGNOSIS_CODE_INVALID');
check('W-06', 'Rama 6 · camino feliz -> se guarda con la descripción del catálogo y su auditoría', outcome(fn () => $associate->execute('dra.lopez', $id, new Diagnosis('J18.9', 'primary'))) === 'ok' && $repo->diagnoses[$id][0]['description'] === 'Neumonía');
check('W-07', 'Orden de las ramas · un duplicado con el catálogo caído responde 409, no 503 (la política va antes del catálogo)', (function (): bool {
    [$a, $r, $n] = flow();
    $a->execute('dra.lopez', $n, new Diagnosis('J18.9', 'primary'));
    $caido = new AssociateDiagnosis(new TreatingPhysicianGuard(new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']])), new InMemoryIcd10Catalog([], true), new DiagnosisPolicy(), $r);
    return outcome(fn () => $caido->execute('dra.lopez', $n, new Diagnosis('J18.9', 'secondary'))) === 'DIAGNOSIS_DUPLICATED';
})());

// TreatingPhysicianGuard: condición `(autor !== null && autor !== usuario) || !tratante`. Cobertura de condiciones (MC/DC): cada una cambia el resultado por sí sola.
$guard = new TreatingPhysicianGuard(new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez', 'dr.ruiz']]));
$resultado = static fn (string $user, ?string $author): string => outcome(fn () => $guard->assertAllowed($user, 'ep1', $author));
check('W-08', 'Condiciones del guardia · sin autor y tratante pasa; sin autor y no tratante falla; autor ajeno falla aunque sea tratante; autor propio pasa', $resultado('dra.lopez', null) === 'ok' && $resultado('dr.perez', null) === 'NOT_TREATING_PHYSICIAN' && $resultado('dr.ruiz', 'dra.lopez') === 'NOT_TREATING_PHYSICIAN' && $resultado('dra.lopez', 'dra.lopez') === 'ok');

// D-02: entre la lectura de la nota y la escritura otra sesión puede firmarla; el repositorio lo vuelve a comprobar al escribir y rechaza.
[, $repo, $id] = flow();
final class FirmaEnMedio implements ClinicalNoteRepository
{
    public function __construct(private readonly InMemoryClinicalNoteRepository $inner)
    {
    }

    public function find(string $noteId): ?ClinicalNote
    {
        $note = $this->inner->find($noteId);
        $this->inner->markSigned($noteId); // otra persona firma justo después de la lectura
        return $note;
    }

    public function add(string $episodeId, string $authorId, SoapSections $sections): string
    {
        return $this->inner->add($episodeId, $authorId, $sections);
    }

    public function addDiagnosis(ClinicalNote $note, Diagnosis $diagnosis, string $description): void
    {
        $this->inner->addDiagnosis($note, $diagnosis, $description);
    }

    public function correct(ClinicalNote $note, SoapSections $sections, string $reason): string
    {
        return $this->inner->correct($note, $sections, $reason);
    }

    public function sign(ClinicalNote $note): void
    {
        $this->inner->sign($note);
    }
}

$firmaEnMedio = new FirmaEnMedio($repo);
$carrera = new AssociateDiagnosis(new TreatingPhysicianGuard(new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']])), new InMemoryIcd10Catalog(['J18.9' => ['Neumonía', true]]), new DiagnosisPolicy(), $firmaEnMedio);
$rechazo = outcome(fn () => $carrera->execute('dra.lopez', $id, new Diagnosis('J18.9', 'primary')));
check('W-09', 'Una nota firmada entre la lectura y la escritura no recibe el diagnóstico y responde NOTE_SIGNED (D-02)', $rechazo === 'NOTE_SIGNED' && ($repo->diagnoses[$id] ?? []) === []);

// UC-SOAP-05: la firma también pierde la carrera si otra sesión firma entre la lectura y el UPDATE; no se audita dos veces.
[, $repo, $id] = flow();
$repo->addDiagnosis($repo->find($id), new Diagnosis('J18.9', 'primary'), 'Neumonía');
$firmaCarrera = new SignSoapNote(new TreatingPhysicianGuard(new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez']])), new SignaturePolicy(), new FirmaEnMedio($repo));
check('W-10', 'Una nota firmada entre la lectura y el UPDATE no se vuelve a firmar y responde NOTE_SIGNED (UC-SOAP-05)', outcome(fn () => $firmaCarrera->execute('dra.lopez', $id)) === 'NOTE_SIGNED');

summary();
