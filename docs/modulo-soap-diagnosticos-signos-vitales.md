# ASII-05 - Notas SOAP, Diagnósticos y Signos Vitales

Responsable: Lis Rosales  
Rama de trabajo: feature/asii-05-soap-diagnosticos-signos-vitales-lis  
Área asignada: Notas SOAP, Diagnósticos y Signos Vitales  
Módulos originales: 11 y 13

## 1. Alcance del módulo

El módulo administra el registro y consulta de notas clínicas en formato SOAP (Subjective, Objective, Assessment, Plan), diagnósticos asociados (CIE-10) y signos vitales del paciente. Se integra con EMR y puede asociarse opcionalmente a una admisión activa.

Fuera de alcance:
- Creación de expedientes médicos o pacientes (Área 2)
- Creación de prescripciones (Área 6) - referenciadas pero gestionadas por Área 6
- Generación de alertas críticas (Área 8) - solo registra has_alert/alert_details
- Gestión de laboratorio (Área 7)
- Auditoría centralizada (Área 1)

## 2. Actores

| Actor | Responsabilidad |
|---|---|
| Médico | Crea, consulta, actualiza y firma notas SOAP; agrega/edita/elimina diagnósticos CIE-10 |
| Enfermera | Registra y consulta el histórico de signos vitales; solo lee nota SOAP |
| Recepcionista | Sin acceso a este módulo (no tiene permisos soap.* ni signos_vitales.*) |
| Admin | Gestión/consulta según permisos RBAC (todos los permisos) |

## 3. Casos de uso

| Codigo | Caso de uso | Actor principal | Resultado esperado |
|---|---|---|---|
| CU-SOAP-01 | Crear nota SOAP | Médico | Nota SOAP creada asociada a expediente |
| CU-SOAP-02 | Consultar notas SOAP | Médico, Enfermera | Timeline por expediente |
| CU-SOAP-03 | Ver detalle nota SOAP | Médico, Enfermera | Detalle + diagnosticos + prescripciones |
| CU-SOAP-04 | Actualizar nota SOAP | Médico | Actualiza (bloqueado si firmado) |
| CU-SOAP-05 | Firmar nota SOAP | Médico | Firma con electronic_sign + signed_at |
| CU-DIAG-01 | Agregar diagnostico | Médico | Diagnostico CIE-10 a SOAP |
| CU-DIAG-02 | Editar diagnostico | Médico | Actualiza tipo/descripcion/codigo |
| CU-DIAG-03 | Eliminar diagnostico | Médico | Elimina diagnostico de SOAP |
| CU-VS-01 | Registrar signos vitales | Enfermera | Registro con measured_at + calculo alertas |
| CU-VS-02 | Consultar signos vitales | Médico, Enfermera | Historico ordenado por fecha |
| CU-VS-03 | Consultar VS por expediente | Médico, Enfermera | Listado filtrable |

## 4. Requerimientos funcionales

| Codigo | Requerimiento | Criterio de aceptación |
|---|---|---|
| RF-SOAP-01 | Crear SOAP vinculada a expediente | medical_record_id valido (tenant), admission_id opcional, doctor_id obligatorio, S/O/A/P obligatorios |
| RF-SOAP-02 | Timeline por expediente | Ordenado por created_at desc, paginado, filtrable por medical_record_id/doctor_id/admission_id/fechas |
| RF-SOAP-03 | Proteger notas firmadas | No actualizar ni agregar diagnósticos si signed_at != null |
| RF-SOAP-04 | Firmar nota SOAP | set electronic_sign + signed_at (datetime) |
| RF-SOAP-05 | Carga de relaciones | Detalle incluye diagnoses, prescriptions; evitar N+1 |
| RF-DIAG-01 | Agregar diagnostico CIE-10 | soap_note_id obligatorio, cie10_code max 10, description max 200, type enum [principal,secundario,presuntivo,definitivo] default presuntivo |
| RF-DIAG-02 | Gestion diagnosticos | Editar/eliminar con validación pertenencia tenant/SOAP |
| RF-VS-01 | Registrar signos vitales | medical_record_id obligatorio, registered_by usuario autenticado, measured_at obligatorio |
| RF-VS-02 | Deteccion alertas | has_alert bool + alert_details JSON segun valores anormales (no genera alerta critica) |
| RF-VS-03 | Filtrado/ordenamiento | Por medical_record_id/admission_id/fechas, ordenado por measured_at desc, paginado |
| RF-TEN-01 | Aislamiento por tenant | BelongsToTenant en consultas + Rule::exists escopada por tenant; 404 si no pertenece |

