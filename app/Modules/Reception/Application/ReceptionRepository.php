<?php

declare(strict_types=1);

namespace App\Modules\Reception\Application;

interface ReceptionRepository
{
    /** Save specimen and its outbox event in one transaction. */
    public function transaction(callable $operation): mixed;

    public function save(array $sample): void;

    public function find(string $tenant, string $code): ?array;

    public function byRequest(string $tenant, string $key): ?array;
}
