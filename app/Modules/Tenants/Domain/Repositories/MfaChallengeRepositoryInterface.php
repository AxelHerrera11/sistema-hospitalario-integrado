<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Repositories;
use App\Modules\Tenants\Domain\Entities\MfaChallenge;
interface MfaChallengeRepositoryInterface
{
    public function save(MfaChallenge $challenge): void;
    public function findById(string $id): ?MfaChallenge;
    public function delete(string $id): void;}
