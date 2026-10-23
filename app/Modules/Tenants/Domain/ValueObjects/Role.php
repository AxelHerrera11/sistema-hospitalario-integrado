<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
/**
 * Rol de una membresía hospitalaria. Usa los mismos códigos que config/rbac.php
 * (hospital_memberships.role_code); distingue mayúsculas y no los convierte.
 */
final class Role
{
    public const SUPERADMIN = 'superadmin';
    public const ADMIN_HOSPITAL = 'admin_hospital';
    public const MEDICO = 'medico';
    public const ENFERMERIA = 'enfermeria';
    public const RECEPCION = 'recepcion';
    public const TECNICO_LABORATORIO = 'tecnico_laboratorio';
    public const BIOQUIMICO = 'bioquimico';
    public const JEFATURA_MEDICA = 'jefatura_medica';
    private const VALID_ROLES = [
        self::SUPERADMIN,
        self::ADMIN_HOSPITAL,
        self::MEDICO,
        self::ENFERMERIA,
        self::RECEPCION,
        self::TECNICO_LABORATORIO,
        self::BIOQUIMICO,
        self::JEFATURA_MEDICA,];
    private const MFA_REQUIRED_ROLES = [
        self::SUPERADMIN,
        self::ADMIN_HOSPITAL,];
    private string $value;
    public function __construct(string $role)
    {
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException('Rol inválido: ' . $role . '. Válidos: ' . implode(', ', self::VALID_ROLES));}
        $this->value = $role;}
    public static function fromString(string $role): self
    {
        return new self($role);}
    public function value(): string
    {
        return $this->value;}
    public function requiresMfa(): bool
    {
        return in_array($this->value, self::MFA_REQUIRED_ROLES, true);}
    public function equals(Role $other): bool
    {
        return $this->value === $other->value;}
    public function __toString(): string
    {
        return $this->value;}
    public static function all(): array
    {
        return self::VALID_ROLES;}
    public static function mfaRequiredRoles(): array
    {
        return self::MFA_REQUIRED_ROLES;}}
