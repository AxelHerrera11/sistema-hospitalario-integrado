# ASII-06 — Semana 8: flujo UX del catálogo

## Alcance académico identificado

El issue maestro #11 y la documentación específica de ASII-06 no publican un
enunciado de Semana 8, ni criterios de aceptación, pruebas o evidencias
semanales obligatorias. El template general del repositorio
`.github/ISSUE_TEMPLATE/module_task.md` identifica la actividad como
**“Semana 8: flujo UX”**. Se toma ese objetivo general como alcance; el
recorrido definido aquí es una propuesta UX específica del catálogo, no una
instrucción académica adicional ni una pantalla implementada.

El issue #11 sí define objetivos y Definition of Done generales del módulo
(análisis/diseño, contrato API y UI si aplica, validaciones, pruebas, evidencia
y riesgos), pero no asigna esos puntos específicamente a Semana 8. Los RF/RNF
y criterios existentes están en Semana 2 y se enfocan en creación e integridad
de wards/beds; no se crean requisitos funcionales nuevos en esta entrega. La
actividad general tampoco define casos de prueba semanales; se ejecutan
pruebas existentes como regresión del contrato que sustenta el flujo.

## Entrega

[Flujo UX](./flujo-ux.md) documenta el recorrido de consulta de wards y camas
con las operaciones ya disponibles:

- `GET /api/v1/wards`
- `GET /api/v1/wards/{ward}/beds`

Incluye acceso autorizado, carga, resultados, listas vacías y errores. No
agrega endpoints, componentes frontend, ni acciones de creación, edición,
ocupación o asignación.

## Reglas visibles en el flujo

La autorización efectiva se valida en servidor con `camas.ver`, después de
`api`, `tenant` y `jwt.auth`. La configuración actual concede este permiso a
los roles `superadmin` y `recepcion`; la interfaz no debe inferir permisos por
nombre de rol ni sustituir la comprobación del servidor.

El recorrido solo presenta datos de `wards` y `beds`. No ofrece acciones sobre
pacientes, estancias o estados operativos. Los errores y estados reflejan los
contratos existentes de las rutas, no funcionalidades futuras.

## Dependencia y limitaciones

La rama parte de `ASII06_SEMANA08_BASE`
(`728762423a7fd26c2913e49438909d3da4d8ebbe`), commit de Semana 7 asociado al
PR #1041. No reconstruye el trabajo de Semanas 6–7. El flujo es diseño, no
implementación visual; la conexión `hospital` continúa fija y no existe
aislamiento multi-tenant completo.

Ver [evidencia de integración y validación](./evidencia-integracion.md).
