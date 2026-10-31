# Bitacora de despliegue - Reportes operativos (Semana 17)

## Evidencia ejecutada

Entorno local de verificacion, no staging: Windows 11, PHP 8.2.33, PHPUnit 11.5.55. La rama S17 solo
agrega pruebas y documentacion; la implementacion es la de S16 y no cambio.

| Comprobacion | Resultado |
|---|---|
| `php -l tests/Feature/ReportesOperativos/ReportesSeguridadTest.php` | Sin errores de sintaxis |
| `php vendor/bin/phpunit tests/Feature/ReportesOperativos --testdox` | OK: 10 pruebas, 85 aserciones |
| Pruebas nuevas de seguridad | 6: matriz, exportacion, tenant, base sin configurar, datos internos, sin sesion |
| S16: rutas, middlewares y matriz RBAC | Reales; fuente HOSPITAL en memoria |

## Procedimiento preparado

1. Fijar el SHA aprobado y ejecutar `composer install --no-interaction --prefer-dist --optimize-autoloader`.
2. Proveer fuera del repositorio `APP_KEY`, `JWT_SECRET`, la conexion HOSPITAL del tenant y
   `HOSPITAL_DB_TENANT_ID`; usar `APP_DEBUG=false` y HTTPS.
3. Ejecutar `php artisan config:cache` y `php artisan route:cache`; reiniciar workers persistentes.
4. Ejecutar `php vendor/bin/phpunit tests/Feature/ReportesOperativos` contra la base HOSPITAL autorizada.
5. Confirmar 401 sin JWT, 403 sin permiso o con tenant distinto, 503 sin `HOSPITAL_DB_TENANT_ID`, 404
   para reporte inexistente, 406 para formato no soportado, CSV con apostrofo ante formulas y
   `Cache-Control: no-store, private` en JSON, CSV y error.
6. Registrar commit, hora, responsable, comandos y resultados antes de declarar desplegado.

Reversion: revertir el PR de la entrega y limpiar la cache de configuracion y rutas. No hay migracion
S17 que revertir.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Accion verificable |
|---|---|---|
| Sin PostgreSQL/HOSPITAL real en este equipo | Plataforma / coordinacion | Proveer base y cadena de conexion |
| Sin staging u on-premise autorizado | Coordinacion del proyecto | Proveer entorno, secretos y ventana |
| `HOSPITAL_DB_TENANT_ID` no definido fuera del repo | Plataforma | Definir el valor por entorno |
| Sin limite de tasa comprobado | API Gateway | Definir politica y prueba para `/api/v1` |
