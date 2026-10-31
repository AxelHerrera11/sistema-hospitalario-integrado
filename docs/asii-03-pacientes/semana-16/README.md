# ASII-03 Pacientes — Semana 16

## Objetivo

Validar la persistencia PostgreSQL real de Pacientes y avanzar el cierre
extremo a extremo sin reimplementar las operaciones entregadas en Semana 15.

## Alcance y estado final

Se aplicaron las migraciones oficiales a las dos bases de prueba vacías
indicadas por `phpunit.xml`. La nueva prueba de integración confirma INSERT,
SELECT, UPDATE y los dos conflictos UNIQUE directamente contra PostgreSQL.
También comprobó que el TenantMiddleware real rechaza una cabecera faltante y
un hospital no registrado.

**Estado:** Pacientes implementado, pendiente de validación final de login/JWT,
autorización real y recorrido autenticado desde Vue.

## Componentes comprobados

- CENTRAL y HOSPITAL conectan a PostgreSQL 16 y reportan migraciones aplicadas.
- HOSPITAL contiene `local_patients`; inserción, lectura, actualización y
  conflictos de `code` y `global_id` se validan dentro de transacciones
  revertidas.
- TenantMiddleware real consulta CENTRAL y rechaza contextos ausentes o
  desconocidos.
- Pruebas API, dominio y RBAC de prueba; build Vue y test de permisos del
  router.

## Limitaciones

Las bases tienen esquema pero ningún hospital, usuario ni membresía de prueba.
El comando oficial de primera cuenta requiere un hospital activo, y no hay
seeders de identidad; por instrucción no se inventaron usuarios ni tokens.
Login/JWT, autorización con identidad real, tenant autorizado, API Pacientes
contra PostgreSQL y recorrido visual autenticado siguen pendientes. La
conexión hospitalaria es fija; no se demostró aislamiento físico por tenant.

Ver resultados y comandos en
[evidencia de cierre funcional](./evidencia-cierre-funcional.md).
