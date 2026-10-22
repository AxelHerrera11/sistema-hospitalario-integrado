<?php

declare(strict_types=1);

namespace MicroHis\CatalogoLab\Application;

use Closure;
use MicroHis\CatalogoLab\Domain\DomainRuleViolation;
use MicroHis\CatalogoLab\Domain\LabTest;

/** UC-LAB-01 Registrar prueba: valida, impide vigencias solapadas (UC-LAB-04) y guarda con auditoría. */
final class RegisterLabTest
{
    /** @param Closure(): string $today @param Closure(): string $newId */
    public function __construct(private readonly LabTestRepository $store, private readonly Closure $today, private readonly Closure $newId) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, string $actor): LabTest
    {
        $code = strtoupper(trim(is_string($data['code'] ?? null) ? $data['code'] : ''));
        $test = LabTest::register(($this->newId)(), $data, $this->store->latestVersionNo($code) + 1, ($this->today)());
        if ($this->store->hasActiveOverlap($test->code, $test->validFrom)) {
            throw new DomainRuleViolation('LAB_TEST_VALIDITY_OVERLAP', 'valid_from');
        }
        try {
            $this->store->save($test, $actor);
        } catch (PersistenceFailure $failure) {
            // Dos altas simultáneas del mismo código: la base (UNIQUE código-versión) decidió.
            throw $failure->duplicate ? new DomainRuleViolation('LAB_TEST_VALIDITY_OVERLAP', 'valid_from') : $failure;
        }

        return $test;
    }
}
