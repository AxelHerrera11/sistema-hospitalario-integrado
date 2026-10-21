<?php

declare(strict_types=1);

// Controlador frontal HTTP (sin framework). Ejemplo local:
//   MICROHIS_CATALOG_DB=/tmp/catalogo.sqlite php micro-his/catalogo-medicamentos/bin/catalogo.php /tmp/catalogo.sqlite init
//   MICROHIS_CATALOG_DB=/tmp/catalogo.sqlite php -S 127.0.0.1:8085 micro-his/catalogo-medicamentos/public/index.php

use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;
use MicroHis\MedicationCatalog\Presentation\JsonView;
use MicroHis\MedicationCatalog\Presentation\Request;

require __DIR__ . '/../app.php';

$database = getenv('MICROHIS_CATALOG_DB') ?: '';
try {
    if ($database === '' || !is_file($database)) {
        throw new PDOException('Base del catálogo no configurada.');
    }
    $response = catalog_router(new PdoMedicationCatalog(new PDO('sqlite:' . $database)))->dispatch(Request::fromGlobals());
} catch (PDOException) {
    $response = ['status' => 503, 'body' => ['error' => 'CATALOG_UNAVAILABLE', 'message' => 'No se pudo abrir la base del catálogo.']];
}
(new JsonView())->send($response);
