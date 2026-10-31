<?php

declare(strict_types=1);

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Persistence\InMemoryAllergyRepository;
use MicroHis\Alergias\Presentation\AllergyBannerView;
use MicroHis\Alergias\Presentation\AllergyController;
use MicroHis\Alergias\Presentation\ApiKernel;

require __DIR__ . '/../bootstrap.php';

/** Caja negra: solo el contrato HTTP de openapi.yaml, con particiones de equivalencia y valores límite. */
function check(string $name, bool $ok): void
{
    echo ($ok ? 'OK' : 'FALLO') . ": {$name}" . PHP_EOL;
    $ok || exit(1);
}
function server(?InMemoryAllergyRepository &$repo = null): ApiKernel
{
    $repo ??= new InMemoryAllergyRepository(['P-1']);
    return new ApiKernel(new AllergyController(new RegisterAllergy($repo, fn () => uniqid('A-')), new GetAllergyAlert($repo), new AllergyBannerView()), 'HOSP-DEMO', fn () => true);
}
$h = ['x-tenant-id' => 'HOSP-DEMO', 'x-user-ref' => 'medico1'];
$post = function (ApiKernel $k, array $change, string $patient = 'P-1') use ($h): array {
    [$status, , $out] = $k->handle('POST', "/api/v1/patients/{$patient}/allergies", $h, json_encode(array_merge(['allergen_name' => 'Penicilina', 'allergy_type' => 'allergy', 'severity' => 'mild'], $change)));
    return [$status, json_decode($out, true)['code'] ?? null];
};
$level = fn (ApiKernel $k) => json_decode($k->handle('GET', '/api/v1/patients/P-1/allergy-alert', $h, '')[2], true)['alert']['level'];
// Partición: severity (5 valores del enum) → nivel del banner.
foreach (['mild' => 'warning', 'moderate' => 'warning', 'unknown' => 'warning', 'severe' => 'critical', 'life_threatening' => 'critical'] as $severity => $expected) {
    $k = server();
    $post($k, ['severity' => $severity]);
    check("severity {$severity} → banner {$expected}", $level($k) === $expected);
}
check('severity fuera del enum → 422 SEVERITY_INVALID', $post(server(), ['severity' => 'grave']) === [422, 'SEVERITY_INVALID']);
// Partición: allergy_type (3 válidos y 1 inválido).
check('allergy_type intolerance (válido) → 201', $post(server(), ['allergy_type' => 'intolerance'])[0] === 201);
check('allergy_type fuera del enum → 422 ALLERGY_TYPE_INVALID', $post(server(), ['allergy_type' => 'reaccion']) === [422, 'ALLERGY_TYPE_INVALID']);
// Valores límite del alérgeno: un carácter frente a solo espacios.
check('allergen_name de 1 carácter → 201', $post(server(), ['allergen_name' => 'X'])[0] === 201);
check('allergen_name solo espacios y sin código → 422 ALLERGEN_REQUIRED', $post(server(), ['allergen_name' => '   ']) === [422, 'ALLERGEN_REQUIRED']);
// Partición: equivalente activa (mismo paciente, tipo y alérgeno sin distinguir mayúsculas) frente a distinta.
$k = server($repo);
$post($k, []);
check('mismo alérgeno en mayúsculas → 409 ALLERGY_ALREADY_ACTIVE', $post($k, ['allergen_name' => 'PENICILINA']) === [409, 'ALLERGY_ALREADY_ACTIVE']);
check('mismo alérgeno con otro tipo (intolerancia) → 201', $post($k, ['allergy_type' => 'intolerance'])[0] === 201);
check('paciente inexistente → 404 PATIENT_NOT_FOUND', $post(server(), [], 'P-9') === [404, 'PATIENT_NOT_FOUND']);
// Partición: estado del almacenamiento al consultar.
$k = server($repo);
$repo->failReads = true;
check('lectura sin base → 200 con level unavailable (nunca none)', $level($k) === 'unavailable');
echo 'Todas las pruebas de caja negra pasaron.' . PHP_EOL;
