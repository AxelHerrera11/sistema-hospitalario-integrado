<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
final class ValidationResult
{
    private bool $valid;
    private array $errors;
    public function __construct(bool $valid, array $errors = [])
    {
        $this->valid = $valid;
        $this->errors = $errors;}
    public function isValid(): bool { return $this->valid; }
    public function errors(): array { return $this->errors; }
    public function firstError(): ?string { return $this->errors[0] ?? null; }}
