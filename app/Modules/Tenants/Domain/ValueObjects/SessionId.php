<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;
final class SessionId
{
    private string $value;
    public function __construct(string $sessionId)
    {
        try {
            Uuid::isValid($sessionId) || throw new InvalidArgumentException('SessionId inválido: no es UUID válido');
        } catch (UnsatisfiedDependencyException) {
            throw new InvalidArgumentException('SessionId inválido: UUID library no disponible');}
        $this->value = $sessionId;}
    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());}
    public function value(): string
    {
        return $this->value;}
    public function equals(SessionId $other): bool
    {
        return $this->value === $other->value;}
    public function __toString(): string
    {
        return $this->value;}}
