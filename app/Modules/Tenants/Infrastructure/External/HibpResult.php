<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\External;
final class HibpResult
{
    private bool $compromised;
    private int $count;
    public function __construct(bool $compromised, int $count)
    {
        $this->compromised = $compromised;
        $this->count = $count;}
    public function compromised(): bool { return $this->compromised; }
    public function count(): int { return $this->count; }
}
