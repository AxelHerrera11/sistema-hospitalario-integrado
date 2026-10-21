# Declaración de uso de IA — Contrato del slice H8

- **Herramienta:** OpenCode (modelo `minimax-m3.1-flash-free`), por indicación expresa de Javier
  Fajardo López, para redactar el contrato del slice H8 a partir de la migración y sus pruebas.
- **Propósito:** documentar las tres tablas del slice H8 (`local_access_audits`, `outbox_events`,
  `inbox_messages`), su uso desde el módulo de signos vitales y alertas críticas, y los acuerdos
  pendientes del grupo.
- **Contenido aceptado:** la estructura del documento (estado, tablas, uso por módulo, respuestas a
  los tres puntos, SQLSTATEs, verificación y acuerdos pendientes) y el ejemplo de inserción en una
  sola transacción.
- **Contenido verificado con comandos reales:** la definición de las tablas y sus disparadores, y
  los resultados `OK (14 tests, 122 assertions)` y `OK (73 tests, 860 assertions)` sobre
  PostgreSQL 16. El diseño de la auditoría local es el Anexo A publicado en el PR #376.
- **Verificación humana pendiente:** Javier revisa esta declaración y el contrato antes de
  publicarlo, y ASII-13 y ASII-21 confirman los nombres de evento y el canal de publicación.
- **Datos usados:** únicamente identificadores ficticios de prueba; no hay datos de pacientes,
  credenciales ni tokens.
