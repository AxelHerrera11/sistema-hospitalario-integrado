<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\ValueObjects;
use InvalidArgumentException;
final class Password
{
    private string $hash;
    private function __construct(string $hash)
    {$this->hash = $hash;}
    public static function fromHash(string $hash): self
    {
        if (empty($hash)) {
            throw new InvalidArgumentException('Hash de contraseña no puede estar vacío');}
        return new self($hash);}
    public static function fromRaw(string $raw, \App\Modules\Tenants\Domain\Services\Argon2idHasher $hasher): self
    {
        if (empty($raw)) {
            throw new InvalidArgumentException('Contraseña no puede estar vacía');}
        return new self($hasher->hash($raw));}
    public function verify(string $raw, \App\Modules\Tenants\Domain\Services\Argon2idHasher $hasher): bool
    {
        return $hasher->verify($raw, $this->hash);}
    public function needsRehash(\App\Modules\Tenants\Domain\Services\Argon2idHasher $hasher): bool
    {
        return $hasher->needsRehash($this->hash);}
    public function hash(): string
    {
        return $this->hash;}}
