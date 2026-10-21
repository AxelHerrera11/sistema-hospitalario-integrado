<?php

declare(strict_types=1);

// Casos de prueba de Semana 15, trazados a RF/RNF (ver docs/catalogo-medicamentos/semana-15/matriz-pruebas.md).
// U = unitaria, W = caja blanca, B = caja negra. Uso: php micro-his/catalogo-medicamentos/tests/casos.php

use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\MedicationRuleViolation;
use MicroHis\MedicationCatalog\Domain\Validity;
use MicroHis\MedicationCatalog\Persistence\InMemoryMedicationRepository;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;
use MicroHis\MedicationCatalog\Presentation\Request;

require __DIR__ . '/harness.php';
require __DIR__ . '/../app.php';

$draft = static fn (array $overrides = []): array => $overrides + ['code' => 'amx-500', 'name' => 'Amoxicilina 500 mg', 'active_ingredient' => 'Amoxicilina', 'pharmacological_group' => 'Antibiótico'];
$sqlite = static function (): PDO {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec((string) file_get_contents(__DIR__ . '/../database/schema.sql'));

    return $pdo;
};
$field = static fn (callable $action): ?string => thrown($action, MedicationRuleViolation::class)?->field;

echo "== Unitarias ==\n";
check('U1 Validity::fromInput normaliza " INACTIVE " y rechaza "expired" (RF-06)', Validity::fromInput(' INACTIVE ') === Validity::Inactive
    && $field(static fn () => Validity::fromInput('expired')) === 'validity');
$m = Medication::register($draft(['code' => ' amx-500 ', 'name' => '  Amoxicilina 500 mg  ']));
check('U2 register normaliza el código a mayúsculas, recorta textos y nace active (RF-01, RF-02)', $m->code === 'AMX-500'
    && $m->name === 'Amoxicilina 500 mg' && $m->validity === Validity::Active);
$stored = Medication::restore('7', 'AMX-500', 'Amoxicilina 500 mg', 'Amoxicilina', 'Antibiótico', Validity::Active);
$changed = $stored->changeValidity(Validity::Inactive);
check('U3 changeValidity conserva id, código y nombre y rechaza repetir el estado (RF-06)', [$changed->id, $changed->code, $changed->name] === ['7', 'AMX-500', 'Amoxicilina 500 mg']
    && $field(static fn () => $changed->changeValidity(Validity::Inactive)) === 'validity');

echo "\n== Caja blanca ==\n";
// W1: Router::withJson. Condición: !is_array(b) || (array_is_list(b) && b !== []); luego cada campo conocido debe ser string.
$router = catalog_router(new InMemoryMedicationRepository());
$post = static fn (string $body): array => $router->dispatch(new Request('POST', '/medications', [], $body));
check('W1a !is_array verdadero (no es JSON) -> 400 BODY_INVALID', $post('no-json')['body']['error'] === 'BODY_INVALID');
check('W1b lista no vacía -> 400 BODY_INVALID', $post('["x"]')['body']['error'] === 'BODY_INVALID');
check('W1c lista vacía [] equivale a objeto vacío: pasa la forma y el dominio responde 422 en code', $post('[]')['body']['field'] === 'code');
check('W1d campo conocido no textual -> 422 con el campo, sin llegar al caso de uso', $post('{"name":7}')['body']['field'] === 'name');
check('W1e objeto válido -> 201 (rama del handler)', $post(json_encode($draft(), JSON_UNESCAPED_UNICODE))['status'] === 201);
// W2: PdoMedicationCatalog::insert. Ramas del catch y de la confirmación, forzadas con triggers reales de SQLite.
$pdo = $sqlite();
$repo = new PdoMedicationCatalog($pdo);
$repo->insert(Medication::register($draft()));
check('W2a SQLSTATE 23000 con "unique" -> DuplicateMedicationCode', thrown(static fn () => $repo->insert(Medication::register($draft(['code' => 'AMX-500']))), DuplicateMedicationCode::class) !== null);
$pdo->exec("CREATE TRIGGER rechazo BEFORE INSERT ON medications WHEN NEW.code = 'BLQ-1' BEGIN SELECT RAISE(ABORT, 'regla local'); END");
check('W2b SQLSTATE 23000 sin "unique" -> PersistenceFailure (no se confunde con duplicado)', thrown(static fn () => $repo->insert(Medication::register($draft(['code' => 'BLQ-1']))), PersistenceFailure::class) !== null);
$pdo->exec("CREATE TRIGGER ignorar BEFORE INSERT ON medications WHEN NEW.code = 'IGN-1' BEGIN SELECT RAISE(IGNORE); END");
check('W2c 0 filas afectadas -> PersistenceFailure (no se confirma el alta)', thrown(static fn () => $repo->insert(Medication::register($draft(['code' => 'IGN-1']))), PersistenceFailure::class) !== null
    && $repo->findByCode('IGN-1') === null);
