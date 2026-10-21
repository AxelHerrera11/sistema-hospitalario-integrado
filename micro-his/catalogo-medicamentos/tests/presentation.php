<?php

declare(strict_types=1);

// Controladores con casos de uso reales sobre el DOBLE FakeCatalog: respuestas de éxito y error.

use MicroHis\MedicationCatalog\Application\ChangeMedicationValidity;
use MicroHis\MedicationCatalog\Application\ConsultMedications;
use MicroHis\MedicationCatalog\Application\MedicationLookup;
use MicroHis\MedicationCatalog\Application\MedicationRegistration;
use MicroHis\MedicationCatalog\Application\MedicationValidityControl;
use MicroHis\MedicationCatalog\Application\RegisterMedication;
use MicroHis\MedicationCatalog\Presentation\RegistrationController;
use MicroHis\MedicationCatalog\Presentation\SearchController;
use MicroHis\MedicationCatalog\Presentation\ValidityController;

require_once __DIR__ . '/FakeCatalog.php';

function controllers(FakeCatalog $fake): array
{
    return [
        new RegistrationController(new RegisterMedication($fake)),
        new SearchController(new ConsultMedications($fake)),
        new ValidityController(new ChangeMedicationValidity($fake)),
    ];
}

$draft = ['code' => ' amx-500 ', 'name' => 'Amoxicilina 500 mg (ficticio)', 'active_ingredient' => 'Amoxicilina', 'pharmacological_group' => 'Antibiótico'];
$fake = new FakeCatalog();
[$alta, $busqueda, $vigencia] = controllers($fake);
$succeeded = static fn (array $response): bool => str_contains(json_encode($response, JSON_UNESCAPED_UNICODE), 'registrado')
    || str_contains(json_encode($response, JSON_UNESCAPED_UNICODE), 'actualizada');

echo "\n== Presentation: alta ==\n";
$created = $alta->register($draft);
check('201 "Medicamento registrado" con código normalizado y active', $created['status'] === 201 && $created['body']['message'] === 'Medicamento registrado'
    && $created['body']['medication']['code'] === 'AMX-500' && $created['body']['medication']['validity'] === 'active');
$invalid = $alta->register(['active_ingredient' => ''] + $draft);
check('422 MEDICATION_INVALID indica el campo', $invalid['status'] === 422 && $invalid['body']['field'] === 'active_ingredient');
check('409 MEDICATION_CODE_DUPLICATED ante el mismo código', $alta->register(['code' => 'Amx-500'] + $draft)['body']['error'] === 'MEDICATION_CODE_DUPLICATED');
[$altaFallida] = controllers(new FakeCatalog(['insert']));
$failedInsert = $altaFallida->register($draft);
check('503 CATALOG_UNAVAILABLE sin mensaje de éxito si falla la escritura', $failedInsert['status'] === 503 && !$succeeded($failedInsert));

echo "\n== Presentation: búsqueda ==\n";
$found = $busqueda->search('amox');
check('200 con resultados y su vigencia', $found['status'] === 200 && $found['body']['count'] === 1 && $found['body']['results'][0]['validity'] === 'active');
check('200 con lista vacía si no hay coincidencias', $busqueda->search('zzz') === ['status' => 200, 'body' => ['results' => [], 'count' => 0]]);
check('422 si el término no es válido', $busqueda->search('a')['status'] === 422);
[, $busquedaFallida] = controllers(new FakeCatalog(['search']));
$failedSearch = $busquedaFallida->search('amox');
check('503 sin "results" si falla la consulta (no se confunde con vacío)', $failedSearch['status'] === 503 && !isset($failedSearch['body']['results']));

echo "\n== Presentation: vigencia ==\n";
$id = $created['body']['medication']['id'];
check('consulta de vigencia: 200 active', $busqueda->validity($id) === ['status' => 200, 'body' => ['id' => $id, 'code' => 'AMX-500', 'validity' => 'active']]);
check('consulta de inexistente: 404 MEDICATION_NOT_FOUND', $busqueda->validity('999')['body']['error'] === 'MEDICATION_NOT_FOUND');
$inactivated = $vigencia->change($id, 'inactive');
check('200 "Vigencia actualizada" y conserva los demás datos', $inactivated['status'] === 200 && $inactivated['body']['message'] === 'Vigencia actualizada'
    && $inactivated['body']['medication'] === array_replace($created['body']['medication'], ['validity' => 'inactive']));
check('422 si el estado no es active/inactive', $vigencia->change($id, 'expired')['body']['field'] === 'validity');
check('404 al cambiar la vigencia de un inexistente', $vigencia->change('999', 'active')['status'] === 404);
$failing = new FakeCatalog(['updateValidity']);
[$altaPrevia, , $vigenciaFallida] = controllers($failing);
$failingId = $altaPrevia->register($draft)['body']['medication']['id'];
$failedUpdate = $vigenciaFallida->change($failingId, 'inactive');
check('503 sin "Vigencia actualizada" si falla la escritura', $failedUpdate['status'] === 503 && !$succeeded($failedUpdate));

echo "\n== Presentation: dependencias ISP ==\n";
$contractOf = static fn (string $class): string => (new ReflectionClass($class))->getConstructor()->getParameters()[0]->getType()->getName();
check('cada controlador depende solo de su contrato segregado', [
    $contractOf(RegistrationController::class), $contractOf(SearchController::class), $contractOf(ValidityController::class),
] === [MedicationRegistration::class, MedicationLookup::class, MedicationValidityControl::class]);
