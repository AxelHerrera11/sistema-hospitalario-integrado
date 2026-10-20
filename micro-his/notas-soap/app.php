<?php

declare(strict_types=1);

use MicroHis\NotasSoap\Application\AssociateDiagnosis;
use MicroHis\NotasSoap\Application\CareAssignmentReader;
use MicroHis\NotasSoap\Application\ClinicalNoteRepository;
use MicroHis\NotasSoap\Application\Icd10Catalog;
use MicroHis\NotasSoap\Application\PermissionChecker;
use MicroHis\NotasSoap\Application\RegisterSoapNote;
use MicroHis\NotasSoap\Application\TreatingPhysicianGuard;
use MicroHis\NotasSoap\Domain\DiagnosisPolicy;
use MicroHis\NotasSoap\Presentation\ApiKernel;
use MicroHis\NotasSoap\Presentation\JsonView;
use MicroHis\NotasSoap\Presentation\Router;
use MicroHis\NotasSoap\Presentation\SoapNoteController;

require __DIR__ . '/bootstrap.php';

/** Raíz de composición: el mismo MVC funciona con cualquier adaptador del Repository (PDO o InMemory). */
function notas_soap_router(ClinicalNoteRepository $notes, CareAssignmentReader $assignments, Icd10Catalog $catalog): Router
{
    $view = new JsonView();
    $guard = new TreatingPhysicianGuard($assignments);
    $controller = new SoapNoteController(
        new RegisterSoapNote($guard, $notes),
        new AssociateDiagnosis($guard, $catalog, new DiagnosisPolicy(), $notes),
        $view,
    );
    return new Router($controller, $view);
}

/** Servidor API v1 (Semana 5): el mismo Router detrás de autenticación, permisos RBAC y trace_id. */
function notas_soap_api(ClinicalNoteRepository $notes, CareAssignmentReader $assignments, Icd10Catalog $catalog, PermissionChecker $permissions, ?Closure $traceIds = null, ?Closure $log = null): ApiKernel
{
    return new ApiKernel(notas_soap_router($notes, $assignments, $catalog), $permissions, new JsonView(), $traceIds, $log);
}
