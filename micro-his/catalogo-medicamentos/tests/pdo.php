<?php

declare(strict_types=1);

// Pruebas REALES del adaptador PDO, separadas de las de dobles: php micro-his/catalogo-medicamentos/tests/pdo.php
// Base de prueba aislada: SQLite en memoria (sqlite::memory:), creada y descartada por este proceso.

use MicroHis\MedicationCatalog\Application\ChangeMedicationValidity;
use MicroHis\MedicationCatalog\Application\ConsultMedications;
use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Application\RegisterMedication;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;
use MicroHis\MedicationCatalog\Presentation\RegistrationController;
use MicroHis\MedicationCatalog\Presentation\SearchController;
use MicroHis\MedicationCatalog\Presentation\ValidityController;

require __DIR__ . '/harness.php';

function isolatedDatabase(bool $withSchema = true): PDO
{
    $pdo = new PDO('sqlite::memory:');
    if ($withSchema) {
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
    }

    return $pdo;
}

$draft = static fn (string $code, string $name, string $ingredient): array => ['code' => $code, 'name' => $name, 'active_ingredient' => $ingredient, 'pharmacological_group' => 'Grupo ficticio'];
$pdo = isolatedDatabase();
$catalog = new PdoMedicationCatalog($pdo);
echo 'Base de prueba: ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . " en memoria, SQLite " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";

echo "\n== PDO: alta y lectura ==\n";
$saved = $catalog->insert(Medication::register($draft('amx-500', 'Amoxicilina 500 mg', 'Amoxicilina')));
check('insert confirma el registro con el id generado por la base', $saved->id === '1' && $catalog->findById('1')?->code === 'AMX-500');
check('findByCode encuentra el código normalizado y devuelve null si no existe', $catalog->findByCode('AMX-500')?->id === '1' && $catalog->findByCode('NO-EXISTE') === null);
check('la restricción UNIQUE se traduce a DuplicateMedicationCode', thrown(static fn () => $catalog->insert(Medication::register($draft('AMX-500', 'Otro', 'Otro'))), DuplicateMedicationCode::class) !== null);
check('findById de un identificador no numérico devuelve null', $catalog->findById('abc') === null);

echo "\n== PDO: búsqueda con sentencias preparadas ==\n";
$catalog->insert(Medication::register($draft('ibu-400', 'Ibuprofeno 400 mg', 'Ibuprofeno')));
$catalog->insert(Medication::register($draft('pct-100', 'Paracetamol 100%', 'Paracetamol')));
$codes = static fn (array $rows): array => array_map(static fn (Medication $m): string => $m->code, $rows);
check('busca sin distinguir mayúsculas por nombre, código o principio activo', $codes($catalog->search('IBUPRO', 50)) === ['IBU-400'] && $codes($catalog->search('amx', 50)) === ['AMX-500']);
check('LIMIT se respeta', count($catalog->search('0', 2)) === 2);
check('"%" se trata como literal y no como comodín', $codes($catalog->search('0%', 50)) === ['PCT-100'] && $catalog->search('%%', 50) === []);
check("un intento de inyección (' OR 1=1 --) no devuelve filas ni altera la tabla", $catalog->search("' OR 1=1 --", 50) === []
    && (int) $pdo->query('SELECT COUNT(*) FROM medications')->fetchColumn() === 3);

echo "\n== PDO: vigencia ==\n";
$catalog->updateValidity('2', Validity::Inactive);
check('updateValidity persiste inactive sin borrar ni cambiar los datos', $catalog->findById('2')?->validity === Validity::Inactive && $catalog->findById('2')?->name === 'Ibuprofeno 400 mg');
check('updateValidity sin fila afectada no se confirma (PersistenceFailure)', thrown(static fn () => $catalog->updateValidity('99', Validity::Active), PersistenceFailure::class) !== null);
check('el CHECK de la base rechaza estados fuera de active/inactive', thrown(static fn () => $pdo->exec("UPDATE medications SET validity = 'expired' WHERE medication_id = 1"), PDOException::class) !== null);

echo "\n== PDO: error de persistencia real (base sin esquema) ==\n";
$broken = new PdoMedicationCatalog(isolatedDatabase(withSchema: false));
foreach (['insert' => [Medication::register($draft('NEW-1', 'Nuevo', 'Nuevo'))], 'findByCode' => ['X'], 'findById' => ['1'], 'search' => ['ab', 5], 'updateValidity' => ['1', Validity::Active]] as $method => $args) {
    check("{$method} sin tabla lanza PersistenceFailure", thrown(static fn () => $broken->{$method}(...$args), PersistenceFailure::class)?->getPrevious() instanceof PDOException);
}
$failedAlta = (new RegistrationController(new RegisterMedication($broken)))->register($draft('NEW-1', 'Nuevo', 'Nuevo'));
check('el flujo completo responde 503 y no informa éxito', $failedAlta['status'] === 503 && !isset($failedAlta['body']['medication']));

echo "\n== PDO: flujo completo con controladores ==\n";
$flowCatalog = new PdoMedicationCatalog(isolatedDatabase());
$created = (new RegistrationController(new RegisterMedication($flowCatalog)))->register($draft('lor-10', 'Loratadina 10 mg', 'Loratadina'));
$search = new SearchController(new ConsultMedications($flowCatalog));
$changed = (new ValidityController(new ChangeMedicationValidity($flowCatalog)))->change($created['body']['medication']['id'], 'inactive');
check('alta 201, cambio 200 y la consulta posterior lee inactive desde la base', $created['status'] === 201 && $changed['status'] === 200
    && $search->validity($created['body']['medication']['id'])['body']['validity'] === 'inactive' && $search->search('lorat')['body']['count'] === 1);

finish();
