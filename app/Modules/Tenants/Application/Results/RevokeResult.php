<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\Results;
final class RevokeResult
{
    private int $revokedSessions;
    public function __construct(int $revokedSessions)
    { $this->revokedSessions = $revokedSessions;}
    public function revokedSessions(): int { return $this->revokedSessions; }}
