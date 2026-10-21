<?php

declare(strict_types=1);

// Punto de composición por consola (sin API ni frontend). Uso:
//   php bin/catalogo.php <base.sqlite> init
//   php bin/catalogo.php <base.sqlite> alta <codigo> <nombre> <principio_activo> <grupo>
//   php bin/catalogo.php <base.sqlite> buscar <termino>
//   php bin/catalogo.php <base.sqlite> vigencia <id>
//   php bin/catalogo.php <base.sqlite> cambiar <id> <active|inactive>
// Salida: JSON {status, body}. Código de salida 0 si status < 400, 1 si no y 2 por uso incorrecto.

use MicroHis\MedicationCatalog\Application\ChangeMedicationValidity;
use MicroHis\MedicationCatalog\Application\ConsultMedications;
use MicroHis\MedicationCatalog\Application\RegisterMedication;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;
use MicroHis\MedicationCatalog\Presentation\RegistrationController;
use MicroHis\MedicationCatalog\Presentation\SearchController;
use MicroHis\MedicationCatalog\Presentation\ValidityController;

require __DIR__ . '/../bootstrap.php';

[, $database, $command] = $argv + [null, null, null];
$arguments = array_slice($argv, 3);
$arity = ['init' => 0, 'alta' => 4, 'buscar' => 1, 'vigencia' => 1, 'cambiar' => 2];
if ($database === null || !isset($arity[$command]) || count($arguments) !== $arity[$command]) {
    fwrite(STDERR, "Uso: php bin/catalogo.php <base.sqlite> init|alta|buscar|vigencia|cambiar ...\n");
    exit(2);
}
if ($command !== 'init' && !is_file($database)) {
    fwrite(STDERR, "La base {$database} no existe; ejecute primero el comando init.\n");
    exit(2);
}

try {
    $pdo = new PDO('sqlite:' . $database, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if ($command === 'init') {
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));
        $response = ['status' => 200, 'body' => ['message' => 'Esquema aplicado', 'database' => $database]];
    } else {
        $catalog = new PdoMedicationCatalog($pdo);
        $response = match ($command) {
            'alta' => (new RegistrationController(new RegisterMedication($catalog)))->register(array_combine(
                ['code', 'name', 'active_ingredient', 'pharmacological_group'], $arguments)),
            'buscar' => (new SearchController(new ConsultMedications($catalog)))->search($arguments[0]),
            'vigencia' => (new SearchController(new ConsultMedications($catalog)))->validity($arguments[0]),
            'cambiar' => (new ValidityController(new ChangeMedicationValidity($catalog)))->change($arguments[0], $arguments[1]),
        };
    }
} catch (PDOException) {
    $response = ['status' => 503, 'body' => ['error' => 'CATALOG_UNAVAILABLE', 'message' => 'No se pudo abrir la base del catálogo.']];
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
exit($response['status'] < 400 ? 0 : 1);
