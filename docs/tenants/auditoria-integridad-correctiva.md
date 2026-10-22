# Auditoría CENTRAL: corrección posterior a #507

La migración `2026_08_11_001000_require_audit_log_hashes` añade NOT NULL y
formato SHA-256 hexadecimal minúsculo en ambos hashes. Reserva `000900`
para los índices de #647. No renumera migraciones ni modifica eventos.

Antes de instalar las restricciones, bloquea escrituras y revisa todas las filas.
Si encuentra hashes nulos o inválidos, aborta la transacción sin rellenarlos:
se necesita una resolución explícita de gobernanza sobre esos registros.

## Reversión

Revertir la correctiva retira únicamente sus restricciones de hash;
conserva la PK, los eventos y el trigger append-only de #507.
El down del enriquecimiento se corrige para que nunca deje una tabla mutable:
con eventos, rechaza la reversión; vacía, elimina tabla y estado de cadena.
El bloqueo exclusivo evita inserciones entre la comprobación y la eliminación.
Reaplicar el enriquecimiento reconstruye la tabla vacía y sus protecciones.
La migración inicial tolera que la tabla ya se haya eliminado en ese rollback.
Los up de bases ya instaladas conservan su comportamiento y numeración.

## Contrato conservado y decisiones pendientes

El escritor mantiene una cadena global (`chain_id = 1`) con `FOR UPDATE`
dentro de la misma transacción: los hospitales comparten el bloqueo.
La prueba usa dos procesos PostgreSQL y el repositorio real; observa el bloqueo
antes de confirmar el primer escritor y verifica el enlace y la cabeza final.
No se garantiza el encadenamiento de escritores SQL que eludan ese protocolo.

Se conserva `text` para actor/hospital porque existen `system` y `unscoped`.
Pasar a UUID o a cadenas por hospital requiere acordar esos casos globales.
La ampliación de CENTRAL y la relación entre `audit_log`, `audit_logs` y
`local_access_audits` siguen pendientes de acuerdo del grupo; este cambio
no constituye aprobación ni crea otra bitácora.

Validación: `php vendor/phpunit/phpunit/phpunit tests/Database/Central/AuditChainIntegrityTest.php`.
La prueba de sincronización conserva el rollback dirigido a su archivo;
no se introduce un conteo fijo de migraciones como `--step 5`.
