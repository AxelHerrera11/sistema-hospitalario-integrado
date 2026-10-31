# Bitacora de despliegue - Citas (Semana 17)

## Evidencia ejecutada

Entorno local de verificacion, no staging: Windows 11, PHP 8.2.33, PHPUnit 11.5.55. La rama S17 solo
agrega pruebas y documentacion; la implementacion es la de S16 y no cambio.

| Comprobacion | Resultado |
|---|---|
| `php -l tests/Feature/Citas/CitasSeguridadTest.php` | Sin errores de sintaxis |
| `php vendor/bin/phpunit tests/Feature/Citas --testdox` | OK: 10 pruebas, 72 aserciones |
| Pruebas nuevas de seguridad | 3: matriz de permisos, aislamiento de tenant, datos internos |
| S16: rutas, middlewares y matriz RBAC | Reales; repositorio HOSPITAL en memoria |

## Procedimiento preparado

1. Fijar el SHA aprobado y ejecutar `composer install --no-interaction --prefer-dist --optimize-autoloader`.
2. Proveer fuera del repositorio `APP_KEY`, `JWT_SECRET`, la conexion HOSPITAL del tenant y
   `HOSPITAL_DB_TENANT_ID`; usar `APP_DEBUG=false` y HTTPS.
3. Ejecutar `php artisan config:cache` y `php artisan route:cache`; reiniciar workers persistentes.
4. Ejecutar `php vendor/bin/phpunit tests/Feature/Citas` contra la base HOSPITAL autorizada.
5. Confirmar 401 sin JWT, 403 con rol sin permiso, 403 por tenant distinto, 409 por solape y 405 al
   intentar borrar. Verificar `Cache-Control: no-store, private` en exito y error.
6. Registrar commit, hora, responsable, comandos y resultados antes de declarar desplegado.

Reversion: revertir el PR de la entrega y limpiar la cache de configuracion y rutas. No hay migracion
S17 que revertir.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Accion verificable |
|---|---|---|
| Sin PostgreSQL/HOSPITAL real en este equipo | Plataforma / coordinacion | Proveer base y cadena de conexion |
| Sin staging u on-premise autorizado | Coordinacion del proyecto | Proveer entorno, secretos y ventana |
| Zona horaria de la conexion `hospital` | Plataforma | Fijar `timezone` y probar fechas |
| Sin limite de tasa comprobado | API Gateway | Definir politica y prueba para `/api/v1` |
