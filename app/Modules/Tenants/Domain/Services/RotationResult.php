<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Domain\Services;
use App\Modules\Tenants\Domain\Entities\RefreshToken;
final class RotationResult
{
    private bool $success;
    private ?RefreshToken $previous;
    private ?RefreshTokenResult $next;
    private ?string $error;
    private bool $reuseDetected;
    private function __construct(
        bool $success,
        ?RefreshToken $previous = null,
        ?RefreshTokenResult $next = null,
        ?string $error = null,
        bool $reuseDetected = false
    ) {
        $this->success = $success;
        $this->previous = $previous;
        $this->next = $next;
        $this->error = $error;
        $this->reuseDetected = $reuseDetected;}
    public static function success(RefreshToken $previous, RefreshTokenResult $next): self
    {
        return new self(true, $previous, $next);}
    public static function failure(string $error): self
    {
        return new self(false, error: $error);}
    public static function reuseDetected(RefreshToken $reused): self
    {
        return new self(false, $reused, error: 'Reutilización de refresh token detectada', reuseDetected: true);}
    public function isSuccess(): bool { return $this->success; }
    /** Token presentado: el rotado en un éxito o el reutilizado en un ataque. */
    public function previous(): ?RefreshToken { return $this->previous; }
    public function next(): ?RefreshTokenResult { return $this->next; }
    public function error(): ?string { return $this->error; }
    public function isReuseDetected(): bool { return $this->reuseDetected; }}
