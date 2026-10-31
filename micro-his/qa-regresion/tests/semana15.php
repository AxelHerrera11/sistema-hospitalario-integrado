<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use MicroHis\QaRegresion\Application\DesignCase;
use MicroHis\QaRegresion\Domain\{CaseRepository, ManualCase, StorageFailure};
use MicroHis\QaRegresion\Presentation\CaseController;

// Runner independiente: no altera los 17 casos de run.php ni usa una base externa.
function s15check(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}
function s15same(mixed $expected, mixed $actual): void {
    s15check($expected === $actual, 'Esperado '.json_encode($expected).' observado '.json_encode($actual));
}
final class S15Repository implements CaseRepository {
    public array $saved = [];
    public int $writes = 0;
    public int $reads = 0;
    public bool $fail = false;
    public function save(ManualCase $case): void {
        ++$this->writes;
        if ($this->fail) { throw new StorageFailure('detalle privado de prueba'); }
        $this->saved[] = $case;
    }
    public function find(string $id): ?ManualCase { ++$this->reads; return null; }
}
function s15controller(S15Repository $repo): CaseController { return new CaseController(new DesignCase($repo)); }
function s15cli(string $input): array {
    $process = proc_open([PHP_BINARY, dirname(__DIR__).'/demo.php'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    s15check(is_resource($process), 'No se pudo iniciar CLI');
    fwrite($pipes[0], $input); fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $code = proc_close($process);
    s15same(0, $code); s15same('', $error);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}
$input = ['purpose' => 'Caso ficticio S15', 'steps' => "1. Abrir\n2. Revisar", 'expected' => 'Campos visibles'];
$tests = [];
$tests['UT-01'] = static function (): string {
    $rejected = 0;
    foreach ([['', 'P', 'S', 'E'], ['id', ' ', 'S', 'E'], ['id', 'P', "\t", 'E'], ['id', 'P', 'S', "\n"]] as $args) {
        try { new ManualCase(...$args); } catch (DomainException) { ++$rejected; }
    }
    s15same(4, $rejected);
    return '4 definiciones incompletas rechazadas por DomainException';
};
$tests['UT-02'] = static function (): string {
    $case = new ManualCase('fijo', '  Propósito  ', "  Paso 1\nPaso 2  ", " Esperado\t");
    s15same(['fijo', 'Propósito', "Paso 1\nPaso 2", 'Esperado'], [$case->id, $case->purpose, $case->steps, $case->expected]);
    $immutable = false;
    try { $case->expected = 'Alterado'; } catch (Error) { $immutable = true; }
    s15check($immutable, 'Entidad modificable'); s15same('Esperado', $case->expected);
    return 'textos normalizados, saltos internos conservados y entidad readonly';
};
$tests['UT-03'] = static function () use ($input): string {
    $repo = new S15Repository();
    $case = (new DesignCase($repo))->execute(...array_values($input));
    s15same(1, $repo->writes); s15same(0, $repo->reads); s15same(1, count($repo->saved));
    s15check($repo->saved[0] === $case, 'No guarda la entidad retornada');
    s15same(array_values($input), [$case->purpose, $case->steps, $case->expected]);
    return 'Application guarda una vez la misma entidad, sin lecturas';
};
$tests['WB-01'] = static function () use ($input): string {
    $repo = new S15Repository(); $controller = s15controller($repo);
    foreach ([null, ['purpose' => []], array_diff_key($input, ['expected' => true])] as $bad) {
        s15same(['status' => 400, 'body' => ['error' => 'INVALID_INPUT']], $controller->create($bad));
    }
    s15same(0, $repo->writes); s15same(201, $controller->create($input)['status']); s15same(1, $repo->writes);
    return 'guardia de formato: 3 rechazos sin save y camino válido con save';
};
$tests['WB-02'] = static function () use ($input): string {
    $repo = new S15Repository(); $controller = s15controller($repo);
    s15same(['status' => 422, 'body' => ['error' => 'CASE_INCOMPLETE']], $controller->create(array_replace($input, ['steps' => ' '])));
    s15same(0, $repo->writes); $repo->fail = true;
    s15same(['status' => 503, 'body' => ['error' => 'CASE_STORAGE_UNAVAILABLE']], $controller->create($input));
    s15same(1, $repo->writes); s15same([], $repo->saved);
    return 'catch DomainException sin save y catch StorageFailure sin éxito ni detalle privado';
};
$tests['BB-01'] = static function () use ($input): string {
    $result = s15cli(json_encode(array_replace($input, ['purpose' => '  Caso ficticio S15  ']), JSON_THROW_ON_ERROR));
    s15same(201, $result['status']); $data = $result['body']['data'];
    $keys = array_keys($data); sort($keys); s15same(['expected', 'id', 'purpose', 'steps'], $keys);
    foreach ($input as $key => $value) { s15same($value, $data[$key]); }
    s15check(preg_match('/^[a-f0-9]{32}$/D', $data['id']) === 1, 'ID público inválido');
    return 'CLI: 201, cuatro campos, textos esperados e ID hexadecimal de 32 caracteres';
};
$tests['BB-02'] = static function (): string {
    s15same(['status' => 400, 'body' => ['error' => 'INVALID_JSON']], s15cli('{'));
    return 'CLI: 400 INVALID_JSON para JSON truncado';
};
$tests['BB-03'] = static function () use ($input): string {
    s15same(['status' => 422, 'body' => ['error' => 'CASE_INCOMPLETE']],
        s15cli(json_encode(array_replace($input, ['expected' => " \t\n"]), JSON_THROW_ON_ERROR)));
    return 'CLI: 422 CASE_INCOMPLETE para esperado solo con espacios';
};
$tests['BB-04'] = static function () use ($input): string {
    $repo = new S15Repository(); $repo->fail = true; $controller = s15controller($repo);
    s15same(['status' => 503, 'body' => ['error' => 'CASE_STORAGE_UNAVAILABLE']], $controller->create($input));
    $repo->fail = false; $result = $controller->create($input);
    s15same(201, $result['status']);
    foreach ($input as $key => $value) { s15same($value, $result['body']['data'][$key]); }
    return 'interfaz pública Controller: 503 seguro; al restaurar dependencia, nueva llamada 201';
};
$passed = $failed = 0;
foreach ($tests as $id => $run) {
    try { $detail = $run(); ++$passed; echo "PASS $id: $detail\n"; }
    catch (Throwable $error) { ++$failed; echo "FAIL $id: {$error->getMessage()}\n"; }
}
echo "$passed aprobadas, $failed fallidas, 0 bloqueadas\n";
exit($failed === 0 ? 0 : 1);
