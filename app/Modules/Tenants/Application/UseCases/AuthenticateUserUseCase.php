<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Application\UseCases;
use App\Modules\Tenants\Application\Commands\AuthenticateCommand;
use App\Modules\Tenants\Application\Commands\MfaVerificationCommand;
use App\Modules\Tenants\Application\Results\AuthResult;
use App\Modules\Tenants\Domain\Entities\MfaChallenge;
use App\Modules\Tenants\Domain\Entities\Session;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Events\DomainEvent;
use App\Modules\Tenants\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\MfaChallengeRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\RefreshTokenRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\SessionRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\Services\Argon2idHasher;
use App\Modules\Tenants\Domain\Services\MfaPolicy;
use App\Modules\Tenants\Domain\Services\MfaRequirement;
use App\Modules\Tenants\Domain\Services\OneTimePasswordVerifier;
use App\Modules\Tenants\Domain\Services\RefreshTokenRotator;
use App\Modules\Tenants\Domain\Services\TokenFactory;
use App\Modules\Tenants\Domain\ValueObjects\DeviceFingerprint;
use App\Modules\Tenants\Domain\ValueObjects\SessionId;
use DomainException;
/** Login con contraseña, bloqueo progresivo por hospital y segundo factor TOTP de un solo uso. */
final class AuthenticateUserUseCase
{
    private const MAX_MFA_ATTEMPTS = 3;
    private const MFA_LOCKOUT_DURATION = 1800;
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TenantRepositoryInterface $tenantRepository,
        private SessionRepositoryInterface $sessionRepository,
        private RefreshTokenRepositoryInterface $refreshRepository,
        private MfaChallengeRepositoryInterface $challengeRepository,
        private AuditLogRepositoryInterface $auditLog,
        private Argon2idHasher $hasher,
        private MfaPolicy $mfaPolicy,
        private OneTimePasswordVerifier $otpVerifier,
        private TokenFactory $tokenFactory,
        private RefreshTokenRotator $refreshRotator
    ) {}
    public function execute(AuthenticateCommand $command): AuthResult
    {
        $tenantId = $command->tenantId();
        $tenant = $this->tenantRepository->findById($tenantId);
        $user = $tenant === null ? null : $this->userRepository->findByEmailAndTenant($command->email(), $tenantId);
        if ($tenant === null || $user === null) {
            $this->fail($command, 'user_not_found');
            throw new DomainException('Credenciales inválidas', 401);}
        if (!$tenant->isActive()) {
            $this->auditLog->log(new DomainEvent(DomainEvent::TENANT_INACTIVE, ['tenant_id' => $tenantId->value(), 'user_id' => $user->id()->value(), 'ip' => $command->ip()]));
            throw new DomainException('Hospital inactivo', 403);}
        if (!$user->isActive()) {
            $this->fail($command, 'user_inactive');
            throw new DomainException('Cuenta desactivada', 403);}
        if ($user->isLocked()) {
            $this->fail($command, 'account_locked');
            throw new DomainException('Cuenta bloqueada temporalmente', 423);}
        if (!$this->hasher->verify($command->rawPassword(), $user->password()->hash())) {
            $this->handleFailedLogin($user, $tenant, $command);
            throw new DomainException('Credenciales inválidas', 401);}
        $user->clearFailedLogins();
        $this->userRepository->save($user);
        $mfaRequirement = $this->mfaPolicy->evaluate($user->roles(), $tenant->policies(), $user->mfaRequired());
        if ($mfaRequirement->required()) {
            return $this->openMfaChallenge($user, $command, $mfaRequirement);}
        return $this->startSession($user, $tenant, $command->device(), $command->ip(), false);}
    public function verifyMfa(MfaVerificationCommand $command): AuthResult
    {
        $challenge = $this->challengeRepository->findById($command->challengeId());
        if ($challenge === null || $challenge->isExpired()) {
            if ($challenge !== null) {
                $this->challengeRepository->delete($challenge->id());}
            throw new DomainException('Desafío MFA inválido o vencido', 401);}
        $user = $this->userRepository->findByIdAndTenant($challenge->userId(), $challenge->tenantId());
        $tenant = $this->tenantRepository->findById($challenge->tenantId());
        if ($user === null || $tenant === null || !$user->isActive() || !$tenant->isActive() || $user->isLocked()) {
            $this->challengeRepository->delete($challenge->id());
            throw new DomainException('Desafío MFA inválido o vencido', 401);}
        $context = ['tenant_id' => $tenant->id()->value(), 'user_id' => $user->id()->value(), 'method' => MfaRequirement::TOTP];
        if (!$this->otpVerifier->verify((string) $user->totpSecret(), $command->code())) {
            $this->auditLog->log(new DomainEvent(DomainEvent::MFA_FAILED, $context));
            $attempts = $challenge->registerFailure();
            if ($attempts >= self::MAX_MFA_ATTEMPTS) {
                $this->challengeRepository->delete($challenge->id());
                $user->lockFor(self::MFA_LOCKOUT_DURATION);
                $this->userRepository->save($user);
                $this->auditLog->log(new DomainEvent(DomainEvent::ACCOUNT_LOCKED, $context + ['lock_type' => 'mfa', 'duration_seconds' => self::MFA_LOCKOUT_DURATION, 'failed_attempts' => $attempts]));
            } else {
                $this->challengeRepository->save($challenge);}
            throw new DomainException('Código MFA inválido', 401);}
        // Consumo único: el mismo challenge_id no puede volver a usarse.
        $this->challengeRepository->delete($challenge->id());
        $this->auditLog->log(new DomainEvent(DomainEvent::MFA_SUCCEEDED, $context));
        return $this->startSession($user, $tenant, $challenge->device(), $challenge->ip(), true);}
    private function openMfaChallenge(User $user, AuthenticateCommand $command, MfaRequirement $requirement): AuthResult
    {
        if ($user->totpSecret() === null) {
            throw new DomainException('El usuario requiere MFA pero no tiene TOTP enrolado', 403);}
        $challenge = MfaChallenge::open($user->tenantId(), $user->id(), $command->device(), $command->ip());
        $this->challengeRepository->save($challenge);
        $this->auditLog->log(new DomainEvent(DomainEvent::MFA_CHALLENGED, [
            'tenant_id' => $user->tenantId()->value(),
            'user_id' => $user->id()->value(),
            'method' => $requirement->type(),
            'challenge_id' => $challenge->id(),]));
        return AuthResult::mfaChallenge($user, $challenge->id(), $requirement->type());}
    private function startSession(User $user, Tenant $tenant, DeviceFingerprint $device, string $ip, bool $mfaUsed): AuthResult
    {
        if ($tenant->policy('single_session', false)) {
            $this->revokeOpenSessions($user, $tenant);}
        // El mismo SessionId va en el JWT y en la sesión persistida.
        $sessionId = SessionId::generate();
        $this->sessionRepository->save(Session::start($sessionId, $user->id(), $tenant->id(), $device));
        $accessToken = $this->tokenFactory->createAccessToken($user, $tenant, $sessionId, $device);
        $refresh = $this->refreshRotator->issue($tenant->id(), $user->id(), $sessionId, $device);
        $user->recordLogin();
        $this->userRepository->save($user);
        $this->auditLog->log(new DomainEvent(DomainEvent::LOGIN_SUCCESS, [
            'tenant_id' => $tenant->id()->value(),
            'user_id' => $user->id()->value(),
            'roles' => $user->roleValues(),
            'mfa_used' => $mfaUsed,
            'device_id' => $device->deviceId(),
            'ip' => $ip,
            'session_id' => $sessionId->value(),]));
        return AuthResult::authenticated($user, $accessToken, $refresh->token(), $this->tokenFactory->accessTokenTtl(), $sessionId);}
    private function revokeOpenSessions(User $user, Tenant $tenant): void
    {
        $revoked = 0;
        foreach ($this->sessionRepository->findByUser($tenant->id(), $user->id()) as $session) {
            if (!$session->isRevoked()) {
                $session->revoke();
                $this->sessionRepository->save($session);
                $this->refreshRepository->revokeBySession($session->id());
                $revoked++;}}
        if ($revoked > 0) {
            $this->auditLog->log(new DomainEvent(DomainEvent::SESSION_REVOKED, [
                'tenant_id' => $tenant->id()->value(),
                'user_id' => $user->id()->value(),
                'reason' => 'single_session_policy',
                'revoked_count' => $revoked,]));}}
    private function handleFailedLogin(User $user, Tenant $tenant, AuthenticateCommand $command): void
    {
        $attempts = $user->registerFailedLogin();
        $this->fail($command, 'bad_password');
        if ($attempts >= (int) $tenant->policy('max_login_attempts', 5)) {
            $duration = $tenant->lockoutDuration($attempts);
            $user->lockFor($duration);
            $this->auditLog->log(new DomainEvent(DomainEvent::ACCOUNT_LOCKED, [
                'tenant_id' => $tenant->id()->value(),
                'user_id' => $user->id()->value(),
                'lock_type' => 'login',
                'duration_seconds' => $duration,
                'failed_attempts' => $attempts,]));}
        $this->userRepository->save($user);}
    private function fail(AuthenticateCommand $command, string $reason): void
    {
        $this->auditLog->log(new DomainEvent(DomainEvent::LOGIN_FAILED, [
            'tenant_id' => $command->tenantId()->value(),
            'email' => $command->email()->value(),
            'reason' => $reason,
            'ip' => $command->ip(),]));}}
