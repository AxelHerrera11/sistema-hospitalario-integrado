<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Presentation;

/** Rutas del módulo: un recurso (el expediente del paciente) con GET (consulta) y POST (apertura). Lo demás, 404/405. */
final class Router
{
    private const RECORD = '#^/patients/([^/]+)/medical-record$#';

    public function __construct(private readonly MedicalRecordController $records, private readonly JsonView $view)
    {
    }

    public function dispatch(Request $request): array
    {
        if (preg_match(self::RECORD, $request->path, $m) !== 1) {
            return $this->view->render(404, ['code' => 'RUTA_NO_ENCONTRADA']);
        }
        $patientCode = rawurldecode($m[1]);

        return match ($request->method) {
            'GET' => $this->records->consult($request, $patientCode),
            'POST' => $this->records->open($request, $patientCode),
            default => $this->view->render(405, ['code' => 'METODO_NO_PERMITIDO']),
        };
    }
}
