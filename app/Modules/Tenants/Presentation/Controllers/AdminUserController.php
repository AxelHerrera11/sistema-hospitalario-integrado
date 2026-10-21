<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Controllers;
use App\Modules\Tenants\Application\Commands\RegisterUserCommand;
use App\Modules\Tenants\Application\Commands\RevokeCommand;
use App\Modules\Tenants\Application\UseCases\RegisterUserUseCase;
use App\Modules\Tenants\Application\UseCases\RevokeSessionUseCase;
use App\Modules\Tenants\Domain\Entities\User;
use App\Modules\Tenants\Domain\Repositories\UserRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Presentation\Requests\RegisterUserRequest;
use App\Modules\Tenants\Presentation\Requests\RevokeSessionRequest;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/** Administración de usuarios del hospital del token; solo admin_hospital. */
final class AdminUserController
{
    public function __construct(
        private RegisterUserUseCase $registerUseCase,
        private RevokeSessionUseCase $revokeUseCase,
        private UserRepositoryInterface $users,
        private AuthResponseTransformer $transformer
    ) {}
    public function list(Request $request): Response
    {
        $auth = self::authorize($request);
        $query = $request->getQueryParams();
        $limit = max(1, min(100, (int) ($query['limit'] ?? 50)));
        $offset = max(0, (int) ($query['offset'] ?? 0));
        $tenantId = new TenantId($auth->tenantId());
        return $this->transformer->json([
            'data' => array_map(fn(User $u) => $u->toArray(), $this->users->findAllByTenant($tenantId, $limit, $offset)),
            'total' => $this->users->countByTenant($tenantId),]);}
    public function create(Request $request): Response
    {
        self::authorize($request);
        $data = RegisterUserRequest::fromRequest($request);
        $result = $this->registerUseCase->execute(new RegisterUserCommand(
            $data->email(),
            $data->name(),
            $data->password(),
            $data->roles(),
            $data->tenantId(),
            $data->createdBy(),
            $data->mfaRequired(),
            $data->existingUserId()));
        return $this->transformer->transformRegister($result);}
    public function revokeSessions(Request $request): Response
    {
        self::authorize($request);
        $data = RevokeSessionRequest::fromRequest($request);
        $result = $this->revokeUseCase->execute(new RevokeCommand($data->tenantId(), $data->requesterId(), null, $data->targetUserId(), $data->reason()));
        return $this->transformer->transformRevoke($result);}
    private static function authorize(Request $request): JwtClaims
    {
        $auth = $request->getAttribute('auth');
        if (!$auth instanceof JwtClaims) {
            throw new \DomainException('Usuario no autenticado', 401);}
        if (!$auth->hasRole(Role::ADMIN_HOSPITAL)) {
            throw new \DomainException('Requiere el rol admin_hospital', 403);}
        return $auth;}}
