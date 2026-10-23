# Evidencia de integración — Semana 7

`GET /api/v1/wards/{ward}/beds` usa `camas.ver` y middleware `api`, `tenant`,
`jwt.auth`, `rbac:camas.ver`. Cada elemento solo expone `bed_id`, `ward_id`,
`code`, `type` y `status`.

```json
{
  "data": [{"bed_id": "uuid-cama", "ward_id": "uuid-ward", "code": "UCI-01", "type": "standard", "status": "available"}],
  "meta": {"ward_id": "uuid-ward", "count": 1, "request_id": "req-ejemplo"}
}
```

Ward existente sin camas: `200`, lista vacía. UUID inválido/ward ausente:
`404 WARD_NOT_FOUND`. Error de persistencia: `503 BED_CATALOG_UNAVAILABLE`,
sin información interna. La consulta PDO filtra por `beds.ward_id`.

## Validaciones

- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — PASS: 11 tests, 48 assertions (incluye filtro entre wards, autorización,
  ward sin camas e inexistente).
- `php artisan test tests/Feature/SalasCamas/WardCatalogEndpointTest.php`
  — 11 pruebas, 48 assertions; wrapper reportó advertencias
  `file_get_contents(...)`, sin fallos. PHPUnit directo PASS.
- `php micro-his/salas-camas/tests/run.php` — PASS: 4 escenarios.
- `php artisan route:list --path=api` — PASS; registradas ambas rutas GET.
- `php -l` en los PHP modificados — sin errores; `git diff --check` — PASS.
- `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php`
  — no pudo conectar a `127.0.0.1:55433` (`Connection refused`); cero
  aserciones. Integración PostgreSQL queda pendiente; prueba configurada con
  wards/camas de prueba, rollback y verificación de ausencia de residuos.

No se ejecutó migración, `migrate:fresh` ni se modificó `.env`.

## Limitaciones y dependencia

`TenantMiddleware` valida el hospital, pero `hospital` sigue siendo conexión
fija. Las tablas carecen de `tenant_id` y no hay selección dinámica por tenant;
no existe aislamiento multi-tenant completo. La rama parte del commit
`9f25214e0b997af7b0459abfeb290fcace1594bb` con Semana 6, cuyo PR sigue pendiente
de aprobación. Falta habilitar PostgreSQL test para validar el adaptador PDO.
