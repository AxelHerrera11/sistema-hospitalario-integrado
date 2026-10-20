<?php

declare(strict_types=1);

// Casos de uso con DOBLES (FakeCatalog): camino feliz, reglas y error de persistencia por operación.

use MicroHis\MedicationCatalog\Application\ChangeMedicationValidity;
use MicroHis\MedicationCatalog\Application\ConsultMedications;
use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\MedicationNotFound;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Application\RegisterMedication;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;
use MicroHis\MedicationCatalog\Domain\Validity;

require_once __DIR__ . '/FakeCatalog.php';

echo "\n== Application: alta ==\n";
$draft = ['code' => 'ibu-400', 'name' => 'Ibuprofeno 400 mg (ficticio)', 'active_ingredient' => 'Ibuprofeno', 'pharmacological_group' => 'AINE'];
$fake = new FakeCatalog();
$registered = (new RegisterMedication($fake))->register($draft);
check('camino feliz: devuelve el registro confirmado con id y vigencia active', $registered->id === '1' && $registered->validity === Validity::Active && $fake->writes === 1);
check('duplicado del código normalizado: DuplicateMedicationCode sin escribir', thrown(static fn () => (new RegisterMedication($fake))->register(['code' => 'IBU-400'] + $draft), DuplicateMedicationCode::class) !== null && $fake->writes === 1);
check('regla de dominio: datos inválidos no llegan a la persistencia', thrown(static fn () => (new RegisterMedication($fake))->register(['name' => ''] + $draft), MedicationRuleViolation::class) !== null && $fake->writes === 1);
check('persistencia: un fallo al insertar se propaga y no hay resultado', thrown(static fn () => (new RegisterMedication(new FakeCatalog(['insert'])))->register($draft), PersistenceFailure::class) !== null);
check('persistencia: un fallo al comprobar el código se propaga', thrown(static fn () => (new RegisterMedication(new FakeCatalog(['findByCode'])))->register($draft), PersistenceFailure::class) !== null);

echo "\n== Application: búsqueda ==\n";
$lookup = new ConsultMedications($fake);
check('camino feliz: encuentra por principio activo sin distinguir mayúsculas', array_map(static fn (Medication $m): string => $m->code, $lookup->search('ibuprofeno')) === ['IBU-400']);
check('sin coincidencias devuelve lista vacía', $lookup->search('zzz') === []);
check('regla: término menor de 2 caracteres se rechaza', thrown(static fn () => $lookup->search(' i '), MedicationRuleViolation::class)?->field === 'term');
check('persistencia: un fallo de búsqueda se propaga (no lista vacía)', thrown(static fn () => (new ConsultMedications(new FakeCatalog(['search'])))->search('ibu'), PersistenceFailure::class) !== null);
check('findById de inexistente devuelve null', $lookup->findById('99') === null);

echo "\n== Application: vigencia ==\n";
$control = new ChangeMedicationValidity($fake);
$changed = $control->changeValidity('1', Validity::Inactive);
check('camino feliz: inactiva y el registro persiste con sus datos', $changed->validity === Validity::Inactive && $fake->rows['1']->validity === Validity::Inactive && $fake->rows['1']->name === $draft['name']);
check('inactivo sigue apareciendo en la búsqueda (no se borra)', $lookup->search('IBU')[0]->validity === Validity::Inactive);
check('inexistente: MedicationNotFound sin escribir', thrown(static fn () => $control->changeValidity('99', Validity::Active), MedicationNotFound::class) !== null && $fake->writes === 2);
check('regla: repetir inactive no escribe', thrown(static fn () => $control->changeValidity('1', Validity::Inactive), MedicationRuleViolation::class) !== null && $fake->writes === 2);
$failing = new FakeCatalog(['updateValidity']);
$failing->rows['1'] = $registered;
check('persistencia: fallo al actualizar se propaga y el estado no cambia', thrown(static fn () => (new ChangeMedicationValidity($failing))->changeValidity('1', Validity::Inactive), PersistenceFailure::class) !== null
    && $failing->rows['1']->validity === Validity::Active);
