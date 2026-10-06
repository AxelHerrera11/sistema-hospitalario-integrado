## Resumen

<!-- Qué cambia y por qué, en 2-4 líneas. -->

**Área:** <!-- p. ej. 7 · Laboratorio -->
**Issue relacionado:** Closes #<!-- número -->

## Tipo de cambio

- [ ] Documentación (análisis, RF/RNF, diseño, contrato API)
- [ ] Backend
- [ ] Frontend
- [ ] Pruebas
- [ ] Corrección de error / seguridad

## Checklist

- [ ] Rama creada desde `develop` con la convención `feature/<area>-<descripcion>`.
- [ ] Solo toca archivos de mi área; los compartidos (`routes/api.php`, `RoleSeeder`, `router/index.js`, `AppLayout.vue`) en bloques pequeños.
- [ ] Respuestas y errores siguen [`docs/contrato-api.md`](../docs/contrato-api.md).
- [ ] Rutas con `['tenant', 'auth.jwt']` + `permission:modulo.accion`.
- [ ] Modelos con `tenant_id` usan `BelongsToTenant`; no se filtra `tenant_id` a mano ni se usa `withoutGlobalScope('tenant')`.
- [ ] Acciones sobre datos clínicos registradas en `audit_logs` (cuando aplique).
- [ ] Documentación del módulo actualizada en `docs/modulo-<area>.md`.

## Evidencia de pruebas

<!-- El CI corre todo esto en cada PR; pega aquí el resumen si probaste en local. -->

- [ ] `php artisan test` (SQLite): <!-- p. ej. 69 passed, 2 skipped -->
- [ ] `php artisan test --configuration=phpunit.pgsql.xml` (PostgreSQL): <!-- resultado -->
- [ ] `npm run build`: <!-- correcto / no aplica -->

## Capturas o demo

<!-- Obligatorio si el PR cambia la interfaz. -->

## Pendiente para próximos PRs

<!-- Lo que queda fuera de este PR. -->
