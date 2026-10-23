# Semana 4 — MVC y Repository sobre el mismo micro

ASII-18, Mar-03; reasignación del grupo, issue #6. Se continúa el mismo micro PHP de S03 según la matriz del [README](../../../README.md), sin copiar otro dominio ni introducir un framework.

## Responsabilidades implementadas

| Componente | Responsabilidad |
|---|---|
| demo.php | Composición de dependencias, fixture explícito y selección InMemory/PDO. |
| ReceptionController | Validar petición y traducir resultados/errores; sin SQL ni reglas clínicas. |
| ReceiveSpecimen | Identidad, reglas, idempotencia y reintentos sobre puertos. |
| ReceptionRepository | Contrato de transacción, escritura y consulta por solicitud/código. |
| InMemoryReceptionRepository | Doble aislado para reglas y fallos de escritura. |
| PdoReceptionRepository | SQL preparado, UNIQUE y espécimen/evento atómicos. |
| JsonView | Vista JSON pública: sin request_hash, actor ni referencias internas. |

`php micro-his/recepcion-muestras/demo.php` usa memoria. `php micro-his/recepcion-muestras/demo.php --pdo` usa PostgreSQL real en his_reception_test (RECEPTION_TEST_PORT=55433) y un esquema temporal que se elimina al terminar. Ambos conservan controlador, servicio, dominio y vista; solo cambia el Repository. Las órdenes son un doble explícito.

## Cliente-servidor e integración

El proceso PHP es cliente de PostgreSQL mediante PDO; el servidor conserva unicidad y atomicidad. El cliente HTTP se incorpora en #841. Compartir la base clínica permite referencias locales y transacciones con outbox_events, pero acopla despliegues al esquema. Cada módulo conserva su propiedad: recepción escribe specimens; ASII-16 conserva las órdenes. No se copia una tabla reception_order_items ni se modifica una orden externa.

Del respaldo de S04 se reutilizó el análisis conceptual de responsabilidades; no se copió microproject, sus DTO duplicados, composer.json ni phpunit.xml.dist. El mapa de componentes se corrigió para coincidir con las clases que existen.

[Fuente MVC](./fuentes/mvc.mmd) · [Diagrama](./mvc.svg) · [Evidencia](./EVIDENCIA.md) · [IA](./DECLARACION_IA.md).

SD-REC-01: fixtures y umbrales requieren validación clínica. El esquema de la demo es didáctico y no sustituye las FK clínicas de S05 ni acredita una integración externa real.
