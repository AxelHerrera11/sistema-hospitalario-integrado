<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Tenants\Application\Administration\ManageHospitalMembership;
use App\Modules\Tenants\Application\Identity\AuthenticateUser;
use App\Modules\Tenants\Application\Identity\RefreshUserSession;
use App\Modules\Tenants\Domain\Audit\AuditLogRepositoryInterface;
use App\Modules\Tenants\Infrastructure\Audit\AuditLogRepository;
use App\Modules\Tenants\Infrastructure\Persistence\MembershipRepository;
use App\Modules\Tenants\Infrastructure\Persistence\RefreshTokenRepository;
use App\Modules\Tenants\Infrastructure\Persistence\SessionRepository;
use App\Modules\Tenants\Infrastructure\Security\JwtAccessTokenIssuer;
use App\Rbac\AuditDenials;
use Illuminate\Support\ServiceProvider;
use MicroHis\Rbac\Domain\DenialRecorder;

final class TenantsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(base_path('config/tenants.php'), 'tenants');

        $this->app->singleton(MembershipRepository::class, fn () => new MembershipRepository);
        $this->app->singleton(SessionRepository::class, fn () => new SessionRepository);
        $this->app->singleton(RefreshTokenRepository::class, fn () => new RefreshTokenRepository);
        $this->app->singleton(AuditLogRepositoryInterface::class, fn () => new AuditLogRepository);
        $this->app->bind(DenialRecorder::class, AuditDenials::class);
        $this->app->bind(JwtAccessTokenIssuer::class, fn () => JwtAccessTokenIssuer::fromConfig());
        $this->app->bind(AuthenticateUser::class, fn ($app) => new AuthenticateUser(
            $app->make(MembershipRepository::class),
            $app->make(SessionRepository::class),
            $app->make(RefreshTokenRepository::class),
            $app->make(JwtAccessTokenIssuer::class),
            $app->make(AuditLogRepositoryInterface::class),
        ));
        $this->app->bind(RefreshUserSession::class, fn ($app) => new RefreshUserSession(
            $app->make(MembershipRepository::class),
            $app->make(SessionRepository::class),
            $app->make(RefreshTokenRepository::class),
            $app->make(JwtAccessTokenIssuer::class),
            $app->make(AuditLogRepositoryInterface::class),
            $app->make(AuthenticateUser::class),
        ));
        $this->app->bind(ManageHospitalMembership::class, fn ($app) => new ManageHospitalMembership(
            $app->make(MembershipRepository::class),
            $app->make(SessionRepository::class),
            $app->make(AuditLogRepositoryInterface::class),
        ));
    }
}
