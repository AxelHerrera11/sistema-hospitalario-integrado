# Semana 13 — Calidad del software y revisiones técnicas formales

## Portada

- **Estudiante:** Fajardo López, Javier Alexander (`Javifa2324`)
- **Módulo:** Verificación de Base de Datos PostgreSQL
- **Semana:** Semana 13 — Revisión técnica formal de un PR de base de datos del grupo
- **Entregable / evidencia:** Acta de revisión con checklist, hallazgos, severidad y responsables.
- **Estructura:** Sistema Hospitalario Integrado 2026 | Semanas 1–15.

## 1. Alcance

Revisión formal del PR **#376 — "Semana 4 — Notas SOAP: Repository con adaptadores PDO e
InMemory y auditoría por interfaz"** (módulo de notas SOAP y diagnósticos), por ser el
PR abierto del grupo que introduce persistencia y un esquema SQL propio.

La revisión se hizo **sobre el diff del PR**, no sobre la rama local del módulo, y se
comunicó al autor con un comentario en el propio PR el mismo día, como manda el plan
de integración.

## 2. Alcance y límites de la revisión

| Sí se revisó | No se revisó |
|---|---|
| Sentencias SQL y su forma | Lógica de negocio del módulo |
| Estructura de las tablas del doble | Interfaz de usuario |
| Transacciones y reversión | Contrato API publicado |
| Correspondencia con el esquema federado | Rendimiento en volumen |
| Registro de entidades fuera de la especificación | Integración con otros módulos |

## 3. Acta de revisión

- **PR revisado:** #376 (Notas SOAP, semana 4).
- **Revisor:** Javier Alexander Fajardo López (módulo de verificación de BD).
- **Fecha:** 20 de octubre de 2026.
- **Alcance:** 708 líneas, 40 archivos; persistencia PDO e InMemory y esquema de prueba.
- **Veredicto:** **Aprobado con condiciones**. Se integra a `develop` cuando el módulo
  cierre el adaptador real contra PostgreSQL 16 y registre las dos entidades fuera de
  la especificación.

### 3.1 Hallazgos

| Id | Severidad | Hallazgo | Responsable | Acción |
|---|---|---|---|---|
| R-01 | Mayor | El identificador de nota se genera como `'nota-' . bin2hex(random_bytes(4))`: 32 bits de entropía y prefijo no UUID. Con volumen real puede colisionar y no cumple el formato UUID del esquema federado. | Módulo de notas SOAP | Usar UUID v4 (`Ramsey\Uuid` o `random_bytes(16)` con versión y variante) |
| R-02 | Mayor | El doble define `local_access_audits` con las columnas `event`, `actor_id`, `detail`, mientras que la entidad `local_access_audit` del slice H8 tiene otro diseño previsto. El nombre de tabla coincide con una entidad de la especificación y el cuerpo no: cuando llegue H8 habrá que migrar. | Módulo de notas SOAP con el dueño de H8 | Declarar el doble como provisional y alinear las columnas con la entidad aprobada |
| R-03 | Mayor | `icd10_codes` es una tabla nueva que **no está** en la especificación de 27 entidades y no está registrada en la lista de tablas del módulo de BD. | Módulo de notas SOAP | Registrarla como tabla local de catálogo o pedir su incorporación a la especificación |
| R-04 | Moderado | El doble no replica las restricciones del esquema federado: unicidad de versión por expediente (`clinical_notes_version_unique`), inmutabilidad al firmar, coherencia episodio-paciente-autor y FK a `medical_records`. Las pruebas en SQLite pasan sin ellas. | Módulo de notas SOAP | Ejecutar el adaptador real contra PostgreSQL 16 con las pruebas de integridad del repositorio |
| R-05 | Moderado | `diagnoses` del doble solo declara FK hacia `clinical_notes`; el esquema federado exige además `care_episode_id` y `author_user_ref_id` con integridad de trigger. | Módulo de notas SOAP | Alinear el doble con las columnas y el trigger |
| R-06 | Menor | `clinical_notes.version_no` tiene valor por defecto pero sin unicidad por expediente: dos notas versión 1 pueden coexistir en el doble. | Módulo de notas SOAP | Añadir la restricción equivalente al `UNIQUE` del esquema real |
| R-07 | Menor | `onset_date` y otros instantes se guardan como `TEXT` en lugar de `date`/`timestamptz`: no hay validación de formato ni de rango. | Módulo de notas SOAP | Usar los tipos del esquema federado en el adaptador real |
| R-08 | Positivo | Sentencias preparadas con parámetros vinculados, sin concatenación. | — | Mantener |
| R-09 | Positivo | La transacción incluye la escritura de auditoría y revierte si la bitácora falla: "todo o nada" correcto. | — | Mantener |
| R-10 | Positivo | Separación de dominio, aplicación y persistencia, con dobles InMemory para las pruebas. | — | Mantener |

