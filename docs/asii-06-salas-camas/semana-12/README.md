# ASII-06 — Semana 12: segunda evaluación parcial

## Alcance académico identificado

La plantilla general del curso enumera Semana 11 (“mockup o prototipo”) y
Semana 13 (“revisión técnica formal”), pero **no define Semana 12**. El issue
maestro #11 tampoco establece un entregable específico para esa semana y no
tiene comentarios adicionales del reviewer.

Como referencia de formato —no como requisito oficial transferible—, Semana 12
de ASII-03 Pacientes prepara una segunda evaluación parcial con evidencia
verificable que distingue implementación, documentación y pendientes. Esta
entrega usa ese precedente para organizar una defensa honesta del prototipo
ASII-06 ya integrado en Semana 11. No inventa funcionalidades ni amplía el
alcance del módulo.

## Estado verificable del módulo

- **IMPLEMENTADO:** APIs de lectura `GET /api/v1/wards` y
  `GET /api/v1/wards/{ward}/beds`; la segunda filtra por `beds.ward_id`.
- **IMPLEMENTADO:** pantalla Vue `/wards`, menú con permiso efectivo
  `camas.ver`, consulta mediante Axios compartido y estados de carga, vacío,
  error y reintento.
- **IMPLEMENTADO:** middleware de servidor `api`, `tenant`, `jwt.auth` y
  `rbac:camas.ver` para las rutas del catálogo.
- **PROBADO:** build, tests frontend, Feature de API, test de integración
  PostgreSQL con rollback y suite independiente del micro-HIS; resultados
  exactos en [matriz de trazabilidad](./matriz-trazabilidad-evidencia.md).
- **PENDIENTE:** validar manualmente el login con un hospital y una cuenta
  autorizados en el entorno local y probar la navegación autenticada en
  navegador. No se facilitaron credenciales ni se modificó la autenticación.

## Entregables de evaluación

- [Presentación](./presentacion.md): guion de defensa con estado por evidencia.
- [Walkthrough](./walkthrough.md): recorrido de 5–8 minutos y preguntas.
- [Matriz de trazabilidad](./matriz-trazabilidad-evidencia.md): afirmaciones,
  fuentes y estado.
- [Diagnóstico de integración local](./diagnostico-integracion-local.md):
  contrato de tenant, explicación del error y requisitos seguros para probar.

## Límites y riesgos

La prueba PostgreSQL confirma el repositorio del catálogo en la conexión
HOSPITAL configurada para tests y verifica rollback. La prueba de identidad
usa SQLite en memoria; no prueba un login real contra usuarios del entorno
local. `TenantMiddleware` valida un hospital, pero la conexión `hospital` del
catálogo sigue fija y `wards`/`beds` no tienen `tenant_id`; no hay aislamiento
multi-tenant completo.

No se agregan `rooms`, datos de demostración persistentes, cambios de estado,
credenciales, permisos nuevos ni operaciones clínicas. El test de integración
usa filas temporales dentro de transacción y hace rollback. No se modificaron
API, frontend, configuración, migraciones o `.env` en Semana 12.