$pdo->exec('DROP TABLE medications');
check('W2d otro error PDO (sin tabla) -> PersistenceFailure', thrown(static fn () => $repo->insert(Medication::register($draft(['code' => 'NEW-1']))), PersistenceFailure::class) !== null);

echo "\n== Caja negra ==\n";
$api = catalog_router(new PdoMedicationCatalog($sqlite()));
$call = static fn (string $method, string $path, array $query = [], string $body = ''): array => $api->dispatch(new Request($method, $path, $query, $body));
$call('POST', '/medications', [], json_encode($draft(), JSON_UNESCAPED_UNICODE));
$call('POST', '/medications', [], json_encode($draft(['code' => 'ace-1', 'name' => 'ÁCIDO ACETILSALICÍLICO', 'active_ingredient' => 'Ácido acetilsalicílico']), JSON_UNESCAPED_UNICODE));
$status = static fn (array $query): int => $call('GET', '/medications', $query)['status'];
check('B1 equivalencias del término: válido con resultados (200), válido sin resultados (200 vacío), inválido (422) (RF-04)',
    $call('GET', '/medications', ['q' => 'amox'])['body']['count'] === 1 && $call('GET', '/medications', ['q' => 'zzz'])['body']['count'] === 0
    && $status(['q' => 'a']) === 422);
$codeStatus = static fn (string $code): int => $call('POST', '/medications', [], json_encode($draft(['code' => $code])))['status'];
check('B2 límites: término 1/2/60/61, código 1/2/20/21 y nombre 120/121 (RF-02, RF-04)',
    [$status(['q' => 'a']), $status(['q' => 'am']), $status(['q' => str_repeat('a', 60)]), $status(['q' => str_repeat('a', 61)])] === [422, 200, 200, 422]
    && [$codeStatus('A'), $codeStatus('B2'), $codeStatus(str_repeat('C', 20)), $codeStatus(str_repeat('D', 21))] === [422, 201, 201, 422]
    && $call('POST', '/medications', [], json_encode($draft(['code' => 'N-120', 'name' => str_repeat('n', 120)])))['status'] === 201
    && $call('POST', '/medications', [], json_encode($draft(['code' => 'N-121', 'name' => str_repeat('n', 121)])))['body']['field'] === 'name');
$rbac = require dirname(__DIR__, 3) . '/config/rbac.php';
$withVer = array_keys(array_filter($rbac['roles'], static fn (array $p): bool => in_array('medicamentos.ver', $p, true)));
sort($withVer);
$writers = array_filter($rbac['roles'], static fn (array $p): bool => array_intersect($p, ['medicamentos.registrar', 'medicamentos.cambiar_vigencia']) !== []);
check('B3 permisos (matriz real de config/rbac.php): lectura solo para admin_hospital, enfermeria, medico y superadmin; ningún rol escribe; recepcion no lee (RF-07)',
    $withVer === ['admin_hospital', 'enfermeria', 'medico', 'superadmin'] && $writers === [] && !in_array('medicamentos.ver', $rbac['roles']['recepcion'], true));
$broken = catalog_router(new PdoMedicationCatalog(new PDO('sqlite::memory:')));
check('B4 errores: 409 duplicado, 404 inexistente, 405 método, 404 ruta y 503 sin éxito (RF-03, RF-05, RF-08, RNF-03)',
    $call('POST', '/medications', [], json_encode($draft()))['status'] === 409 && $call('GET', '/medications/999/validity')['status'] === 404
    && $call('DELETE', '/medications/1/validity')['status'] === 405 && $call('GET', '/x')['status'] === 404
    && $broken->dispatch(new Request('GET', '/medications', ['q' => 'amox']))['body'] === ['error' => 'CATALOG_UNAVAILABLE', 'message' => 'El catálogo no está disponible; no se confirmó la operación.']);
check('B5 regresión AV3: "ácido" y "ÁCIDO" encuentran el mismo registro por la interfaz (RF-04)', $call('GET', '/medications', ['q' => 'ácido'])['body']['count'] === 1
    && $call('GET', '/medications', ['q' => 'ÁCIDO'])['body']['count'] === 1);

finish();
