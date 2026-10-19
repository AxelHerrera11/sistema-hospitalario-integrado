<?php

declare(strict_types=1);

namespace MicroHis\Admision\Presentation;

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Application\PersistenceFailure;
use MicroHis\Admision\Domain\DomainRuleViolation;

/** Controller (MVC): traduce HTTP a casos de uso, elige JSON o la View HTML y mapea errores. Sin reglas de negocio. */
final class AdmissionController
{
    private const STATUS = ['PATIENT_NOT_FOUND' => 404, 'BED_NOT_FOUND' => 404, 'PATIENT_ALREADY_ADMITTED' => 409, 'BED_NOT_AVAILABLE' => 409];

    public function __construct(private readonly AdmitPatient $admit, private readonly FindAvailableBeds $beds, private readonly AvailableBedsView $view) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>|string} */
    public function handle(string $method, string $path, array $input, ?string $user): array
    {
        if ($user === null) {
            return [401, ['error' => 'UNAUTHENTICATED']];
        }
        try {
            return match ("{$method} {$path}") {
                'POST /admissions' => [201, ['admission' => get_object_vars($this->admit->execute($input, $user))]],
                'GET /beds/available' => $this->available((string) ($input['type'] ?? ''), ($input['format'] ?? '') === 'html'),
                default => [404, ['error' => 'NOT_FOUND']],
            };
        } catch (DomainRuleViolation $v) {
            return [self::STATUS[$v->rule] ?? 422, ['error' => $v->rule, 'field' => $v->field]];
        } catch (PersistenceFailure) {
            return [500, ['error' => 'PERSISTENCE_FAILURE']];
        }
    }

    /** @return array{0: int, 1: array<string, mixed>|string} */
    private function available(string $type, bool $html): array
    {
        $beds = $this->beds->execute($type);
        return [200, $html ? $this->view->render($beds, $type) : ['beds' => $beds]];
    }
}
