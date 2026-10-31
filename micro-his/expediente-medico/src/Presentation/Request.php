<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Presentation;

use MicroHis\ExpedienteMedico\Application\RequestContext;
use MicroHis\ExpedienteMedico\Domain\ExpedienteRuleViolation;

/**
 * Petición HTTP ya decodificada (el front controller la arma desde las superglobales). Valida el formato de las
 * cabeceras de identidad y tenant antes de llegar al caso de uso.
 *
 * PROVISIONAL: en el micro-HIS la identidad llega en X-User-ID (local_user_ref_id), como en los demás micro-HIS.
 * En Laravel la resuelve el JWT de Auth y el hospital la cabecera X-Tenant-ID validada por el middleware de tenant.
 */
final readonly class Request
{
    private const IDENTIFIER = '/^[A-Za-z0-9][A-Za-z0-9._:@-]{0,127}$/D';
    private const CORRELATION = '/^[A-Za-z0-9][A-Za-z0-9._:-]{0,63}$/D';

    /** @param array<string, string> $headers */
    public function __construct(public string $method, public string $path, public array $headers = [])
    {
    }

    public function context(): RequestContext
    {
        $actor = $this->header('X-User-ID');
        if (preg_match(self::IDENTIFIER, $actor) !== 1) {
            throw new ExpedienteRuleViolation('IDENTIDAD_REQUERIDA', 401);
        }
        $hospital = $this->header('X-Tenant-ID');
        if (preg_match(self::IDENTIFIER, $hospital) !== 1) {
            throw new ExpedienteRuleViolation('TENANT_REQUERIDO', 400);
        }
        $correlation = $this->header('X-Correlation-ID');
        // Solo la arma el servidor (adaptador Laravel); public/index.php no reenvía esta cabecera desde el cliente.
        $localRef = $this->header('X-Local-User-Ref');

        return new RequestContext(
            $actor,
            $hospital,
            preg_match(self::CORRELATION, $correlation) === 1 ? $correlation : null,
            preg_match(self::IDENTIFIER, $localRef) === 1 ? $localRef : null,
        );
    }

    private function header(string $name): string
    {
        $value = $this->headers[$name] ?? '';

        return is_string($value) ? trim($value) : '';
    }
}
