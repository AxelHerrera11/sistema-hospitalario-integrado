<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
final class TenantId
{
    private string $value;
    public function __construct(string $tenantId)
    {
        try {
            Uuid::isValid($tenantId) || throw new InvalidArgumentException('TenantId inválido: no es UUID válido');
        } catch (UnsatisfiedDependencyException) {
            throw new InvalidArgumentException('TenantId inválido: UUID library no disponible');}
        $this->value = $tenantId;
    }
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());}
    public function value(): string
    {
        return $this->value;}
    public function equals(TenantId $other): bool
    {
        return $this->value === $other->value;}
    public function __toString(): string
    {
        return $this->value;}}
