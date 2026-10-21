<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
final class Argon2idHasher
{
    private int $memoryCost;
    private int $timeCost;
    private int $parallelism;
    public function __construct(
        int $memoryCost = 65536,
        int $timeCost = 4,
        int $parallelism = 3
    ) {
        $this->memoryCost = $memoryCost;
        $this->timeCost = $timeCost;
        $this->parallelism = $parallelism;}
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => $this->memoryCost,
            'time_cost' => $this->timeCost,
            'threads' => $this->parallelism,]);}
    public function verify(string $password, string $hash): bool
    {return password_verify($password, $hash);}
    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
            'memory_cost' => $this->memoryCost,
            'time_cost' => $this->timeCost,
            'threads' => $this->parallelism,]);}}
