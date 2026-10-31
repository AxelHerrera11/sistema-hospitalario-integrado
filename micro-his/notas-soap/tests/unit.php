<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Application\TreatingPhysicianGuard;
use MicroHis\NotasSoap\Domain\ClinicalNote;
use MicroHis\NotasSoap\Domain\Diagnosis;
use MicroHis\NotasSoap\Domain\DiagnosisPolicy;
use MicroHis\NotasSoap\Domain\SignaturePolicy;
use MicroHis\NotasSoap\Domain\SoapRuleViolation;
use MicroHis\NotasSoap\Domain\SoapSections;
use MicroHis\NotasSoap\Domain\Uuid;
use MicroHis\NotasSoap\Persistence\InMemoryCareAssignmentReader;

require __DIR__ . '/support.php';

/** Devuelve «code estado» de la violación de dominio que lanza $fn, o '' si no lanza ninguna. */
function violation(callable $fn): string
{
    try {
        $fn();
        return '';
    } catch (SoapRuleViolation $e) {
        return $e->getMessage() . ' ' . $e->status;
    }
}

$note = static fn (string $status = 'draft', array $dx = []): ClinicalNote => new ClinicalNote('n1', 'ep1', 'dra.lopez', $status, $dx);
$policy = new DiagnosisPolicy();
$principal = new ClinicalNote('n1', 'ep1', 'dra.lopez', 'draft', [new Diagnosis('J18.9', 'primary')]);
$guard = new TreatingPhysicianGuard(new InMemoryCareAssignmentReader(['ep1' => ['dra.lopez', 'dr.ruiz']]));

// RF-SOAP-02 · SoapSections: validación de las cuatro secciones.
check('U-01', 'SoapSections acepta cuatro secciones con texto', violation(fn () => new SoapSections('s', 'o', 'a', 'p')) === '');
check('U-02', 'SoapSections rechaza una sección vacía o con solo espacios (SOAP_SECTION_INVALID 422)', violation(fn () => new SoapSections('s', '  ', 'a', 'p')) === 'SOAP_SECTION_INVALID 422' && violation(fn () => new SoapSections('', 'o', 'a', 'p')) === 'SOAP_SECTION_INVALID 422');
check('U-03', 'SoapSections acepta 4000 caracteres y rechaza 4001 (límite)', violation(fn () => new SoapSections(str_repeat('a', 4000), 'o', 'a', 'p')) === '' && violation(fn () => new SoapSections('s', 'o', 'a', str_repeat('a', 4001))) === 'SOAP_SECTION_INVALID 422');
check('U-04', 'SoapSections cuenta caracteres, no bytes: 4000 «ñ» son válidas', violation(fn () => new SoapSections(str_repeat('ñ', 4000), 'o', 'a', 'p')) === '');
check('U-05', 'SoapSections va y vuelve por JSON sin perder las secciones', SoapSections::fromJson((new SoapSections('s', 'o', 'a', 'p'))->toJson())->plan === 'p');

// RF-SOAP-04 y RF-SOAP-05 · Diagnosis y DiagnosisPolicy.
check('U-06', 'Diagnosis exige código y tipo primary, secondary o differential (DIAGNOSIS_INVALID 422)', violation(fn () => new Diagnosis('', 'primary')) === 'DIAGNOSIS_INVALID 422' && violation(fn () => new Diagnosis('J18.9', 'Primary')) === 'DIAGNOSIS_INVALID 422' && violation(fn () => new Diagnosis('J18.9', 'differential')) === '');
check('U-07', 'DiagnosisPolicy rechaza el mismo código en la nota (DIAGNOSIS_DUPLICATED 409)', violation(fn () => $policy->assertCanAdd($principal, new Diagnosis('J18.9', 'secondary'))) === 'DIAGNOSIS_DUPLICATED 409');
check('U-08', 'DiagnosisPolicy rechaza un segundo principal, y admite un secundario o un principal si el existente es secundario', violation(fn () => $policy->assertCanAdd($principal, new Diagnosis('I10', 'primary'))) === 'PRIMARY_DIAGNOSIS_EXISTS 409' && violation(fn () => $policy->assertCanAdd($principal, new Diagnosis('I10', 'secondary'))) === '' && violation(fn () => $policy->assertCanAdd($note('draft', [new Diagnosis('I10', 'secondary')]), new Diagnosis('J18.9', 'primary'))) === '');

