<?php

declare(strict_types=1);

namespace App\Modules\Reception\Infrastructure;

use Illuminate\Support\Facades\DB;
use App\Modules\Reception\Application\ReceptionRepository;
use App\Modules\Reception\Persistence\PdoReceptionRepository;

/** Resuelve PDO al operar, después de que tenant seleccione la base hospital. */
final class HospitalReceptionRepository implements ReceptionRepository
{
    private function repository(): PdoReceptionRepository
    {
        return new PdoReceptionRepository(DB::connection('hospital')->getPdo());
    }

    public function transaction(callable $operation): mixed
    {
        return $this->repository()->transaction($operation);
    }

    public function save(array $sample): void
    {
        $this->repository()->save($sample);
    }

    public function find(string $tenant, string $code): ?array
    {
        return $this->repository()->find($tenant, $code);
    }

    public function byRequest(string $tenant, string $key): ?array
    {
        return $this->repository()->byRequest($tenant, $key);
    }
}