### 3.2 Acciones derivadas

1. El comentario con esta revisión se publica en el PR #376 el mismo día.
2. La tabla `icd10_codes` se incorpora a la lista de entidades del módulo de BD
   (semana 3) como **entidad candidata fuera de la especificación**, a la espera de
   decisión.
3. El módulo de notas SOAP recibe la lista de restricciones del esquema federado que
   debe replicar su doble (R-04, R-05, R-06, R-07).
4. La propiedad del slice H8 (que define `local_access_audit`) se acuerda con el
   responsable de auditoría, para que el doble no se consolide con otro diseño.

### 3.3 Checklist aplicado

| Punto | Resultado |
|---|---|
| Las consultas usan sentencias preparadas | Cumple |
| Las escrituras multi-tabla están en una transacción | Cumple |
| La reversión se ejecuta ante cualquier fallo | Cumple |
| Los identificadores son UUID | **No cumple** (R-01) |
| Los tipos coinciden con el esquema federado | **No cumple** (R-07) |
| Las entidades nuevas están registradas | **No cumple** (R-02, R-03) |
| Las restricciones del esquema real están replicadas | **No cumple** (R-04, R-05, R-06) |
| La documentación coincide con el código | Cumple |
| El PR no toca carpetas de otros módulos | Cumple |
| No hay credenciales ni datos sensibles | Cumple |

## 4. Criterios de severidad aplicados

| Severidad | Definición |
|---|---|
| Bloqueante | Impide integrar: rompe la integridad o la seguridad del sistema |
| Mayor | Puede integrar con riesgo de datos incorrectos, duplicados o colisiones |
| Moderado | Divergencia entre el doble y el esquema real que puede fallar en producción |
| Menor | Mejora de calidad o consistencia, sin riesgo inmediato |
| Positivo | Práctica correcta que conviene preservar |

## 5. Validación ejecutada

```bash
# comando: gh pr view 376 --json files,additions
# resultado: 40 archivos y 708 lineas; la revision se hizo sobre el diff del PR

# comando: gh pr diff 376 | grep -n "schema.sql\|INSERT INTO clinical_notes\|random_bytes"
# resultado: esquema SQLite de 6 tablas con TEXT como clave, INSERT con placeholders y generacion de id con random_bytes(4)

# comando: podman run --rm --network host -v $PWD:/app -w /app localhost/php85-pgsql-full php vendor/bin/phpunit tests/Database
# resultado: OK (53 tests, 679 assertions) — el esquema de referencia contra el que se compara el doble
```

## 6. Riesgos y pendientes

- La revisión se hizo sobre el PR tal como estaba el 20 de octubre; si el módulo
  publica cambios posteriores, hay que revisarlos de nuevo.
- Los hallazgos R-02, R-03 y R-04 dependen de decisiones de otros módulos (slice H8 y
  especificación de entidades): se comunicaron y quedan con responsable.
- El módulo de notas SOAP no tiene su ruta en Laravel; el adaptador real todavía no
  existe, así que R-04 no puede cerrarse hasta que exista.
