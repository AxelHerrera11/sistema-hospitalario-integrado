# Hallazgos de revisión

Severidad Alta/Media/Baja. Los dos hallazgos altos son bloqueos previos a
exponer funcionalidad sensible; no describen una ruta de Pacientes vulnerable
ya activa, puesto que no hay rutas de ese módulo en `routes/api.php`.

## REV-01 — Falta la frontera HTTP de Pacientes

- **Severidad / prioridad:** Alta / P1.
- **Descripción:** el `PatientController` del Micro-HIS produce arreglos de
  aplicación; no hay Controller/Adapter Laravel, ruta de Pacientes, cliente
  Vue ni composición que conecte autenticación, tenant, servicio y PDO.
- **Evidencia:** `routes/api.php` contiene autenticación y rutas admin, sin
  Pacientes; `resources/js/modules/patients/` no existe. El plan de Semana 5
  declara explícitamente que el adaptador no está cableado.
- **Impacto:** operaciones del dominio no pueden usarse desde el HIS como
  flujo HTTP integrado; OpenAPI no constituye API ejecutable.
- **Recomendación:** primer paso: definir el adapter HTTP vertical (DTO,
  status/envelope, IDs, wiring repository) y su ruta de consulta protegida,
  antes de conectar UI. Acordar selección de conexión hospitalaria por tenant.
- **Área:** Integración Laravel/API + Pacientes + Persistencia.
- **Dependencias:** mapping de IDs y errores (REV-03/04), permisos (REV-02),
  conexión PDO tenant (REV-06).
- **Archivos previstos:** `routes/api.php`; nuevo controller/adapter bajo
  `app/Http/Controllers/Api/V1/`; provider/binding de composición si aplica;
  después el futuro cliente en `resources/js/`.

## REV-02 — Permisos declarados, no aplicados a operaciones de Pacientes

- **Severidad / prioridad:** Alta / P1, puerta de seguridad para REV-01.
- **Descripción:** `pacientes.ver`, `pacientes.registrar` y `pacientes.editar`
  existen en configuración/OpenAPI, pero no hay ruta que use
  `RequirePermission`. La sesión de frontend entrega roles, no
  `user.permissions`; `can()` falla cerrada sin esa fuente.
- **Evidencia:** `config/rbac.php`; rutas actuales de `routes/api.php`; alias
  `rbac` en `RbacServiceProvider`; payload `me` en `AuthController`; helper
  `resources/js/router/permissions.js`.
- **Impacto:** todavía no hay endpoint Pacientes sin autorización; si se
  expone un endpoint sin middleware por operación, los datos sensibles
  quedarían fuera del control RBAC esperado. Ocultar botones no protege API.
- **Recomendación:** aplicar middleware `tenant` → `jwt.auth` → `rbac:<permiso>`
  a cada ruta; probar allow/deny por usuario/tenant. Definir si y cómo se
  publica permiso efectivo al cliente; mantener autorización server-side.
- **Área:** Identidad/Tenant/RBAC + Integración API.
- **Dependencias:** ruta y adapter REV-01; fuente efectiva para UX frontend.
- **Archivos previstos:** `routes/api.php`, controller/feature tests de
  autorización; payload frontend solo mediante acuerdo con Auth/RBAC.

## REV-03 — Identificador público no se traduce en la vista interna

- **Severidad / prioridad:** Media / P2.
- **Descripción:** PostgreSQL usa `local_patient_id`; Domain lo conserva como
  `Patient::$id`; `PatientView` serializa clave `id`; OpenAPI define
  `patient_id` como requerido y público. No se encontró mapping entre ellos.
- **Evidencia:** migration `2026_08_11_000200...`; `PdoPatientRepository`
  hidrata `local_patient_id`; `PatientView::patient()` devuelve `'id'`;
  OpenAPI `Patient` exige `patient_id`.
- **Impacto:** el futuro cliente o adapter no puede consumir la respuesta
  interna como contrato sin transformación; aumenta el riesgo de perder o
  renombrar incorrectamente la identidad usada por relaciones clínicas.
- **Recomendación:** definir DTO/presenter de borde HTTP que traduzca
  `local_patient_id` → `id` interno → `patient_id` externo, sin cambiar el
  valor; agregar prueba de contrato que compruebe la salida.
- **Área:** Pacientes Presentation/API + Integración.
- **Dependencia:** REV-01.

## REV-04 — Duplicados no coinciden con códigos/respuestas del OpenAPI

- **Severidad / prioridad:** Media / P2.
- **Descripción:** duplicate code detectado en Application se convierte a
  `422`, mientras OpenAPI especifica `409` para código o `global_id`
  duplicado. `global_id` no se busca antes del insert; su unique violation
  PDO se envuelve como `PersistenceFailure` y el presenter devuelve `500`.
  Una carrera tras la comprobación de código puede dar el mismo resultado.
