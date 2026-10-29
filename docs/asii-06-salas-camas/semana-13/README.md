# ASII-06 — Semana 13: revisión técnica formal

## Alcance académico

La plantilla general del curso define Semana 13 como **“revisión técnica
formal”**. El issue maestro #11 contiene el objetivo y Definition of Done del
módulo, pero no agrega criterios semanales ni comentarios de revisión. La
entrega de Semana 13 de ASII-03 se usa solo como referencia de formato y método.

Esta revisión contrasta el código disponible en la rama, contratos, permisos,
datos, documentación y pruebas de ASII-06. No es una auditoría de producción,
una prueba de penetración ni una revisión de conformidad WCAG. La prueba con
dos tenants activos confirmó acceso cruzado al catálogo antes de la
contención temporal; no se presenta esta revisión documental como solución
definitiva.

## Resultado ejecutivo

- **Implementado:** endpoints GET de wards y camas por ward; prototipo Vue
  `/wards`; permiso efectivo `camas.ver`; estados de carga, vacío, error y
  reintento.
- **Probado:** build, navegación/permisos frontend, Feature API, identidad
  tenant/JWT/RBAC en fixture aislada y persistencia PostgreSQL con rollback.
- **Aislamiento cruzado confirmado y contenido temporalmente:** antes de la
  mitigación, dos tenants válidos con `camas.ver` recibían HTTP 200 desde la
  misma conexión `hospital`. El PR #1154 limita estas rutas al hospital
  explícitamente asignado a la instancia; la resolución dinámica por hospital
  sigue pendiente.
- **Pendiente:** login y recorrido visual manual con hospital y usuario de
  pruebas autorizados. No se dispuso de esas credenciales ni se inspeccionaron
  registros reales.

## Entregables

- [Plan de revisión](./plan-revision.md): alcance, método, criterios y tabla de roles y responsables.
- [Checklist técnico](./checklist.md): 14 controles con evidencia y estado.
- [Hallazgos](./hallazgos.md): riesgos, prioridades, responsables, estados y condiciones de cierre.
- [Evidencia de revisión](./evidencia-revision.md): comandos y resultados.

## Límites y decisión

El mensaje observado “El hospital indicado no es válido.” corresponde en el
código a `400 TENANT_INVALID` cuando `X-Tenant-ID` no es UUID. La causa
concreta del intento local no pudo confirmarse porque no se inspeccionó el
valor almacenado ni se recibió un hospital autorizado. El diagnóstico seguro
está en [evidencia de Semana 12](../semana-12/diagnostico-integracion-local.md).

Esta entrega de Semana 13 es documental y no modifica aplicación, migraciones,
permisos, configuración o `.env`. La contención de código se integró aparte en
#1154; no equivale a resolver la asignación dinámica de una base por hospital.
Para cerrar esa integración se requiere acordar cómo se resuelve y provisiona
de forma segura cada base, además de una identidad autorizada para validar el
recorrido visual sin omitir middleware.

## Responsabilidad y revisión pendiente

Madelin Cerón es la autora y responsable de las correcciones documentales y
del seguimiento de REV-01 a REV-04. Yamilet1235 realizó la revisión del PR
#1073 y solicitó cambios; no se registra aprobación. La moderación, la
corrección técnica definitiva de REV-01 y la verificación independiente
siguen pendientes de asignación autorizada, según el plan y los hallazgos.
REV-01 permanece abierto: #1154 es una contención temporal, no la solución
definitiva del aislamiento multi-hospital. Se requiere una nueva revisión y
la aprobación final de una persona distinta de Yamilet1235, sin atribuirla
a ningún integrante de antemano. Refs #11.
