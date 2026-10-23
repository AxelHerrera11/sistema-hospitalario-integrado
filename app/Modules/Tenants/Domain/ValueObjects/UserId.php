<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
final class UserId
{
    private string $value;
    public function __construct(string $userId)
    {
        try {
            Uuid::isValid($userId) || throw new InvalidArgumentException('UserId inválido: no es UUID válido');
        } catch (UnsatisfiedDependencyException) {
            throw new InvalidArgumentException('UserId inválido: UUID library no disponible');
        }
        $this->value = $userId;
    }
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());}
    public static function fromString(string $userId): self
    {
        return new self($userId);}
    public function value(): string
    {
        return $this->value;}
    public function equals(UserId $other): bool
    {
        return $this->value === $other->value;}
    public function __toString(): string
    {
        return $this->value;}}
