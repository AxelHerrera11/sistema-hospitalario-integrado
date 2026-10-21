# Presentación de la primera evaluación parcial (Semana 6)

## 1. Presentación (guion de defensa)

### Diapositiva 1 — Alcance del módulo

- Módulo transversal: **verificar el esquema PostgreSQL, llevar la lista de tablas y
  coordinar quién escribe cada migración**.
- Alcance actual: 15 entidades CENTRAL y 27 HOSPITAL, de las cuales 31 ya tienen
  migración (15 + 16).
- Salida esperada: 15 entregas semanales, un PR por semana, y la verificación
  continua de las migraciones que el equipo integre entre el lunes 19 y el jueves 22.

### Diapositiva 2 — Modelo UML (Semana 1)

- `Patient` es la raíz local; `CareEpisode` es el agregado que agrupa asignaciones,
  notas, diagnósticos, alergias, signos vitales y estancias.
- `Ward → Bed → BedStay`: el intervalo de `BedStay` es la fuente autoritativa de
  ocupación; `Bed.status` es estado operativo.
- `CentralUser` y `Hospital` viven en CENTRAL y se referencian por UUID lógico.
- Evidencia: `semana-01/01-modelo-clases.svg` y `semana-01/02-clase-tabla.md`.

### Diapositiva 3 — Requisitos de datos (Semana 2)

- 10 RF y 6 RNF con criterio de aceptación, cada uno amarrado a una restricción real.
- Los cinco pilares: integridad referencial local, no solapamiento temporal, unicidad
  del activo equivalente, obligatoriedad de la identidad clínica y coherencia
  estado-vigencia.
- Cada requisito tiene una prueba que lo respalda: `semana-02/matriz-verificacion.md`.

### Diapositiva 4 — Arquitectura federada (Semana 3)

- Una base CENTRAL (red, identidad, MPI, consentimientos, sincronización) y una base
  HOSPITAL por hospital (16 tablas clínicas hoy).
- Sin FK entre bases: se usan UUID lógicos.
- Regla de asignación por tipo de dato y criterio de "una entidad, un dueño".
- Evidencia: `semana-03/01-arquitectura-federada.svg` y
  `semana-03/asignacion-entidades.md`.

### Diapositiva 5 — Capas y repositorio (Semana 4)

- Presentación sin SQL, aplicación con caso de uso, dominio sin infraestructura y
  persistencia con adaptadores PDO e InMemory tras una interfaz.
- La interfaz se define por concepto de dominio (`BedSnapshotReader`), no por tabla.
- Consulta de referencia: una sola sentencia con `EXISTS` sobre `bed_stays`.

### Diapositiva 6 — Matriz de acceso (Semana 5)

- 21 endpoints mapeados a sus tablas y a su tipo de operación.
- Seis unidades transaccionales: admisión, traslado, orden de laboratorio, resultado
  con alerta, firma de nota y sincronización.
- `GET /api/v1/occupancy` es lectura pura: no asigna ni libera camas.

### Diapositiva 7 — Caso práctico

**Pregunta:** un traslado de cama deja `beds.status = 'available'` pero el `bed_stay`
sigue abierto. ¿Qué responde el dashboard y qué debe hacer la base?

**Respuesta:**

1. El dashboard de ocupación **muestra la inconsistencia** (ocupación por intervalo,
   estado operativo aparte). No corrige el dato: el módulo es de consulta agregada.
2. La base de datos **impide** el estado imposible: la exclusión GiST
   `bed_stays_bed_active_period_exclusion` rechaza dos estancias activas en la misma
   cama, y `bed_stays_period_check` rechaza un intervalo con `ended_at <= started_at`.
3. El módulo de traslados (ASII-08) escribe cerrar y abrir en **una sola transacción**,
   para que no exista una cama con dos estancias activas ni una estancia huérfana.

**Conclusión:** la base garantiza la coherencia estructural; los módulos de flujo
garantizan la coherencia del proceso; el dashboard solo informa. Esa separación es la
base del módulo de verificación de BD.

### Diapositiva 7 — Cierre

- Entregado: modelo, requisitos, topología, capas y matriz de acceso.
- Pendiente declarado: auditoría de migraciones, errores de integridad, calidad de
  datos, paginación, índices, SQA y pruebas de caja negra (semanas 7 a 15).
- Acuerdos abiertos: quién escribe cada migración nueva, contrato común de errores,
  seeder compartido y capa común de filtros y exportación.
