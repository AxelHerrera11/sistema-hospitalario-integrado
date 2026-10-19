<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Presentation;

use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\PersistenceFailure;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Domain\DomainRuleViolation;

/** Traduce HTTP a casos de uso y errores a códigos de estado. No contiene reglas de negocio. */
final class LabTestHttpHandler
{
    public function __construct(private readonly RegisterLabTest $register, private readonly FindActiveLabTests $find) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>} */
    public function handle(string $method, string $path, array $input, ?string $user): array
    {
        if ($user === null) {
            return [401, ['error' => 'UNAUTHENTICATED']];
        }
        try {
            return match ("{$method} {$path}") {
                'POST /lab-tests' => [201, ['lab_test' => get_object_vars($this->register->execute($input, $user))]],
                'GET /lab-tests' => [200, ['lab_tests' => $this->find->execute((string) ($input['date'] ?? date('Y-m-d')), (string) ($input['search'] ?? ''))]],
                default => [404, ['error' => 'NOT_FOUND']],
            };
        } catch (DomainRuleViolation $v) {
            return [$v->rule === 'LAB_TEST_VALIDITY_OVERLAP' ? 409 : 422, ['error' => $v->rule, 'field' => $v->field]];
        } catch (PersistenceFailure) {
            return [500, ['error' => 'PERSISTENCE_FAILURE']];
        }
    }
}
