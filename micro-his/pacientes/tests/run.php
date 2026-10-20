<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use MicroHis\Pacientes\Application\{PatientRepository, PatientService, PersistenceFailure};
use MicroHis\Pacientes\Domain\{DomainRuleViolation, Patient};
use MicroHis\Pacientes\Presentation\PatientController;

final class FakePatientRepository implements PatientRepository
{
    private array $patients = [];

    public function __construct(private bool $fail = false) {}

    public function findByCode(string $code): ?Patient
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        return $this->patients[$code] ?? null;
    }

    public function insert(Patient $patient): Patient
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        $saved = new Patient('id-1', $patient->globalId, $patient->code, $patient->givenName, $patient->familyName, $patient->birthDate, $patient->sex, $patient->contact, $patient->status);
        return $this->patients[$saved->code] = $saved;
    }

    public function update(Patient $patient): Patient
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        return $this->patients[$patient->code] = $patient;
    }
}

$check = static function (bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
};

$repo = new FakePatientRepository();
$controller = new PatientController(new PatientService($repo));
$data = ['global_id' => 'global-1', 'code' => 'P-1', 'given_name' => 'Ana', 'family_name' => 'Paz', 'birth_date' => '1990-01-01', 'sex' => 'female'];
$created = $controller->register($data);
$check($created['status'] === 201, 'registration failed');
$found = $controller->search('P-1');
$check($found['body']['patient']->id === 'id-1', 'search failed');
$updated = $controller->update('P-1', ['given_name' => 'Ana Maria']);
$check($updated['body']['patient']->givenName === 'Ana Maria' && $updated['body']['patient']->code === 'P-1', 'update failed');
echo "PASS camino feliz (registro, búsqueda, actualización)\n";

try {
    Patient::register(array_replace($data, ['birth_date' => '2999-01-01']));
    throw new RuntimeException('future birth date accepted');
} catch (DomainRuleViolation $e) {
    $check($e->errorCode === 'BIRTH_DATE_FUTURE', 'wrong domain rule');
}
echo "PASS regla de dominio (fecha futura)\n";

$failure = (new PatientController(new PatientService(new FakePatientRepository(true))))->search('P-1');
$check($failure['status'] === 500 && $failure['body']['error']['code'] === 'PERSISTENCE_FAILURE', 'persistence failure not translated');
echo "PASS error de persistencia (fake)\n";
