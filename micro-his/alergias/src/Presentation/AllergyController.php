<?php

declare(strict_types=1);

namespace MicroHis\Alergias\Presentation;

use MicroHis\Alergias\Application\GetAllergyAlert;
use MicroHis\Alergias\Application\PersistenceFailure;
use MicroHis\Alergias\Application\RegisterAllergy;
use MicroHis\Alergias\Domain\DomainRuleViolation;

/** Controller (MVC): traduce HTTP a casos de uso, elige JSON o la View del banner y mapea errores. Sin reglas de negocio. */
final class AllergyController
{
    private const STATUS = ['PATIENT_NOT_FOUND' => 404, 'ALLERGY_ALREADY_ACTIVE' => 409];

    public function __construct(private readonly RegisterAllergy $register, private readonly GetAllergyAlert $alert, private readonly AllergyBannerView $view) {}

    /** @param array<string, mixed> $input @return array{0: int, 1: array<string, mixed>|string} */
    public function handle(string $method, string $path, array $input, ?string $user): array
    {
        if ($user === null) {
            return [401, ['error' => 'UNAUTHENTICATED']];
        }
        if (!preg_match('#^/patients/([\w-]+)/(allergies|allergy-alert)$#', $path, $m)) {
            return [404, ['error' => 'NOT_FOUND']];
        }
        try {
            return match ("{$method} {$m[2]}") {
                'POST allergies' => [201, ['allergy' => get_object_vars($this->register->execute($m[1], $input, $user))]],
                'GET allergy-alert' => [200, ($input['format'] ?? '') === 'html' ? $this->view->render($this->alert->execute($m[1]))
                    : ['alert' => get_object_vars($this->alert->execute($m[1]))]],
                default => [405, ['error' => 'METHOD_NOT_ALLOWED']],
            };
        } catch (DomainRuleViolation $v) {
            return [self::STATUS[$v->rule] ?? 422, ['error' => $v->rule, 'field' => $v->field]];
        } catch (PersistenceFailure) {
            return [500, ['error' => 'PERSISTENCE_FAILURE']];
        }
    }
}
