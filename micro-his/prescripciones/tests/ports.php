<?php

declare(strict_types=1);

use MicroHis\Prescripciones\Application\AllergyChecker;
use MicroHis\Prescripciones\Application\AuditLogger;
use MicroHis\Prescripciones\Application\Exception\AllergyCheckUnavailable;
use MicroHis\Prescripciones\Application\Exception\AuditUnavailable;
use MicroHis\Prescripciones\Application\Exception\MedicationNotFound;
use MicroHis\Prescripciones\Application\Exception\PersistenceFailure;
use MicroHis\Prescripciones\Application\MedicationCatalog;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAllergyChecker;
use MicroHis\Prescripciones\Application\Persistence\InMemoryAuditLogger;
use MicroHis\Prescripciones\Application\Persistence\InMemoryMedicationCatalog;
use MicroHis\Prescripciones\Domain\Allergy;
use MicroHis\Prescripciones\Domain\AllergyType;
use MicroHis\Prescripciones\Domain\MedicationSnapshot;
use MicroHis\Prescripciones\Domain\Severity;

// Los adaptadores InMemory cumplen los puertos de aplicación.
check('puertos: InMemoryAllergyChecker implementa AllergyChecker', new InMemoryAllergyChecker instanceof AllergyChecker);
check('puertos: InMemoryMedicationCatalog implementa MedicationCatalog', new InMemoryMedicationCatalog instanceof MedicationCatalog);
check('puertos: InMemoryAuditLogger implementa AuditLogger', new InMemoryAuditLogger instanceof AuditLogger);

// Excepciones: las de infraestructura son RuntimeException; el medicamento ausente es error de dominio.
check('excepción: AllergyCheckUnavailable es RuntimeException con mensaje por defecto',
    new AllergyCheckUnavailable instanceof RuntimeException && (new AllergyCheckUnavailable)->getMessage() === 'Servicio de alergias no disponible.');
check('excepción: AuditUnavailable es RuntimeException', new AuditUnavailable instanceof RuntimeException);
check('excepción: PersistenceFailure conserva la causa', (new PersistenceFailure('x', new LogicException('causa')))->getPrevious() instanceof LogicException);
check('excepción: MedicationNotFound es DomainException y nombra el medicamento',
    new MedicationNotFound('MED-1') instanceof DomainException && str_contains((new MedicationNotFound('MED-1'))->getMessage(), 'MED-1'));

// Alergias: devuelve las del paciente, lista vacía si no tiene y falla cerrada si el servicio cae.
$penicilina = new Allergy('PEN', 'Penicilina', AllergyType::Allergy, Severity::Severe);
$allergies = new InMemoryAllergyChecker(['pac-1' => [$penicilina]]);
check('alergias: devuelve las alergias activas del paciente', $allergies->activeAllergies('pac-1') === [$penicilina]);
check('alergias: un paciente sin registro no tiene alergias', $allergies->activeAllergies('pac-2') === []);
$allergies->setAllergies('pac-2', [$penicilina]);
check('alergias: setAllergies reemplaza la lista del paciente', count($allergies->activeAllergies('pac-2')) === 1);
$allergies->failNext(true);
check('alergias: el servicio caído lanza AllergyCheckUnavailable, no una lista vacía',
    thrown(static fn () => $allergies->activeAllergies('pac-1'), AllergyCheckUnavailable::class) instanceof AllergyCheckUnavailable);
$allergies->clear();
check('alergias: clear borra datos y la falla simulada', $allergies->activeAllergies('pac-1') === []);

// Catálogo: devuelve el medicamento activo; ausente o inactivo da MedicationNotFound.
$amoxicilina = new MedicationSnapshot('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', true);
$catalog = new InMemoryMedicationCatalog([$amoxicilina]);
check('catálogo: encuentra el medicamento activo', $catalog->find('med-1') === $amoxicilina);
check('catálogo: un medicamento inexistente da MedicationNotFound',
    thrown(static fn () => $catalog->find('med-9'), MedicationNotFound::class) instanceof MedicationNotFound);
$catalog->upsert(new MedicationSnapshot('med-1', 'AMX500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Penicilinas', false));
check('catálogo: un medicamento inactivo da MedicationNotFound',
    thrown(static fn () => $catalog->find('med-1'), MedicationNotFound::class) instanceof MedicationNotFound);
$catalog->clear();
check('catálogo: clear vacía el catálogo', thrown(static fn () => $catalog->find('med-1'), MedicationNotFound::class) !== null);

// Auditoría: registra eventos en orden; con falla simulada lanza AuditUnavailable y no guarda nada.
$audit = new InMemoryAuditLogger;
$audit->record('prescripcion.borrador', ['id' => 'p-1']);
$audit->record('prescripcion.firmada', ['id' => 'p-1']);
check('auditoría: registra los eventos en orden', array_column($audit->logs, 'event') === ['prescripcion.borrador', 'prescripcion.firmada']);
check('auditoría: conserva el payload', $audit->logs[0]['payload'] === ['id' => 'p-1']);
$audit->failNext(true);
check('auditoría: con falla simulada lanza AuditUnavailable',
    thrown(static fn () => $audit->record('prescripcion.bloqueada', []), AuditUnavailable::class) instanceof AuditUnavailable);
check('auditoría: el evento fallido no queda registrado', count($audit->logs) === 2);
$audit->clear();
check('auditoría: clear vacía los registros', $audit->logs === []);
