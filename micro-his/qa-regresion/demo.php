<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

use MicroHis\QaRegresion\Application\DesignCase;
use MicroHis\QaRegresion\Persistence\PdoCaseRepository;
use MicroHis\QaRegresion\Presentation\CaseController;

$pdo = new PDO('sqlite::memory:');
$pdo->exec(file_get_contents(__DIR__.'/database/schema.sqlite.sql'));
try {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $result = (new CaseController(new DesignCase(new PdoCaseRepository($pdo))))->create($input);
} catch (JsonException) {
    $result = ['status' => 400, 'body' => ['error' => 'INVALID_JSON']];
}
echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE).PHP_EOL;