// RF-SOAP-07 · versionado: la nota firmada es inmutable.
check('U-09', 'DiagnosisPolicy rechaza un diagnóstico en una nota firmada (NOTE_SIGNED 409)', violation(fn () => $policy->assertCanAdd($note('signed'), new Diagnosis('I10', 'secondary'))) === 'NOTE_SIGNED 409' && $note('signed')->isSigned() && !$note()->isSigned());

// RF-SOAP-03 · autoría clínica: médico tratante y autor.
check('U-10', 'TreatingPhysicianGuard deja pasar al tratante y exige que el autor sea quien escribe', violation(fn () => $guard->assertAllowed('dra.lopez', 'ep1')) === '' && violation(fn () => $guard->assertAllowed('dra.lopez', 'ep1', 'dra.lopez')) === '' && violation(fn () => $guard->assertAllowed('dr.ruiz', 'ep1', 'dra.lopez')) === 'NOT_TREATING_PHYSICIAN 403');
check('U-11', 'TreatingPhysicianGuard rechaza a quien no atiende el episodio (NOT_TREATING_PHYSICIAN 403)', violation(fn () => $guard->assertAllowed('dr.perez', 'ep1')) === 'NOT_TREATING_PHYSICIAN 403' && violation(fn () => $guard->assertAllowed('dra.lopez', 'ep9')) === 'NOT_TREATING_PHYSICIAN 403');

// RNF-SOAP-02 · integridad: los identificadores son UUID v4 únicos.
check('U-12', 'Uuid::v4 genera identificadores con formato v4 y distintos entre sí', preg_match(Uuid::PATTERN, Uuid::v4()) === 1 && Uuid::v4() !== Uuid::v4());

// Defecto D-01 (equivalencia): el texto «vacío» incluye espacios que trim() no reconoce.
check('U-13', 'SoapSections rechaza una sección con solo espacio duro (U+00A0)', violation(fn () => new SoapSections("\u{00A0}", 'o', 'a', 'p')) === 'SOAP_SECTION_INVALID 422');
check('U-14', 'SoapSections rechaza una sección con solo espacio de ancho cero (U+200B)', violation(fn () => new SoapSections("\u{200B}", 'o', 'a', 'p')) === 'SOAP_SECTION_INVALID 422');
check('U-15', 'Diagnosis rechaza un código con forma distinta de CIE-10 (inyección, espacios, minúsculas) y acepta J18, J18.9 y S72.001A', violation(fn () => new Diagnosis("J18.9'; DROP TABLE diagnoses; --", 'primary')) === 'DIAGNOSIS_INVALID 422' && violation(fn () => new Diagnosis(' J18.9', 'primary')) === 'DIAGNOSIS_INVALID 422' && violation(fn () => new Diagnosis('j18.9', 'primary')) === 'DIAGNOSIS_INVALID 422' && (new Diagnosis('J18', 'primary'))->code === 'J18' && (new Diagnosis('S72.001A', 'secondary'))->code === 'S72.001A');


// UC-SOAP-05 · SignaturePolicy: completitud antes de firmar.
$signer = new SignaturePolicy();
$completa = new ClinicalNote('n1', 'ep1', 'dra.lopez', 'draft', [new Diagnosis('J18.9', 'primary'), new Diagnosis('I10', 'secondary')], new SoapSections('s', 'o', 'a', 'p'));
check('U-16', 'SignaturePolicy deja firmar una nota con cuatro secciones y exactamente un diagnóstico principal', violation(fn () => $signer->assertCanSign($completa)) === '');
check('U-17', 'SignaturePolicy rechaza firmar sin diagnóstico principal o sin secciones (SOAP_INCOMPLETE 422)', violation(fn () => $signer->assertCanSign(new ClinicalNote('n1', 'ep1', 'dra.lopez', 'draft', [new Diagnosis('I10', 'secondary')], new SoapSections('s', 'o', 'a', 'p')))) === 'SOAP_INCOMPLETE 422'
    && violation(fn () => $signer->assertCanSign(new ClinicalNote('n1', 'ep1', 'dra.lopez', 'draft', [new Diagnosis('J18.9', 'primary')]))) === 'SOAP_INCOMPLETE 422');
check('U-18', 'SignaturePolicy rechaza firmar dos veces (NOTE_SIGNED 409) y antes que la completitud', violation(fn () => $signer->assertCanSign(new ClinicalNote('n1', 'ep1', 'dra.lopez', 'signed'))) === 'NOTE_SIGNED 409');
summary();
