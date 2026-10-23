<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Presentation;

use MicroHis\QaCalidad\Application\AccessDenied;
use MicroHis\QaCalidad\Application\AccessUnavailable;
use MicroHis\QaCalidad\Application\RunNotFound;
use MicroHis\QaCalidad\Domain\InvalidQaInput;
use MicroHis\QaCalidad\Domain\QaStorageFailure;
use MicroHis\QaCalidad\Domain\RunStateConflict;

/** Única tabla excepción → estado HTTP del módulo (contrato de errores, Semana 5). */
final class ErrorMapper
{
    /** @param callable(string, array<string, mixed>): void $report */
    public function __construct(private $report = null) {}

    public function map(\Throwable $error, string $trace): array
    {
        return match (true) {
            $error instanceof AccessDenied => HttpResponse::error(403, 'RBAC_DENIED', $trace),
            $error instanceof AccessUnavailable => HttpResponse::error(503, 'RBAC_UNAVAILABLE', $trace),
            $error instanceof RunNotFound => HttpResponse::error(404, 'QA_RUN_NOT_FOUND', $trace),
            $error instanceof InvalidQaInput => HttpResponse::error(422, 'QA_VALIDATION_FAILED', $trace, ['reason' => $error->reason, 'message' => $error->getMessage()]),
            $error instanceof RunStateConflict => HttpResponse::error(409, 'QA_RUN_STATE_CONFLICT', $trace, ['reason' => $error->reason, 'message' => $error->getMessage()]),
            $error instanceof QaStorageFailure => $this->reported(503, 'QA_STORAGE_UNAVAILABLE', $error, $trace),
            default => $this->reported(500, 'INTERNAL_ERROR', $error, $trace),
        };
    }

    private function reported(int $status, string $code, \Throwable $error, string $trace): array
    {
        // Al cliente solo le llega el código; la causa técnica va al reporte interno.
        $this->report !== null && ($this->report)($code, ['type' => $error::class, 'cause' => $error->getMessage(), 'trace_id' => $trace]);

        return HttpResponse::error($status, $code, $trace);
    }
}
