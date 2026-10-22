# Evidencia de persistencia del mismo micro PHP

## Propósito

La matriz exige SQL preparado y pruebas de éxito, dominio y error de persistencia.
El runner InMemory permite reproducirlos rápidamente, pero no prueba bloqueos ni rollback de PostgreSQL.
tests/postgres.php complementa esa evidencia sin incorporar Laravel al micro.

## Preparación y ejecución

Usar PostgreSQL en una base terminada en _test. El puerto de la revisión es 55433.
Variables opcionales: RECEPTION_TEST_DATABASE, RECEPTION_TEST_PORT, RECEPTION_TEST_USERNAME y RECEPTION_TEST_PASSWORD.
Ejecutar php micro-his/recepcion-muestras/tests/postgres.php desde la raíz.
La prueba crea un esquema rx_micro_ aleatorio, carga el schema.sql del micro y prepara tres órdenes ficticias.
Al terminar elimina exclusivamente ese esquema, también cuando ocurre una excepción.

## Casos ejecutados

| Caso | Acción | Comprobación |
|---|---|---|
| P01 | Recibir muestra conforme contra I1 | Registro accepted y código. |
| P02 | Leer por código desde el mismo tenant | Mismo ID persistido. |
| P03 | Leer el código desde H2 | Ausencia de resultado. |
| P04 | Recibir I2 con volumen insuficiente | rejected y code=null. |
| P05 | Inspeccionar outbox | Un aviso de rechazo persistido. |
| P06 | Eliminar outbox de prueba y recibir I3 inválida | Falla real de SQL y ninguna muestra I3 sobreviviente. |
| P07 | Leer estado de I3 tras fallo | Sigue pending; no queda cambio parcial. |

[Salida literal de siete comprobaciones](../evidencias/micro-postgres.txt).
La eliminación del outbox ocurre solo dentro del esquema desechable para inducir un error real.
No es un procedimiento de mantenimiento ni una instrucción de producción.

## Correspondencia con código y continuidad

Se prueban el mismo ReceiveSpecimen y PdoReceptionRepository de semanas 3/4.
No se reemplazan por la implementación del HIS, cuyo esquema agrega idempotencia e inmutabilidad.
El prototipo HTTP de semana 5 usa estas mismas clases y se prueba con un servidor PHP temporal.
La arquitectura es verificable: el controlador no ejecuta SQL y el servicio depende de ReceptionRepository.

## Conclusión

La prueba aporta evidencia de PostgreSQL real, con separación explícita de los dobles de memoria.
No se atribuyen porcentajes de cobertura o rendimiento a siete comprobaciones de persistencia.
