<?php
declare(strict_types=1);
namespace App\Modules\Tenants\Presentation\Controllers;
use App\Modules\Tenants\Application\Commands\RegisterTenantCommand;
use App\Modules\Tenants\Application\UseCases\RegisterTenantUseCase;
use App\Modules\Tenants\Domain\Entities\Tenant;
use App\Modules\Tenants\Domain\Repositories\TenantRepositoryInterface;
use App\Modules\Tenants\Domain\ValueObjects\JwtClaims;
use App\Modules\Tenants\Domain\ValueObjects\Role;
use App\Modules\Tenants\Domain\ValueObjects\TenantId;
use App\Modules\Tenants\Presentation\Transformers\AuthResponseTransformer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
/** Alta y consulta de hospitales; solo superadmin. */
final class AdminTenantController
{
    public function __construct(
        private RegisterTenantUseCase $registerUseCase,
        private TenantRepositoryInterface $tenants,
        private AuthResponseTransformer $transformer
    ) {}
    public function list(Request $request): Response
    {
        self::authorize($request);
        return $this->transformer->json(['data' => array_map(fn(Tenant $t) => $t->toArray(), $this->tenants->findAll())]);}
    public function create(Request $request): Response
    {
        self::authorize($request);
        $data = (array) $request->getParsedBody();
        $result = $this->registerUseCase->execute(new RegisterTenantCommand(
            (string) ($data['name'] ?? ''),
            (int) ($data['user_quota'] ?? 100),
            is_array($data['policies'] ?? null) ? $data['policies'] : []));
        return $this->transformer->json(['tenant_id' => $result->tenantId()->value(), 'name' => $result->name()], 201);}
    public function get(Request $request): Response
    {
        self::authorize($request);
        $tenant = $this->tenants->findById(new TenantId((string) $request->getAttribute('id')));
        if ($tenant === null) {
            return $this->transformer->error('Hospital no encontrado', 404);}
        return $this->transformer->json($tenant->toArray());}
    private static function authorize(Request $request): void
    {
        $auth = $request->getAttribute('auth');
        if (!$auth instanceof JwtClaims) {
            throw new \DomainException('Usuario no autenticado', 401);}
        if (!$auth->hasRole(Role::SUPERADMIN)) {
            throw new \DomainException('Requiere el rol superadmin', 403);}}}
