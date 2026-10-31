# Semana 3 — Micro monolito PHP por capas

ASII-18, Mar-03; reasignación del grupo, issue #6. Objetivo: ejecutar recepción e identificación con Presentation, Application, Domain y Persistence según la matriz del [README](../../../README.md).

[Micro ejecutable y comandos](../../../micro-his/recepcion-muestras/README.md). El controlador valida antes del servicio; ReceiveSpecimen usa puertos; PDO prepara sus consultas y la transacción guarda specimen y evento juntos. InMemory permite probar fallos y rollback sin infraestructura.

[Fuentes](./fuentes/capas.mmd) · [SVG](./capas.svg) · [Evidencia](./EVIDENCIA.md) · [IA](./DECLARACION_IA.md).

schema.sql es solo un ejercicio aislado; no sustituye 000601 ni crea tablas de órdenes. PostgreSQL real se prueba en #840; las FK locales clínicas corresponden a S05. SD-REC-01: umbrales e identificación requieren validación clínica. No se afirma cobertura ni concurrencia a partir de generar códigos aleatorios.

## Complemento PostgreSQL — PR #840

[Persistencia verificada y límites](./persistencia-verificada.md): usa RECEPTION_TEST_PORT=55433 y la base his_reception_test; las órdenes se sustituyen por un doble y no se crean tablas de ASII-16.
