# Evidencia de integración — Parte 2

| Superficie | Estado | Evidencia |
|---|---|---|
| GET / POST / PATCH Laravel | Integradas previamente en Parte 1 | Rutas registradas en `PatientsServiceProvider` |
| Vue Pacientes | Conectado en código | Usa cliente Axios común para las tres operaciones |
| Permisos UI | Efectivos en el payload de login | `can()` muestra/oculta acciones; el servidor decide finalmente |
| PostgreSQL hospital | No disponible | Puerto de test 55433 no acepta TCP; `ConnectionIsolationTest` falla conectando a central 55431 |
| Tenant / JWT real | Pendiente | Los tests HTTP actuales usan dobles; DB de identidad no accesible |
| RBAC | Lógica comprobable, recorrido real pendiente | `PatientPermissionsTest` ejecuta `RequirePermission` y matriz con store en memoria |
| Extremo a extremo | No completado | Sin PostgreSQL y credenciales/sesión de prueba no se ejecutó login→persistencia |

## Comandos y resultados

- `php artisan test tests/Database/ConnectionIsolationTest.php` — bloqueado:
  conexión rechazada a `127.0.0.1:55431`; no hubo conexión PostgreSQL.
- `Test-NetConnection 127.0.0.1 -Port 55433` — `False` (puerto hospital de
  pruebas cerrado).
- `php artisan test tests/Feature/Patients/PatientApiTest.php` — 10 pruebas,
  47 aserciones, sin fallos; warnings por `.env` ausente.
- `php artisan test tests/Unit/Patients/PatientServiceTest.php` — 5 pruebas,
  10 aserciones, PASS.
- `php micro-his/pacientes/tests/run.php` — PASS.
- `php artisan test tests/Feature/Rbac/PatientPermissionsTest.php` — 131
  aserciones, sin fallos; política RBAC real con store/identidad de prueba.
- `php artisan route:list --path=api` — PASS; rutas GET/POST/PATCH presentes.
- `npm run build` — PASS; Vite compiló la página Pacientes.
- `node --test resources/js/router/permissions.test.mjs` — PASS.
- No se ejecutaron INSERT/SELECT/UPDATE ni migraciones; no se usaron datos reales.
- No se modificó `.env` ni se imprimieron credenciales.

Pendiente: PostgreSQL real (constraints y persistencia), middleware Tenant/JWT
con datos de prueba y recorrido autenticado completo.
