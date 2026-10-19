<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\AuthenticateCommand;
use App\Modules\Tenants\Application\Commands\MfaVerificationCommand;
use App\Modules\Tenants\Application\Results\AuthResult;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Events\LoginSuccess;
use App\Modules\Tenants\Domain\Events\LoginFailed;
use App\Modules\Tenants\Domain\Events\MfaChallenged;
use App\Modules\Tenants\Domain\Events\MfaSucceeded;
use App\Modules\Tenants\Domain\Events\MfaFailed;
use App\Modules\Tenants\Domain\Events\AccountLocked;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\MfaRequirement;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\DeviceTrustService;
use App\Modules\Tenants\Domain\Services\TotpVerifier;
use App\Modules\Tenants\Domain\Services\WebAuthnVerifier;
use App\Modules\Tenants\Domain\Services\BlocklistService;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\Email;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Infrastructure\External\EventPublisher;
final class AuthenticateUserUseCase
{
    private UserRepositoryInterface $userRepository;
    private TenantRepositoryInterface $tenantRepository;
    private RefreshTokenRepositoryInterface $refreshRepository;
    private SessionRepositoryInterface $sessionRepository;
    private AuditLogRepositoryInterface $auditLog;
    private EventPublisher $eventPublisher;
    private Argon2idHasher $hasher;
    private MfaPolicy $mfaPolicy;
    private TokenFactory $tokenFactory;
    private RefreshTokenRotator $refreshRotator;
    private DeviceTrustService $deviceTrust;
    private TotpVerifier $totpVerifier;
    private WebAuthnVerifier $webAuthnVerifier;
    private BlocklistService $blocklist;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const MAX_MFA_ATTEMPTS = 3;
    private const LOCKOUT_DURATION_1 = 900;
    private const LOCKOUT_DURATION_2 = 3600;
    private const LOCKOUT_DURATION_3 = 86400;
    private const MFA_LOCKOUT_DURATION = 1800;
    public function __construct(
        UserRepositoryInterface $userRepository,
        TenantRepositoryInterface $tenantRepository,
        RefreshTokenRepositoryInterface $refreshRepository,
        SessionRepositoryInterface $sessionRepository,
        AuditLogRepositoryInterface $auditLog,
        EventPublisher $eventPublisher,
        Argon2idHasher $hasher,
        MfaPolicy $mfaPolicy,
        TokenFactory $tokenFactory,
        RefreshTokenRotator $refreshRotator,
        DeviceTrustService $deviceTrust,
        TotpVerifier $totpVerifier,
        WebAuthnVerifier $webAuthnVerifier,
        BlocklistService $blocklist
    ) {
        $this->userRepository = $userRepository;
        $this->tenantRepository = $tenantRepository;
        $this->refreshRepository = $refreshRepository;
        $this->sessionRepository = $sessionRepository;
        $this->auditLog = $auditLog;
        $this->eventPublisher = $eventPublisher;
        $this->hasher = $hasher;
        $this->mfaPolicy = $mfaPolicy;
        $this->tokenFactory = $tokenFactory;
        $this->refreshRotator = $refreshRotator;
        $this->deviceTrust = $deviceTrust;
        $this->totpVerifier = $totpVerifier;
        $this->webAuthnVerifier = $webAuthnVerifier;
        $this->blocklist = $blocklist;
    }
    public function execute(AuthenticateCommand $command): AuthResult
    {
        $user = $this->userRepository->findByEmailAndTenant($command->email(), $command->tenantId());
        if ($user === null) {
            $this->auditLog->log(new LoginFailed(
                $command->email()->value(),
                $command->tenantId(),
                'user_not_found',
                $command->ip() ));
            throw new \DomainException('Credenciales inválidas');}
        if (!$user->isActive()) {
            throw new \DomainException('Cuenta desactivada');}
        if (!$this->hasher->verify($command->rawPassword(), $user->password()->hash())) {
            $this->handleFailedLogin($user, $command);
            throw new \DomainException('Credenciales inválidas');}
        $this->clearLoginFailures($user);
        $tenant = $this->tenantRepository->findById($command->tenantId());
        if ($tenant === null || !$tenant->isActive()) {
            throw new \DomainException('Tenant inactivo');}
        $mfaRequirement = $this->mfaPolicy->evaluate(
            $user->roles(),
            $tenant->policies(),
            $user->mfaRequired());
        if ($mfaRequirement->required()) {
            return $this->handleMfaChallenge($user, $tenant, $command, $mfaRequirement);}
        return $this->generateTokens($user, $tenant, $command->device(), $command->ip());}
    public function verifyMfa(MfaVerificationCommand $command, User $user, Tenant $tenant, DeviceFingerprint $device, string $ip): AuthResult
    { $valid = false;
        if ($command->method() === 'TOTP') {
            $valid = $this->totpVerifier->verify($user->totpSecret(), $command->code());
        } elseif ($command->method() === 'WEBAUTHN') {
            $valid = $this->webAuthnVerifier->verify($user->id()->value(), $command->code());}
        if (!$valid) {
            $this->handleFailedMfa($user);
            $this->auditLog->log(new MfaFailed($user->id(), $tenant->id(), $command->method()));
            throw new \DomainException('Código MFA inválido');}
        $this->clearMfaFailures($user);
        $this->auditLog->log(new MfaSucceeded($user->id(), $tenant->id(), $command->method()));
        return $this->generateTokens($user, $tenant, $device, $ip); }
    private function handleMfaChallenge(User $user, Tenant $tenant, AuthenticateCommand $command, MfaRequirement $requirement): AuthResult
    { $challengeId = \Ramsey\Uuid\Uuid::uuid4()->toString();
        $this->auditLog->log(new MfaChallenged(
            $user->id(),
            $tenant->id(),
            $requirement->type(),
            $challengeId ));
        return new AuthResult(
            accessToken: null,
            refreshToken: '',
            expiresIn: 0,
            user: $user,
            sessionId: null,
            mfaRequired: true,
            challengeId: $challengeId,
            mfaMethod: $requirement->type() );}
    private function generateTokens(User $user, Tenant $tenant, DeviceFingerprint $device, string $ip): AuthResult
    { $sessionId = SessionId::generate();
        $accessToken = $this->tokenFactory->createAccessToken($user, $tenant, $sessionId, $device);
        $refreshResult = $this->refreshRotator->generateAndStore($user, $tenant, $device, $sessionId, $accessToken->jti());
        $this->handleSingleSessionPolicy($user, $tenant, $device, $sessionId);
        $session = \App\Modules\Tenants\Domain\Entities\Session::create($user->id(), $tenant->id(), $device);
        $this->sessionRepository->save($session);
        $user->recordLogin($device);
        $this->userRepository->save($user);
        $this->auditLog->log(new LoginSuccess(
            $user->id(),
            $tenant->id(),
            $user->roleValues(),
            true,
            $device,
            $ip,
            $sessionId->value() ));
        $this->eventPublisher->publish(new LoginSuccess(
            $user->id(),
            $tenant->id(),
            $user->roleValues(),
            true,
            $device,
            $ip,
            $sessionId->value() ));
        return new AuthResult(
            $accessToken,
            $refreshResult->token(),
            $refreshResult->expiresAt() - time(),
            $user,
            $sessionId,
            false  );}
    private function handleSingleSessionPolicy(User $user, Tenant $tenant, DeviceFingerprint $device, SessionId $newSessionId): void
    { if (!$tenant->policy('single_session', false)) { return;}
        $existingSessions = $this->sessionRepository->findByUserId($user->id());
        foreach ($existingSessions as $session) {
            if (!$session->isRevoked()) {
                $this->blocklist->add($session->id()->value(), $session->createdAt()->getTimestamp() + 900);
                $this->refreshRepository->deleteAllByUserId($user->id());
                $this->sessionRepository->delete($session->id());
                $this->auditLog->log(new \App\Modules\Tenants\Domain\Events\SessionRevoked(
                    $user->id(),
                    $tenant->id(),
                    'single_session_policy', 1 ));}}}
    private function handleFailedLogin(User $user, AuthenticateCommand $command): void
    { $attempts = $this->incrementLoginFailures($user);
        $this->auditLog->log(new LoginFailed(
            $command->email()->value(),
            $command->tenantId(),
            'bad_password',
            $command->ip() ));
        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $duration = $this->calculateLockoutDuration($attempts);
            $user->lock();
            $this->userRepository->save($user);
            $this->auditLog->log(new AccountLocked(
                $user->id(),
                $command->tenantId(),
                'login',
                $duration,
                $attempts )); } }
    private function handleFailedMfa(User $user): void
    { $attempts = $this->incrementMfaFailures($user);
        if ($attempts >= self::MAX_MFA_ATTEMPTS) {
            $this->auditLog->log(new AccountLocked(
                $user->id(),
                $user->tenantId(),
                'mfa',
                self::MFA_LOCKOUT_DURATION,
                $attempts)); }}
    private function incrementLoginFailures(User $user): int
    { return 1; }
    private function clearLoginFailures(User $user): void
    {    }
    private function incrementMfaFailures(User $user): int
    { return 1; }
    private function clearMfaFailures(User $user): void
    {    }
    private function calculateLockoutDuration(int $attempts): int
    {
        if ($attempts >= 20) return self::LOCKOUT_DURATION_3;
        if ($attempts >= 10) return self::LOCKOUT_DURATION_2;
        return self::LOCKOUT_DURATION_1;}}
