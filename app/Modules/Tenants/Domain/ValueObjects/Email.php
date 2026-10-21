<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
final class Email
{
    private string $value;
    public function __construct(string $email)
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email inválido: ' . $email);}
        if (strlen($email) > 255) {
            throw new InvalidArgumentException('Email excede 255 caracteres');}
        $this->value = strtolower($email);}
    public function value(): string
    {
        return $this->value;}
    public function equals(Email $other): bool
    {
        return $this->value === $other->value;}
    public function __toString(): string
    {
        return $this->value;}}
