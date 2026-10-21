# Declaración de uso de inteligencia artificial

**Herramienta:** OpenCode con el modelo MiniMax M3.1 Flash.

**Propósito:** redactar el contrato de la API de órdenes de laboratorio a partir
del plan transversal de integración recibido el 18 de octubre de 2026, de la
documentación de los módulos de laboratorio ya integrada en `develop`
(catálogo de pruebas, recepción de muestras, ingreso y validación de
resultados) y de las especificaciones del esquema federado en `openspec/`.

**Prompt relevante:** «Órdenes de laboratorio: contrato el martes 20 y PR
integrado el miércoles 21; María y Gerson dependen de este módulo», dentro del
plan transversal que asigna a Javier la coordinación de contratos.

**Contenido aceptado o modificado:** la estructura de endpoints, idempotencia,
códigos de error y tabla de propiedad de entidades son **propuestas** para
validar con el equipo. Se alinearon los nombres de entidad y tabla
(`lab_order`/`lab_orders`, `lab_test`/`lab_tests`, `specimen`/`specimens`,
`lab_result_version`/`lab_result_versions`) con la especificación H6/H7 de
`openspec/changes/postgresql-federated-schema-rebuild/` y con las migraciones
HOSPITAL existentes en `develop`. Los permisos `laboratorio.ordenes.*` son
propuestos porque el RBAC común todavía no los define.

**Validación humana:** pendiente. Javier Alexander Fajardo López debe revisar
el contrato y esta declaración, y ASII-16 debe confirmar la propiedad del
módulo, antes de que el PR se integre a `develop`.
