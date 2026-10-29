# ASII-23 Semana 17 — Seguridad y despliegue del módulo

**Issue:** `Refs #152`. Pruebas de seguridad reales ya integradas y la guía
de instalación para el demo/despliegue local.

## Controles de seguridad implementados y probados

| Control | Implementación | Prueba |
|---|---|---|
| Autenticación obligatoria (JWT) | `jwt.auth` en la ruta | `IndicatorEndpointTest`: 401 `AUTH_REQUIRED` |
| Autorización por permiso del servidor | `rbac:reportes.ver` (`config/rbac.php`: superadmin/admin_hospital/medico/tecnico_laboratorio/bioquimico lo traen) | 403 `RBAC_DENIED` |
| Aislamiento tenant | `X-Tenant-ID` obligatorio + hospital activo | `TENANT_MISMATCH` con `X-Tenant-ID: hospital-b` |
| Inyección SQL imposible | Params preparados en el servidor PostgreSQL, sin concatenación | SQL canónico en `PdoEpisodeSummaryReader` |
| Sin datos personales | Consulta `count+SUM`; respuesta solo agregados | `IndicatorHospitalIntegrationTest` (no-leak) |
| Fallo honesto | Base caída → 503 sin cifras (no 500, no 0 %) | base inexistente → `REPORTES_UNAVAILABLE` |
| Sin caché compartida | `Cache-Control: no-store, private` en toda respuesta | header asertado en tests |
| Sin detalles internos | Códigos de error cerrados, sin texto de excepción | validado por tests 422/503 |

## Despliegue local (demo manual)

1. PostgreSQL 15+: crear dos bases (CENTRAL y HOSPITAL); en HOSPITAL:
   `CREATE EXTENSION btree_gist;`
2. `cp .env.example .env` (y ajustar `CENTRAL_DB_*`, `HOSPITAL_DB_*`,
   `APP_KEY` con `php artisan key:generate`).
3. `composer migrate:central`, `composer migrate:framework`,
   `composer migrate:hospital` (en ese orden).
4. Provisionar la cuenta: `php artisan auth:provision-first-superadmin`
   (contraseña: mínimo 12 caracteres con mayúscula, minúscula, número y
   símbolo).
5. `php artisan serve` y `npm run dev`.
6. Demo de la ruta del permiso: el rol `superadmin` ya trae
   `reportes.ver`; ninguna otra acción especial es necesaria.

**Nota:** el provisionador crea la cuenta en la base CENTRAL; el
`hospital_memberships` que registra queda activo para el hospital
indicado durante el comando.
