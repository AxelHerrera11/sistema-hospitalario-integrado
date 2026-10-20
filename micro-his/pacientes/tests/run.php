<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use MicroHis\Pacientes\Application\PatientRepository;
use MicroHis\Pacientes\Application\PatientService;
use MicroHis\Pacientes\Application\PersistenceFailure;
use MicroHis\Pacientes\Domain\DomainRuleViolation;
use MicroHis\Pacientes\Domain\Patient;
use MicroHis\Pacientes\Persistence\InMemoryPatientRepository;
use MicroHis\Pacientes\Presentation\PatientController;

final class FailingPatientRepository implements PatientRepository
{
    public function findByCode(string $code): ?Patient
    {
        throw new PersistenceFailure('fake failure');
    }

    public function insert(Patient $patient): Patient
    {
        throw new PersistenceFailure('fake failure');
    }

    public function update(Patient $patient): Patient
    {
        throw new PersistenceFailure('fake failure');
    }
}

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$data = [
    'global_id' => 'global-1',
    'code' => 'P-1',
    'given_name' => 'Ana',
    'family_name' => 'Paz',
    'birth_date' => '1990-01-01',
    'sex' => 'female',
];

$repository = new InMemoryPatientRepository();

$controller = new PatientController(
    new PatientService($repository)
);

$created = $controller->register($data);

$check($created['status'] === 201, 'registration failed');

$check(
    $created['body']['patient']['id'] === 'mem-1',
    'in-memory id was not generated'
);

$check(
    $created['body']['patient']['code'] === 'P-1',
    'view did not serialize patient'
);

$found = $controller->search('P-1');

$check(
    $found['status'] === 200 &&
    $found['body']['patient']['given_name'] === 'Ana',
    'search failed'
);

$updated = $controller->update(
    'P-1',
    ['given_name' => 'Ana Maria']
);

$check(
    $updated['body']['patient']['given_name'] === 'Ana Maria',
    'update failed'
);

$check(
    $updated['body']['patient']['code'] === 'P-1',
    'patient identity changed during update'
);

echo "PASS MVC + InMemory (registro, búsqueda y actualización)\n";

$duplicate = $controller->register($data);

$check(
    $duplicate['status'] === 422 &&
    $duplicate['body']['error']['code'] === 'PATIENT_CODE_EXISTS',
    'duplicate patient code was accepted'
);

echo "PASS regla de aplicación (código duplicado)\n";

try {
    Patient::register(
        array_replace(
            $data,
            ['birth_date' => '2999-01-01']
        )
    );

    throw new RuntimeException('future birth date accepted');
} catch (DomainRuleViolation $e) {
    $check(
        $e->errorCode === 'BIRTH_DATE_FUTURE',
        'wrong domain rule'
    );
}

echo "PASS regla de dominio (fecha futura)\n";

$failureController = new PatientController(
    new PatientService(new FailingPatientRepository())
);

$failure = $failureController->search('P-1');

$check(
    $failure['status'] === 500 &&
    $failure['body']['error']['code'] === 'PERSISTENCE_FAILURE',
    'persistence failure not translated'
);

echo "PASS error de persistencia (double)\n";
