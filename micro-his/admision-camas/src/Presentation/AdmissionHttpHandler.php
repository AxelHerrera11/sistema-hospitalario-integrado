<?php

declare(strict_types=1);

namespace MicroHis\Admision\Presentation;

use MicroHis\Admision\Application\AdmitPatient;
use MicroHis\Admision\Application\FindAvailableBeds;
use MicroHis\Admision\Application\PersistenceFailure;
use MicroHis\Admision\Domain\DomainRuleViolation;

/** Traduce HTTP a casos de uso y errores a códigos de estado. No contiene reglas de negocio. */
final class AdmissionHttpHandler
{
    private const STATUS = ['PATIENT_NOT_FOUND' => 404, 'BED_NOT_FOUND' => 404, 'PATIENT_ALREADY_ADMITTED' => 409, 'BED_NOT_AVAILABLE' => 409];

    public function __construct(private readonly AdmitPatient $admit, private readonly FindAvailableBeds $beds) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>} */
    public function handle(string $method, string $path, array $input, ?string $user): array
    {
        if ($user === null) {
            return [401, ['error' => 'UNAUTHENTICATED']];
        }
        try {
            return match ("{$method} {$path}") {
                'POST /admissions' => [201, ['admission' => get_object_vars($this->admit->execute($input, $user))]],
                'GET /beds/available' => [200, ['beds' => $this->beds->execute((string) ($input['type'] ?? ''))]],
                default => [404, ['error' => 'NOT_FOUND']],
            };
        } catch (DomainRuleViolation $v) {
            return [self::STATUS[$v->rule] ?? 422, ['error' => $v->rule, 'field' => $v->field]];
        } catch (PersistenceFailure) {
            return [500, ['error' => 'PERSISTENCE_FAILURE']];
        }
    }
}