## 5. Requerimientos no funcionales

| Codigo | Requerimiento | Criterio de aceptación |
|---|---|---|
| RNF-01 | Autorizacion RBAC | Middlewares tenant, auth.jwt, permission: soap.ver/crear/firmar, signos_vitales.ver/registrar |
| RNF-02 | Aislamiento estricto | BelongsToTenant + Rule::exists con where tenant_id; resolucion por ID; 404 (no 403) |
| RNF-03 | Validacion defensiva | Enums, tipos, longitudes, fechas, max de columna decimal, lista blanca ordenamiento |
| RNF-04 | Rendimiento | Paginacion, eager loading, indices BD |
| RNF-05 | Consistencia API | Paginador nativo Laravel (metadata en raiz) |
| RNF-06 | Integridad referencial | Diagnosticos cascade con SOAP |

## 6. Principio SOLID aplicado

Ejemplo concreto de **SRP**: el cálculo de alertas de signos vitales vive en `App\Services\VitalSignEvaluator`, no en el controller.

```php
// app/Services/VitalSignEvaluator.php
class VitalSignEvaluator
{
    /** @return array{has_alert: bool, alert_details: array|null} */
    public function evaluate(array $data): array
    {
        $alerts = [];
        // compara cada campo con su rango de referencia y acumula fuera de rango
        return [
            'has_alert' => ! empty($alerts),
            'alert_details' => empty($alerts) ? null : $alerts,
        ];
    }
}

// app/Http/Controllers/Api/V1/VitalSignController.php — orquesta, no calcula
public function store(StoreVitalSignRequest $request): JsonResponse
{
    $data = $request->validated();
    $data['tenant_id'] = $request->attributes->get('tenant')->id;
    $data['registered_by'] = Auth::id();
    $evaluation = $this->evaluator->evaluate($data);
    $data['has_alert'] = $evaluation['has_alert'];
    $data['alert_details'] = $evaluation['alert_details'];
    $vs = VitalSign::query()->create($data);

    return response()->json(['vital_sign' => $vs], 201);
}
```

El resto: **O/C** (rangos y enums extensibles sin tocar el controller), **LSP** (los modelos son intercambiables gracias al scope global BelongsToTenant), **ISP** (un FormRequest focalizado por operación: Store vs Update), **DIP** (el controller recibe `VitalSignEvaluator` inyectado, no lo instancia).

## 7. Vista arquitectónica

| Capa | Responsabilidad | Componentes |
|---|---|---|
| UI (Vue) | Formularios SOAP, timeline, VS | modulos/area5 (futuro) |
| API (Laravel) | Endpoints v1 | routes/api.php bloque Area 5, Controllers Api/V1 |
| Validacion | Reglas entrada | Form Requests (Store/Update) con scope tenant |
| Logica Negocio | Reglas firma, calculo alertas VS | VitalSignEvaluator (Services) |
| Modelos | Entidades/relaciones | SoapNote, Diagnosis, VitalSign, MedicalRecord, Doctor, Admission |
| Persistencia | DB + aislamiento | emr_tables + indices |

## 8. Contrato API preliminar

Rutas protegidas: ['tenant','auth.jwt'] + permission.

| Metodo | Ruta | Permiso | Descripcion |
|---|---|---|---|
| GET | /api/v1/soap-notes | soap.ver | Listar (filtros: medical_record_id,doctor_id,admission_id,from,to,per_page,sort_by,sort_dir). Default created_at desc |
| POST | /api/v1/soap-notes | soap.crear | Crear SOAP |
| GET | /api/v1/soap-notes/{soap_note} | soap.ver | Detalle + diagnoses,prescriptions |
| PUT | /api/v1/soap-notes/{soap_note} | soap.crear | Actualizar (bloqueado si firmado) |
| POST | /api/v1/soap-notes/{soap_note}/sign | soap.firmar | Firmar {electronic_sign} |
| POST | /api/v1/soap-notes/{soap_note}/diagnoses | soap.crear | Agregar diagnostico |
| PUT | /api/v1/diagnoses/{diagnosis} | soap.crear | Editar diagnostico |
| DELETE | /api/v1/diagnoses/{diagnosis} | soap.crear | Eliminar diagnostico |
| GET | /api/v1/vital-signs | signos_vitales.ver | Listar (medical_record_id,admission_id,from,to,per_page,sort_by=measured_at,sort_dir) |
| POST | /api/v1/vital-signs | signos_vitales.registrar | Registrar VS |
| GET | /api/v1/medical-records/{medical_record}/vital-signs | signos_vitales.ver | Timeline VS por expediente |