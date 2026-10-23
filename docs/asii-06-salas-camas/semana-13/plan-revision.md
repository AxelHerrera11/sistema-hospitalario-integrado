# Plan de revisión técnica — ASII-06

## Objetivo

Determinar qué está realmente implementado y probado en el catálogo de wards y
camas, comparar frontend, contrato API, persistencia, tenant/RBAC y
documentación, y registrar riesgos verificables sin asumir funciones
pendientes ni cambiar controles de seguridad.

## Alcance revisado

- Prototipo `resources/js/modules/salas-camas/pages/WardCatalogPage.vue`,
  rutas, navegación, permisos efectivos y Axios compartido.
- `WardCatalogController`, `SalasCamasServiceProvider`, `CatalogService`,
  repositorios y adaptadores PDO.
- Respuestas, campos, estados, filtrado y middleware de ambas rutas GET.
- `TenantMiddleware`, `JwtAuth`, `RequirePermission`, proveedor de permisos,
  membresías y configuración RBAC.
- Tests frontend, Feature, identidad, integración PostgreSQL y micro-HIS.
- Documentación de ASII-06 de Semanas 1–12, issue #11, plantilla académica y
  Semana 13 de ASII-03 como referencia metodológica.

## Fuera de alcance

No se hizo prueba de penetración, revisión de configuración productiva,
lectura de `.env`/secretos, consulta de datos reales de hospitales o usuarios,
login manual, inspección visual en navegador ni evaluación WCAG. No se cambió
la autorización, tenant, persistencia, migraciones o lógica clínica.

## Método

1. Confirmar rama y estado del worktree.
2. Identificar el alcance de Semana 13 en la plantilla y issue.
3. Seguir el flujo HTTP y Vue desde la ruta hasta el repositorio.
4. Contrastar campos y errores con pruebas y documentación.
5. Revisar la frontera de tenant/JWT/RBAC y persistencia real.
6. Ejecutar las validaciones disponibles y clasificar su alcance.
7. Registrar hallazgos accionables y distinguir hechos de riesgos pendientes.

## Criterios de salida

La revisión es defendible si distingue implementado, probado y pendiente;
proporciona evidencia por criterio; no presenta tests aislados como E2E; y no
declara aislamiento multi-tenant completo mientras la conexión del catálogo
sea fija y sus tablas carezcan de `tenant_id`.

## Participación

Revisión técnica realizada en esta tarea como apoyo al módulo. Los equipos de
identidad/tenant, persistencia y responsable funcional son dependencias
sugeridas para atender los riesgos; no se atribuyen aprobaciones ni
responsabilidades confirmadas.
