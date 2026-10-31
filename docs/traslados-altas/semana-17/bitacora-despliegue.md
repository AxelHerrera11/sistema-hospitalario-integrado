# Bitácora de despliegue - Semana 17

## Evidencia ejecutada el 21/10/2026

Entorno local de verificación, no staging: Windows 11, PHP 8.2.12 y Vite 7.3.2. La rama S17 solo agrega pruebas y documentación; por eso se reutiliza la evidencia PostgreSQL 17.10 de S16 para el flujo que no cambió.

| Comprobación | Resultado |
|---|---|
| `php artisan route:list` para traslados, altas y estancias | 3 rutas publicadas |
| `php artisan test tests/Feature/TrasladosAltas/TrasladosAltasApiTest.php` | 5 pruebas, 44 aserciones; advertencia porque el clon no tiene `.env` |
| `php micro-his/traslados-altas/tests/run.php` | 101/101 |
| `node --test resources/js/modules/traslados/traslados.test.mjs` | 3/3 |
| `npm run build` | 109 módulos transformados |
| S16: API PostgreSQL y recorrido HTTP | 3/3 y 11/11, datos ficticios |
| S16: recorrido de la SPA | 1/1 con permisos pendientes y 5/5 con activación temporal |

La prueba nueva ejecuta `APP_DEBUG=false` y confirma que el 503 no contiene `SQLSTATE` ni el texto secreto de la excepción. También exige `Cache-Control: no-store, private`, `Retry-After` y una traza generada cuando la cabecera no es confiable.

## Procedimiento preparado

1. Fijar el SHA aprobado y ejecutar `composer install --no-interaction --prefer-dist --optimize-autoloader` y `npm ci && npm run build`.
2. Proveer fuera del repositorio `APP_KEY`, `JWT_SECRET`, `CENTRAL_DB_*`, la conexión HOSPITAL del tenant y `HOSPITAL_DB_TENANT_ID` (sin él, 503); usar `APP_DEBUG=false`.
3. Ejecutar las migraciones CENTRAL y HOSPITAL con respaldo y una base explícitamente seleccionada; esta entrega no agrega migraciones.
4. Ejecutar las suites Feature, Database y del micro-HIS sin usar `migrate:fresh` sobre datos compartidos.
5. Confirmar los tres permisos activos, la referencia local del usuario y las respuestas 401, 403, 409, 503, traslado 201 y alta 200.
6. Verificar en PostgreSQL que el traslado conserva el episodio y crea estancias contiguas; el alta usa el mismo T para estancia y episodio; `beds.status` no cambia por liberar ocupación.

Reversión: revertir el PR de la entrega y limpiar la caché de configuración. No hay migración S17 que revertir. Un resultado incierto durante COMMIT se consulta antes de cualquier reintento.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Acción verificable |
|---|---|---|
| Permisos en `rbac.pending` | RBAC | Aprobar/rechazar la propuesta y probar la matriz integrada |
| `local_user_refs` sembradas manualmente | ASII-01 / identidad | Sincronizar membresías CENTRAL por hospital |
| Una conexión HOSPITAL para todos los tenants | Plataforma/Tenants | Resolver el endpoint clínico y probar aislamiento físico |
| Sin staging/on-premise autorizado | Coordinación del proyecto | Proveer entorno, secretos y ventana de despliegue |
| Sin límite de tasa comprobado | API Gateway | Definir política y prueba para `/api/v1` |
