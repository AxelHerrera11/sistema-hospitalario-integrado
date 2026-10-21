<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use Psr\Http\Message\ServerRequestInterface;
final class RefreshRequest
{
    private function __construct(private string $refreshToken, private string $ip) {}
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = (array) $request->getParsedBody();
        $token = $data['refresh_token'] ?? '';
        if (!is_string($token) || $token === '') {
            throw new \InvalidArgumentException('refresh_token es obligatorio');}
        return new self($token, $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');}
    public function refreshToken(): string { return $this->refreshToken; }
    public function ip(): string { return $this->ip; }}
