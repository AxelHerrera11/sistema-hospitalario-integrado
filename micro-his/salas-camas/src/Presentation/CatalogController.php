<?php
declare(strict_types=1);

namespace MicroHis\SalasCamas\Presentation;

use MicroHis\SalasCamas\Application\{CatalogOperationFailure, CatalogService, PersistenceFailure};

final class CatalogController
{
    public function __construct(private readonly CatalogService $catalog) {}

    public function createWard(array $data): array
    {
        return $this->respond(fn() => $this->catalog->createWard($data));
    }

    public function createBed(string $wardCode, array $data): array
    {
        return $this->respond(fn() => $this->catalog->createBed($wardCode, $data));
    }

    private function respond(callable $operation): array
    {
        try {
            return ['status' => 201, 'body' => ['item' => $operation()]];
        } catch (CatalogOperationFailure $e) {
            return ['status' => $e->errorCode === 'WARD_NOT_FOUND' ? 404 : 422,
                'body' => ['error' => ['code' => $e->errorCode, 'message' => $e->getMessage()]]];
        } catch (PersistenceFailure) {
            return ['status' => 500, 'body' => ['error' => ['code' => 'PERSISTENCE_FAILURE', 'message' => 'No se pudo completar la operación.']]];
        }
    }
}
