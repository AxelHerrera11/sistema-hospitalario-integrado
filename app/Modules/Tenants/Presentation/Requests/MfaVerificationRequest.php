<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Requests;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
final class MfaVerificationRequest
{
    private function __construct(private string $challengeId, private string $code) {}
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $data = (array) $request->getParsedBody();
        $challengeId = (string) ($data['challenge_id'] ?? '');
        if (!Uuid::isValid($challengeId)) {
            throw new \InvalidArgumentException('challenge_id inválido');}
        return new self($challengeId, (string) ($data['code'] ?? ''));}
    public function challengeId(): string { return $this->challengeId; }
    public function code(): string { return $this->code; }}
