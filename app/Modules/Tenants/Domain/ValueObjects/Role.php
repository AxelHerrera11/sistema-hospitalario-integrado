<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
final class Role
{
    public const TENANT_ADMIN = 'TENANT_ADMIN';
    public const MEDICO = 'MEDICO';
    public const ENFERMERO = 'ENFERMERO';
    public const PACIENTE = 'PACIENTE';
    public const AUDITOR = 'AUDITOR';
    public const PLATFORM_ADMIN = 'PLATFORM_ADMIN';
    private static array $validRoles = [
        self::TENANT_ADMIN,
        self::MEDICO,
        self::ENFERMERO,
        self::PACIENTE,
        self::AUDITOR,
        self::PLATFORM_ADMIN,];
    private static array $mfaRequiredRoles = [
        self::TENANT_ADMIN,
        self::AUDITOR,
        self::PLATFORM_ADMIN,];
    private string $value;
    public function __construct(string $role)
    {
        $role = strtoupper(trim($role));
        if (!in_array($role, self::$validRoles, true)) {
            throw new InvalidArgumentException('Rol inválido: ' . $role . '. Válidos: ' . implode(', ', self::$validRoles));}
        $this->value = $role;}
    public static function fromString(string $role): self
    {
        return new self($role);}
    public function value(): string
    {
        return $this->value;}
    public function requiresMfa(): bool
    {
        return in_array($this->value, self::$mfaRequiredRoles, true);}
    public function equals(Role $other): bool
    {
        return $this->value === $other->value;}
    public function __toString(): string
    {
        return $this->value;}
    public static function all(): array
    {
        return self::$validRoles;}
    public static function mfaRequiredRoles(): array
    {
        return self::$mfaRequiredRoles;}}
