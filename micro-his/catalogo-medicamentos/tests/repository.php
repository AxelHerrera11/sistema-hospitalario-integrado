<?php

declare(strict_types=1);

// Prueba de CONTRATO del Repository: las mismas verificaciones contra los dos adaptadores reales.
// PDO usa una base aislada sqlite::memory: por proceso; InMemory no usa base de datos.
// Uso: php micro-his/catalogo-medicamentos/tests/repository.php

use MicroHis\MedicationCatalog\Application\ChangeMedicationValidity;
use MicroHis\MedicationCatalog\Application\DuplicateMedicationCode;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Application\PersistenceFailure;
use MicroHis\MedicationCatalog\Application\RegisterMedication;
use MicroHis\MedicationCatalog\Domain\Medication;
use MicroHis\MedicationCatalog\Domain\Validity;
use MicroHis\MedicationCatalog\Persistence\InMemoryMedicationRepository;
use MicroHis\MedicationCatalog\Persistence\PdoMedicationCatalog;

require __DIR__ . '/harness.php';

$adapters = [
    'InMemory' => static fn (): MedicationRepository => new InMemoryMedicationRepository(),
    'PDO' => static function (): MedicationRepository {
        $pdo = new PDO('sqlite::memory:');
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sql'));

        return new PdoMedicationCatalog($pdo);
    },
];
$draft = static fn (string $code, string $name, string $ingredient): array => ['code' => $code, 'name' => $name, 'active_ingredient' => $ingredient, 'pharmacological_group' => 'Grupo ficticio'];
$codes = static fn (array $rows): array => array_map(static fn (Medication $m): string => $m->code, $rows);
$observed = [];

foreach ($adapters as $name => $make) {
    echo "\n== Contrato del Repository: {$name} ==\n";
    $repo = $make();
    $saved = $repo->insert(Medication::register($draft('ibu-400', 'Ibuprofeno 400 mg', 'Ibuprofeno')));
    $repo->insert(Medication::register($draft('amx-500', 'Amoxicilina 500 mg', 'Amoxicilina')));
    $repo->insert(Medication::register($draft('pct-100', 'Paracetamol 100%', 'Paracetamol')));

    check("{$name}: insert devuelve el registro confirmado y se lee por id y por código", $saved->id !== null
        && $repo->findById($saved->id)?->code === 'IBU-400' && $repo->findByCode('IBU-400')?->id === $saved->id);
    check("{$name}: id y código inexistentes devuelven null", $repo->findById('999') === null && $repo->findByCode('NO-EXISTE') === null);
    check("{$name}: el código duplicado lanza DuplicateMedicationCode",
        thrown(static fn () => $repo->insert(Medication::register($draft('IBU-400', 'Otro', 'Otro'))), DuplicateMedicationCode::class) !== null);
    check("{$name}: busca sin distinguir mayúsculas por código, nombre o principio activo",
        $codes($repo->search('AMOXI', 50)) === ['AMX-500'] && $codes($repo->search('ibu', 50)) === ['IBU-400']);
    check("{$name}: ordena por nombre y respeta el límite", $codes($repo->search('0', 50)) === ['AMX-500', 'IBU-400', 'PCT-100']
        && $codes($repo->search('0', 2)) === ['AMX-500', 'IBU-400']);
    check("{$name}: '%' es literal, no comodín", $codes($repo->search('0%', 50)) === ['PCT-100'] && $repo->search('%%', 50) === []);

    $repo->updateValidity($saved->id, Validity::Inactive);
    $after = $repo->findById($saved->id);
    check("{$name}: updateValidity persiste inactive y conserva el registro y sus datos",
        $after?->validity === Validity::Inactive && $after->name === 'Ibuprofeno 400 mg' && $codes($repo->search('ibu', 50)) === ['IBU-400']);
    check("{$name}: updateValidity sin registro no se confirma (PersistenceFailure)",
        thrown(static fn () => $repo->updateValidity('999', Validity::Active), PersistenceFailure::class) !== null);

    $flow = $make();
    $created = (new RegisterMedication($flow))->register($draft('lor-10', 'Loratadina 10 mg', 'Loratadina'));
    $changed = (new ChangeMedicationValidity($flow))->changeValidity($created->id, Validity::Inactive);
    check("{$name}: los casos de uso funcionan sobre el adaptador", $changed->validity === Validity::Inactive
        && $flow->findById($created->id)?->validity === Validity::Inactive);

    $observed[$name] = [$codes($repo->search('0', 50)), $after->validity, $after->code];
}

echo "\n== Equivalencia entre adaptadores ==\n";
check('InMemory y PDO producen los mismos resultados observables', $observed['InMemory'] === $observed['PDO']);

finish();
