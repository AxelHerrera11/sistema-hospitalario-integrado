<?php

declare(strict_types=1);

namespace App\GobernanzaAuditoria;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use MicroHis\Gobernanza\Application\RecordAuditEvent;
use MicroHis\Gobernanza\Domain\AuditCriticality;
use MicroHis\Gobernanza\Domain\AuditOutcome;
use MicroHis\Gobernanza\Domain\AuditStorageFailure;
use MicroHis\Gobernanza\Presentation\AuditHttpHandler;
use PDOException;

/**
 * GET /api/v1/audit-events. Solo traduce Request/Response de Laravel:
 * la identidad sale del guard "api" y del atributo "tenant" que fija
 * TenantMiddleware; la lógica vive en AuditHttpHandler y los casos de uso.
 * integrity y catalog (Semana 16): verificación de la cadena y opciones de los filtros, con el mismo permiso.
 */
final class AuditEventController
{
    public function __construct(private readonly AuditHttpHandler $handler) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');
        $tenant = $request->attributes->get('tenant');
        $server = [
            'user_id' => $user?->getAuthIdentifier(),
            'tenant_id' => is_object($tenant) && method_exists($tenant, 'getKey') ? $tenant->getKey() : null,
        ];
        $headers = [
            'X-Tenant-ID' => $request->header('X-Tenant-ID'),
            'X-Request-ID' => $request->header('X-Request-ID'),
        ];

        $result = $this->handler->search($server, $headers, $request->query());

        return new JsonResponse($result['body'], $result['status'], $result['headers']);
    }

    /** GET /api/v1/audit-events/integrity: recorre la cadena y deja constancia de la verificación en la bitácora. */
    public function integrity(Request $request, AuditChainVerifier $verifier, RecordAuditEvent $audit): JsonResponse
    {
        $hospital = self::hospitalOf($request);
        try {
            $result = $verifier->verify($hospital);
            $audit->record(
                (string) $request->user('api')?->getAuthIdentifier(),
                $hospital,
                'gobernanza',
                'integridad_verificada',
                AuditOutcome::Success,
                AuditCriticality::Security,
                null,
                null,
                $request->header('X-Request-ID') ?: null,
                $result['valid'] ? 'chain_valid' : 'chain_broken',
                ['checked' => $result['checked'], 'total' => $result['total']],
            );
        } catch (AuditStorageFailure|PDOException) {
            return self::json(['code' => 'AUDIT_UNAVAILABLE'], 503);
        }

        return self::json($result);
    }

    /** GET /api/v1/audit-events/catalog: personal del hospital y módulos con eventos, para los filtros. */
    public function catalog(Request $request, AuditFilterCatalog $catalog): JsonResponse
    {
        try {
            return self::json($catalog->for(self::hospitalOf($request)));
        } catch (PDOException) {
            return self::json(['code' => 'AUDIT_UNAVAILABLE'], 503);
        }
    }

    private static function hospitalOf(Request $request): string
    {
        $tenant = $request->attributes->get('tenant');

        return is_object($tenant) && method_exists($tenant, 'getKey') ? (string) $tenant->getKey() : '';
    }

    private static function json(array $body, int $status = 200): JsonResponse
    {
        return new JsonResponse($body, $status, ['Cache-Control' => 'no-store, private']);
    }
}
