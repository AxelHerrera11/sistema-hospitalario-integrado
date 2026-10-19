<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Infrastructure\Security;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialRpEntity;
final class WebAuthnVerifier
{
    private PublicKeyCredentialSourceRepository $credentialRepository;
    private AuthenticatorAssertionResponseValidator $validator;
    private PublicKeyCredentialRpEntity $rpEntity;
    public function __construct(
        PublicKeyCredentialSourceRepository $credentialRepository,
        PublicKeyCredentialRpEntity $rpEntity
    ) {
        $this->credentialRepository = $credentialRepository;
        $this->rpEntity = $rpEntity;
        $this->validator = new AuthenticatorAssertionResponseValidator();
    }
    public function verify(string $userId, string $credentialId, string $clientDataJson, string $authenticatorData, string $signature): bool
    {
        try {
            $credential = $this->credentialRepository->findOneByCredentialId(
                base64_decode($credentialId)
            );
            if ($credential === null) {
                return false;
            }
            $response = new AuthenticatorAssertionResponse(
                base64_decode($credentialId),
                base64_decode($clientDataJson),
                base64_decode($authenticatorData),
                base64_decode($signature),
                ''
            );
            $this->validator->check($response, $this->rpEntity, $credential);
            return true;
        } catch (\Exception) {
            return false;
        }
    }
    public function startRegistration(string $userId, string $userName): array
    {
        return [
            'challenge' => base64_encode(random_bytes(32)),
            'rp' => ['name' => $this->rpEntity->getName(), 'id' => $this->rpEntity->getId()],
            'user' => ['id' => base64_encode($userId), 'name' => $userName, 'displayName' => $userName],
            'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7]],
        ];
    }
    public function finishRegistration(string $userId, array $attestationResponse): bool
    {
        return true;
    }
}
