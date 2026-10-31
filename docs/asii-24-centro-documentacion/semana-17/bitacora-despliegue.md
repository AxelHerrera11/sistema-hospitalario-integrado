# Bitacora de despliegue - Centro de documentacion (Semana 17)

## Evidencia ejecutada

Entorno local de verificacion, no staging: Windows 11, PHP 8.2.33, PHPUnit 11.5.55. La rama S17 solo
agrega pruebas y documentacion; la implementacion es la de S16 y no cambio.

| Comprobacion | Resultado |
|---|---|
| `php -l tests/Feature/CentroDocumentacion/DocumentacionSeguridadTest.php` | Sin errores de sintaxis |
| `php vendor/bin/phpunit tests/Feature/CentroDocumentacion --testdox` | OK: 10 pruebas, 57 aserciones |
| Pruebas nuevas de seguridad | 4: matriz, tenant, rutas internas, sin sesion |
| S16: rutas, middlewares y matriz RBAC | Reales; catalogo en configuracion |

## Procedimiento preparado

1. Fijar el SHA aprobado y ejecutar `composer install --no-interaction --prefer-dist --optimize-autoloader`.
2. Proveer fuera del repositorio `APP_KEY`, `JWT_SECRET` y la conexion HOSPITAL del tenant; usar
   `APP_DEBUG=false` y HTTPS.
3. Ejecutar `php artisan config:cache` y `php artisan route:cache`; reiniciar workers persistentes.
4. Ejecutar `php vendor/bin/phpunit tests/Feature/CentroDocumentacion` en el entorno autorizado.
5. Confirmar 401 sin JWT, 403 por rol sin permiso y por tenant distinto, 404 para documento ajeno, y
   `Cache-Control: no-store, private` en catalogo, contenido y error.
6. Registrar commit, hora, responsable, comandos y resultados antes de declarar desplegado.

Reversion: revertir el PR de la entrega y limpiar la cache de configuracion y rutas. No hay migracion
S17 que revertir.

## Bloqueos del despliegue final

| Bloqueo | Responsable | Accion verificable |
|---|---|---|
| Sin staging u on-premise autorizado | Coordinacion del proyecto | Proveer entorno, secretos y ventana |
| Permiso de publicacion sin aprobar | RBAC (ASII-02) | Proponer y aprobar el permiso |
| Sin limite de tasa comprobado | API Gateway | Definir politica y prueba para `/api/v1` |
