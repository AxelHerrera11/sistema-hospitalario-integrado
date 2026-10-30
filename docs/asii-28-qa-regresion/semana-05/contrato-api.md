# Contrato API REST preliminar — no implementado

Prefijo propuesto `/api/v1/qa`. Actualmente no hay rutas QA registradas.
Todos los ejemplos siguientes son **contractuales**, no respuestas obtenidas por HTTP.
Primera etapa solo casos manuales (RF-01). No hay listado masivo, actualización
o borrado; CaseRepository dispone de save/find, no de list/update/delete.

## Reglas comunes propuestas

JSON UTF-8, Accept: application/json; POST además Content-Type: application/json.
Bearer y X-Tenant-ID son obligatorios en el host futuro; nunca incluir tokens en
ejemplos. Resolver primero propiedad/aislamiento según arquitectura-cliente-servidor.
Permisos `qa.casos.registrar` y `qa.casos.ver` son **propuestas**, no claves
registradas ni autorizaciones vigentes. Deben acordarse/registrarse antes de exponer rutas.
Autenticación/contexto/permisos se evalúan antes de buscar o guardar el recurso.

| Método / ruta propuesta | Objetivo y parámetros | Entrada y validación | Éxito propuesto | Errores previstos | Estado real |
|---|---|---|---|---|---|
| POST /api/v1/qa/manual-cases | Crear caso, sin ID elegido por cliente; permiso propuesto qa.casos.registrar | Objeto JSON con purpose, steps, expected, todos string; trim y no vacíos. Pasos es texto multilínea. Sin query params | 201, Location de detalle y data del caso; meta.request_id | 400 JSON/tipo/campo ausente; 401/403 contexto/acceso; 413 tamaño; 415 tipo de contenido; 422 texto vacío; 500/503 | DesignCase/CaseController existen; adaptación HTTP, seguridad y persistencia durable pendientes |
| GET /api/v1/qa/manual-cases/{id} | Recuperar detalle; permiso propuesto qa.casos.ver | Sin body/query; id externo propuesto de 32 hex minúsculas, generado por servidor | 200, data del caso; meta.request_id | 400 id/contexto inválido; 401/403; 404 recurso ausente o ajeno; 500/503 | find existe en repositorio; caso de uso, controlador de lectura y ruta pendientes |

Validación externa propuesta: máximo 64 KiB por solicitud; campos adicionales
rechazados como INVALID_INPUT. Esto es NUEVO: CaseController actual ignora extras
y no limita tamaño. La restricción HTTP de ID no modifica el contrato Repository
que acepta identidades opacas (sus pruebas usan también fixed/missing).
El GET no revela existencia de otro ámbito: devuelve 404 después de autenticar.

## Solicitudes y respuestas de referencia

POST body:
```json
{"purpose":"Revisar formulario QA","steps":"1. Abrir el formulario\n2. Revisar campos","expected":"Propósito, pasos y esperado visibles"}
```

201 (Location: /api/v1/qa/manual-cases/0123456789abcdef0123456789abcdef):
```json
{"data":{"id":"0123456789abcdef0123456789abcdef","purpose":"Revisar formulario QA","steps":"1. Abrir el formulario\n2. Revisar campos","expected":"Propósito, pasos y esperado visibles"},"meta":{"request_id":"qa-ejemplo-01"}}
```

GET de ese ID: 200 con el mismo data y correlación nueva por solicitud.
Error 422:
```json
{"error":"CASE_INCOMPLETE","meta":{"request_id":"qa-ejemplo-02"}}
```

Error 404:
```json
{"error":"CASE_NOT_FOUND","meta":{"request_id":"qa-ejemplo-03"}}
```

No retornar el sobre CLI `{"status":201,"body":...}` como payload HTTP:
el adaptador enviará status HTTP y el contenido de body, añadiendo meta. El núcleo
actual no genera request_id, Location ni respuestas HTTP; se implementarán en el host.

## Errores y compatibilidad

| Código / error propuesto | Tratamiento y relación con lo existente |
|---|---|
| 400 INVALID_JSON / INVALID_INPUT | JSON mal formado o forma/tipos/campo requerido ausente; esos errores ya existen como resultados CLI. HTTP real aún pendiente |
| 400 TENANT_REQUIRED / TENANT_INVALID | Comportamiento existente de TenantMiddleware en HIS, pendiente de conectar a rutas QA |
| 401 TOKEN_MISSING / error de token-sesión | Política del host JwtAuth; mantener su catálogo y respuesta, sin inventar JWT QA |
| 403 RBAC_DENIED / TENANT_MISMATCH / HOSPITAL_INACTIVE | Permiso, identidad o contexto denegados por el host; sus formatos difieren del error CLI |
| 404 CASE_NOT_FOUND | Nuevo para consulta ausente/ajena; hospital inexistente conserva 404 HOSPITAL_NOT_FOUND del host |
| 413 PAYLOAD_TOO_LARGE / 415 UNSUPPORTED_MEDIA_TYPE | Nuevas comprobaciones de frontera, pendientes |
| 422 CASE_INCOMPLETE | Regla actual de dominio expuesta hoy en resultado CLI |
| 503 CASE_STORAGE_UNAVAILABLE | Resultado CLI actual para StorageFailure; GET necesitará captura equivalente |
| 500 INTERNAL_ERROR | Nuevo manejo seguro de inesperados, sin SQL, paths ni stack |

El host futuro normalizará envoltura/correlación sin perder código/status de
middleware; esa normalización y sus pruebas aún no existen. No presentar una
respuesta middleware actual como si ya incluyera meta.request_id.
409 CONFLICT se reserva para un conflicto funcional futuro explícito, **no aplica
a estas dos rutas hoy**: no hay unicidad por propósito ni idempotencia; duplicar
un ID en Repository se traduce actualmente a StorageFailure/503, no a 409.

## Recursos posteriores, sin rutas contratadas aún

| Recurso | Relación/requisito y decisión pendiente |
|---|---|
| Campañas y casos asociados | RF-02; validar existencia/diseño y pertenencia antes de asociación |
| Ejecuciones y resultados | RF-03/04/08; observaciones, comparación registrada y aprobado/fallido; nuevas ejecuciones no sobrescriben historia |
| Evidencias | RF-05; referencia privada ligada a ejecución; tamaño/tipo/retención por acordar |
| Defectos | RF-06; solo ejecución fallida; severidad y ciclo de vida pendientes |
| Trazabilidad | RF-07 y RNF-01; campaña → caso → ejecución → evidencia/defecto, filtrado por ámbito |

Se especificarán payloads, permisos y rutas al aprobar cada incremento; no se
publican endpoints ficticios para aparentar cobertura completa de RF-02–08.