- **Evidencia:** `PatientService::register()` solo consulta por code;
  constraints únicas en migration; presenter asigna `422` a todos los
  `PatientOperationFailure` y `500` a `PersistenceFailure`; test espera
  `422 PATIENT_CODE_EXISTS`; OpenAPI define `409 Conflict`.
- **Impacto:** cliente recibe error interno o clasificación incompatible para
  una condición de negocio esperable; no puede señalar el conflicto con
  consistencia.
- **Recomendación:** acordar mapping de conflicto para ambos constraints,
  incluyendo violaciones de unicidad bajo carrera; preservar códigos/fields
  estables, traducir a Problem JSON solo en adapter HTTP y no exponer detalles
  PDO.
- **Área:** Pacientes Application/Persistence + API.
- **Dependencia:** REV-01.

## REV-05 — `global_id` no valida UUID antes de persistir

- **Severidad / prioridad:** Media / P2.
- **Descripción:** OpenAPI exige formato UUID y PostgreSQL almacena `global_id`
  como `uuid`, pero `Patient` solo comprueba que no sea cadena vacía. El valor
  malformado pasa dominio y la excepción PDO se presenta como fallo 500.
- **Evidencia:** `Patient::__construct()`; OpenAPI `PatientCreate.global_id`;
  columna de migration; catch PDO en `PdoPatientRepository`.
- **Impacto:** entrada inválida no produce error de validación accionable;
  una futura API puede clasificar como error interno un dato rechazable.
- **Recomendación:** validar DTO/formato en borde (y definir si Domain debe
  proteger también esa invariante); cubrir UUID inválido y mapearlo a 422
  según contrato, dejando las fallas de infraestructura como 500.
- **Área:** Pacientes Domain/API.
- **Dependencia:** diseño del adapter REV-01.

## REV-06 — Sin prueba directa del repositorio PDO de Pacientes

- **Severidad / prioridad:** Media / P3.
- **Descripción:** suite propia instancia `InMemoryPatientRepository`; no
  prueba sentencias, hydration, `RETURNING`, uniqueness ni update del
  `PdoPatientRepository`. Existen pruebas Laravel PostgreSQL de esquema y
  relaciones locales, no de este adapter.
- **Evidencia:** `micro-his/pacientes/tests/run.php`; tests
  `CareIntegrityTest` y otras pruebas de constraints; no hay referencia a
  `PdoPatientRepository` bajo tests.
- **Impacto:** regresiones o diferencias de tipos/envelopes entre el doble y
  PostgreSQL pueden pasar la suite del módulo.
- **Recomendación:** agregar prueba de integración aislada con PostgreSQL de
  test para alta, búsqueda, update y conflictos; no sustituir tests InMemory.
- **Área:** Pacientes/Persistencia + QA/DB.
- **Dependencia:** conexión/configuración reproducible de tests PostgreSQL.
- **Archivos previstos:** nuevo test bajo `tests/Database/Hospital/` o
  `micro-his/pacientes/tests/` con bootstrap de integración.

## REV-07 — UPDATE no verifica si persistió alguna fila

- **Severidad / prioridad:** Baja / P4.
- **Descripción:** tras ejecutar `UPDATE ... WHERE local_patient_id=:id`,
  `PdoPatientRepository::update()` devuelve `$p` sin comprobar filas
  afectadas.
- **Evidencia:** sentencia y retorno de `update()` en
  `PdoPatientRepository.php`; el servicio busca primero por código y luego
  actualiza por ID.
- **Impacto:** si la fila desaparece entre búsqueda y actualización, el
  resultado puede aparentar éxito aunque PostgreSQL no actualizó registro.
- **Recomendación:** tratar `rowCount() === 0` como not-found/conflicto con
  semántica acordada y añadir prueba para la condición concurrente.
- **Área:** Pacientes/Persistencia + API.
- **Dependencia:** adapter que exponga el status al contrato (REV-01).

## Ruta de integración propuesta

`Vue Pacientes` (no existe) → `routes/api.php` (sin endpoints Pacientes) →
middlewares Tenant/JWT/RBAC (registrados globalmente, no aplicados aquí) →
HTTP Controller/Adapter (pendiente) → `PatientService` → `PatientRepository`
→ `PdoPatientRepository` (implementado, constructor recibe PDO) →
PostgreSQL `hospital.local_patients` (migration existe).

Orden de trabajo: (1) acordar con Integración y Persistencia el wiring HTTP y
conexión hospitalaria del tenant; (2) incluir middleware por permiso y
probar denegación/autorización con Auth/RBAC; (3) hacer mapping de IDs/errores
y pruebas PostgreSQL; (4) después conectar Vue y diseñar permisos frontend.
