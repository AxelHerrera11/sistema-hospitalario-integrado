<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Presentation;

use MicroHis\CatalogoLab\Application\FindActiveLabTests;
use MicroHis\CatalogoLab\Application\PersistenceFailure;
use MicroHis\CatalogoLab\Application\RegisterLabTest;
use MicroHis\CatalogoLab\Domain\DomainRuleViolation;

/** Controller (MVC): traduce HTTP a casos de uso, elige JSON o la View HTML y mapea errores. Sin reglas de negocio. */
final class LabTestController
{
    public function __construct(private readonly RegisterLabTest $register, private readonly FindActiveLabTests $find, private readonly LabTestListView $view) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>|string} */
    public function handle(string $method, string $path, array $input, ?string $user): array
    {
        if ($user === null) {
            return [401, ['error' => 'UNAUTHENTICATED']];
        }
        try {
            return match ("{$method} {$path}") {
                'POST /lab-tests' => [201, ['lab_test' => get_object_vars($this->register->execute($input, $user))]],
                'GET /lab-tests' => $this->list((string) ($input['date'] ?? date('Y-m-d')), (string) ($input['search'] ?? ''), ($input['format'] ?? '') === 'html'),
                default => [404, ['error' => 'NOT_FOUND']],
            };
        } catch (DomainRuleViolation $v) {
            return [$v->rule === 'LAB_TEST_VALIDITY_OVERLAP' ? 409 : 422, ['error' => $v->rule, 'field' => $v->field]];
        } catch (PersistenceFailure) {
            return [500, ['error' => 'PERSISTENCE_FAILURE']];
        }
    }

    /** @return array{0: int, 1: array<string, mixed>|string} */
    private function list(string $date, string $search, bool $html): array
    {
        $tests = $this->find->execute($date, $search);
        return [200, $html ? $this->view->render($tests, $date) : ['lab_tests' => $tests]];
    }
}
