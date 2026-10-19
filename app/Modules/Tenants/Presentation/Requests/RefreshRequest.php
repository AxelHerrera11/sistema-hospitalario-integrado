<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use Psr\Http\Message\ServerRequestInterface;
final class RefreshRequest
{
    private string $refreshToken;
    private function __construct(string $refreshToken)
    {
        $this->refreshToken = $refreshToken;
    }
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = $request->getParsedBody();
        return new self($data['refresh_token'] ?? '');
    }
    public function refreshToken(): string { return $this->refreshToken; }
}
