<?php

declare(strict_types=1);

namespace App\Modules\Reception\Application;

interface ReceptionRepository
{
    /** Lock order, save sample, update status and enqueue rejection atomically. */
    public function transaction(callable $operation): mixed;

    public function order(string $tenant, string $item): ?array;

    public function save(array $sample): void;

    public function find(string $tenant, string $code): ?array;

    public function byRequest(string $tenant, string $key): ?array;
}
