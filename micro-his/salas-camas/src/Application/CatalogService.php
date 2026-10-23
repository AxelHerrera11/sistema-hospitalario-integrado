<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Application;

use MicroHis\SalasCamas\Domain\{Bed, DomainRuleViolation, Ward};

final class CatalogService
{
    public function __construct(
        private readonly WardRepository $wards,
        private readonly BedRepository $beds
    ) {}

    public function listWards(): array
    {
        return $this->wards->listAll();
    }

    public function listBedsForWard(string $wardId): array
    {
        if ($this->wards->findById($wardId) === null) {
            throw new CatalogOperationFailure('WARD_NOT_FOUND', 'La ward solicitada no existe.');
        }

        return $this->beds->listByWardId($wardId);
    }

    public function createWard(array $data): Ward
    {
        try {
            return $this->wards->insertWard(Ward::create($data));
        } catch (DomainRuleViolation $e) {
            throw new CatalogOperationFailure($e->errorCode, $e->getMessage(), $e);
        }
    }

    public function createBed(string $wardCode, array $data): Bed
    {
        try {
            $ward = $this->wards->findByCode(trim($wardCode))
                ?? throw new CatalogOperationFailure('WARD_NOT_FOUND', 'Sala no encontrada.');
            return $this->beds->insertBed(Bed::create((string)$ward->id, $data));
        } catch (DomainRuleViolation $e) {
            throw new CatalogOperationFailure($e->errorCode, $e->getMessage(), $e);
        }
    }
}
