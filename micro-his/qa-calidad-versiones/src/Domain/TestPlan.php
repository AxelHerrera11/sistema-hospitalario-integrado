<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Plan de pruebas de una versión: qué suites se ejecutan sobre qué commit.
 * Reglas: versión SemVer, commit SHA-1 completo, nombres únicos y al menos una
 * suite obligatoria unitaria y una E2E (sin ellas no hay gate que evaluar).
 */
final readonly class TestPlan
{
    /** @param list<PlannedSuite> $suites */
    public function __construct(
        public string $releaseVersion,
        public string $commitSha,
        public array $suites,
    ) {
        if (preg_match('/^\d+\.\d+\.\d+(-[0-9A-Za-z.-]+)?$/', $releaseVersion) !== 1) {
            throw new InvalidQaInput('version_invalida', 'La versión debe seguir SemVer, por ejemplo 1.4.0 o 1.4.0-rc.1.');
        }
        if (preg_match('/^[0-9a-f]{40}$/', $commitSha) !== 1) {
            throw new InvalidQaInput('commit_invalido', 'El commit debe ser un SHA-1 completo de 40 caracteres hexadecimales.');
        }
        $names = array_map(static fn (PlannedSuite $s): string => $s->name, $suites);
        if (count($names) !== count(array_unique($names))) {
            throw new InvalidQaInput('suite_duplicada', 'Cada suite del plan debe tener un nombre único.');
        }
        foreach ([SuiteKind::Unit, SuiteKind::E2e] as $kind) {
            if ($this->requiredOf($kind) === []) {
                throw new InvalidQaInput('plan_incompleto', "El plan necesita al menos una suite obligatoria de tipo {$kind->value}.");
            }
        }
    }

    public function suite(string $name): ?PlannedSuite
    {
        foreach ($this->suites as $suite) {
            if ($suite->name === $name) {
                return $suite;
            }
        }

        return null;
    }

    /** @return list<PlannedSuite> */
    public function requiredOf(?SuiteKind $kind = null): array
    {
        return array_values(array_filter(
            $this->suites,
            static fn (PlannedSuite $s): bool => $s->required && ($kind === null || $s->kind === $kind),
        ));
    }
}
