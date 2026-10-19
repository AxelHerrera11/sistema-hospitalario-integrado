<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use Psr\Http\Message\ServerRequestInterface;
final class MfaVerificationRequest
{
    private string $challengeId;
    private string $code;
    private string $method;
    private function __construct(string $challengeId, string $code, string $method)
    {
        $this->challengeId = $challengeId;
        $this->code = $code;
        $this->method = strtoupper($method);
    }
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = $request->getParsedBody();
        return new self(
            $data['challenge_id'] ?? '',
            $data['code'] ?? '',
            $data['method'] ?? 'TOTP'
        );
    }
    public function challengeId(): string { return $this->challengeId; }
    public function code(): string { return $this->code; }
    public function method(): string { return $this->method; }
}
