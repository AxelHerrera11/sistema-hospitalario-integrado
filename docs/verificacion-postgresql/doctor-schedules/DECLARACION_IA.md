# Declaración de uso de IA — Acuerdos de `doctor_schedules` e inventario del esquema

- **Herramienta:** OpenCode (modelo `minimax-m3.1-flash-free`), por indicación expresa de Javier
  Fajardo López, para redactar la ficha de acuerdos y verificar el inventario real de tablas.
- **Propósito:** registrar los acuerdos pendientes con responsable y verificar qué tablas existen
  en `develop` frente a la especificación de 27 entidades HOSPITAL.
- **Contenido aceptado:** la estructura de la ficha de acuerdos (acuerdo, opciones, quién decide,
  dónde se responde, qué bloquea, fecha límite y consecuencia) y la tabla de inventario por slice.
- **Contenido verificado con comandos reales:** el inventario de tablas de las dos bases y el
  listado de migraciones HOSPITAL de `origin/develop`. La evidencia de que la FK compuesta no es
  implementable (SQLSTATE 42830) se había medido antes y se reproduce en la ficha.
- **Verificación humana pendiente:** Javier revisa esta declaración, la ficha de acuerdos y el
  inventario antes de publicar.
- **Datos usados:** únicamente metadatos de esquema y nombres de tabla; no hay datos de pacientes,
  credenciales ni tokens.
