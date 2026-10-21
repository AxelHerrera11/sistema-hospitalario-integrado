<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Persistence\InMemory;
use App\Modules\Tenants\Domain\Entities\MfaChallenge;
use App\Modules\Tenants\Domain\Repositories\MfaChallengeRepositoryInterface;
final class InMemoryMfaChallengeRepository implements MfaChallengeRepositoryInterface
{
    /** @var array<string, MfaChallenge> */
    private array $challenges = [];
    public function save(MfaChallenge $challenge): void
    {
        $this->challenges[$challenge->id()] = $challenge;}
    public function findById(string $id): ?MfaChallenge
    {
        return $this->challenges[$id] ?? null;}
    public function delete(string $id): void
    {
        unset($this->challenges[$id]);}}
