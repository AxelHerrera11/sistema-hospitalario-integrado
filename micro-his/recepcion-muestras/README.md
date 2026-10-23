# Micro-HIS Recepción de muestras

PHP 8.2+ vanilla. Una sola fuente de dominio compartida con Laravel mediante app/Modules/Reception/bootstrap.php.

- Presentation/ReceptionController valida y traduce errores; no contiene SQL ni reglas clínicas.
- Application/ReceiveSpecimen depende de ReceptionRepository y OrderGateway.
- Domain contiene código verificable, UUID, errores y validadores intercambiables.
- Persistence implementa InMemory (doble) y PDO con consultas preparadas; specimen y evento se guardan juntos.

Ejecutar `php micro-his/recepcion-muestras/tests/run.php` y `php micro-his/recepcion-muestras/tests/lsp.php` desde la raíz. Las pruebas reales PostgreSQL se agregan en el complemento S03, PR #840.

schema.sql pertenece exclusivamente a un ejercicio aislado (his_reception_test), no reemplaza las migraciones Laravel ni crea tablas de órdenes. Usa specimens, outbox_events, UUID y numeric, sin tenant_id. Las referencias UUID locales son lógicas en este ejercicio; las FK RESTRICT clínicas se prueban en la migración 000601 de S05.

La conexión PDO debe pertenecer al hospital autenticado: no se acepta un DSN ni credenciales desde el payload. La API real usa la conexión hospital seleccionada por tenant/JWT. Órdenes reales y su búsqueda quedan indisponibles/PROPUESTAS hasta que ASII-16 publique su contrato; las pruebas utilizan un doble de OrderGateway.
