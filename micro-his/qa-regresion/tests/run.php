<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use MicroHis\QaRegresion\Application\DesignCase;
use MicroHis\QaRegresion\Domain\{CaseRepository, ManualCase, StorageFailure};
use MicroHis\QaRegresion\Persistence\{InMemoryCaseRepository, PdoCaseRepository};
use MicroHis\QaRegresion\Presentation\CaseController;

$passed = $failed = 0;
function check(bool $condition): void { if (!$condition) { throw new RuntimeException('Expectativa incumplida'); } }
$test = static function (string $name, callable $run) use (&$passed, &$failed): void {
    try { $run(); ++$passed; echo "PASS $name\n"; }
    catch (Throwable $error) { ++$failed; echo "FAIL $name: {$error->getMessage()}\n"; }
};
$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(dirname(__DIR__).'/database/schema.sqlite.sql'));
$input = ['purpose' => " Revisar ' OR 1=1 -- ", 'steps' => '1. Abrir pantalla', 'expected' => 'Contenido visible'];
foreach (['InMemory' => new InMemoryCaseRepository(), 'PDO' => new PdoCaseRepository($pdo)] as $name => $repo) {
    $controller = new CaseController(new DesignCase($repo));
    $test("$name camino feliz y lectura", static function () use ($controller, $repo, $input): void {
        $response = $controller->create($input);
        check($response['status'] === 201);
        $data = $response['body']['data'];
        check(strlen($data['id']) === 32 && $data['purpose'] === trim($input['purpose']));
        check(get_object_vars($repo->find($data['id'])) === $data);
        $second = $controller->create($input);
        check($second['body']['data']['id'] !== $data['id']);
        check(get_object_vars($repo->find($data['id'])) === $data);
    });
    foreach (array_keys($input) as $field) {
        $test("$name rechaza $field vacío", static function () use ($controller, $input, $field): void {
            check($controller->create(array_replace($input, [$field => '  ']))['status'] === 422);
        });
    }
    $test("$name formato inválido", static function () use ($controller): void {
        check($controller->create(['purpose' => []])['status'] === 400);
        check($controller->create(null)['status'] === 400);
    });
    $test("$name inexistente", static function () use ($repo): void { check($repo->find('missing') === null); });
    $test("$name duplicado no sobrescribe", static function () use ($repo): void {
        $case = new ManualCase('fixed', 'Propósito', 'Pasos', 'Esperado');
        $repo->save($case);
        try { $repo->save(new ManualCase('fixed', 'Otro', 'Pasos', 'Esperado')); throw new LogicException('Duplicado aceptado'); }
        catch (StorageFailure) { check(get_object_vars($repo->find('fixed')) === get_object_vars($case)); }
    });
}
$broken = new class implements CaseRepository {
    public function save(ManualCase $case): void { throw new StorageFailure('detalle privado'); }
    public function find(string $id): ?ManualCase { throw new StorageFailure('detalle privado'); }
};
$missingTable = new PdoCaseRepository(new PDO('sqlite::memory:'));
foreach (['doble' => $broken, 'PDO sin tabla' => $missingTable] as $name => $repo) {
    $test("$name fallo seguro", static function () use ($repo, $input): void {
        $result = (new CaseController(new DesignCase($repo)))->create($input);
        check($result === ['status' => 503, 'body' => ['error' => 'CASE_STORAGE_UNAVAILABLE']]);
    });
}
$test('PDO fallo de consulta traducido', static function () use ($missingTable): void {
    try { $missingTable->find('x'); throw new LogicException('Fallo omitido'); }
    catch (StorageFailure $error) { check($error->getPrevious() instanceof PDOException); }
});
$test('vista conserva campos y sobre JSON público', static function (): void {
    $case = new ManualCase('case-view', 'Propósito', "Paso 1\nPaso 2", 'Esperado');
    $response = MicroHis\QaRegresion\Presentation\CaseView::created($case);
    check($response === ['status' => 201, 'body' => ['data' => [
        'purpose' => 'Propósito', 'steps' => "Paso 1\nPaso 2", 'expected' => 'Esperado', 'id' => 'case-view',
    ]]]);
});
echo "$passed aprobadas, $failed fallidas\n";
exit($failed === 0 ? 0 : 1);
