# Narrativa de alcance y trazabilidad — Catálogo de pruebas de laboratorio

## Propósito

Que el laboratorio mantenga **un catálogo único y versionado de las pruebas que se pueden ordenar**, con sus requisitos de muestra (tipo, recipiente, volumen, ayuno, preparación y transporte), su tipo de resultado, su unidad, sus rangos de referencia y críticos, y una vigencia explícita. Órdenes (ASII-16), recepción de muestras (ASII-18), ingreso y validación de resultados (ASII-19 y ASII-20) y alertas críticas (ASII-21) consultan esta definición en lugar de duplicarla.

## Alcance funcional

**Incluido**

- Registrar una prueba con:
  - Código local único por versión, nombre, sección y código LOINC opcional.
  - Requisitos de muestra: tipo de muestra, recipiente, volumen mínimo, ayuno y horas de ayuno, preparación, tiempo máximo de transporte y condición de conservación.
  - Tipo de resultado (`numeric`, `text`, `ordinal`, `qualitative`), unidad (obligatoria si es numérico), rangos de referencia y críticos, y tiempo estimado de entrega.
  - Vigencia `[valid_from, valid_to)` sin altas retroactivas.
- Rechazar altas con requisitos incompletos, rangos incoherentes, vigencia inválida o solapada con otra versión activa del mismo código.
- Consultar el catálogo vigente a una fecha (hoy por defecto) y el detalle con requisitos de muestra.
- Cerrar la vigencia o desactivar una versión, con motivo y sin borrarla.
- Versionar: un cambio de requisitos o rangos se registra como una **nueva versión**; las órdenes ya emitidas conservan la versión con la que se pidieron.
- Auditar altas y cierres en `local_access_audits` (HOSPITAL).

**Fuera de alcance**

- Emitir órdenes: módulo *Órdenes de laboratorio desde EMR* (ASII-16).
- Recibir, identificar o rechazar muestras: módulo *Recepción de muestras* (ASII-18).
- Capturar y validar resultados: módulos ASII-19 y ASII-20.
- Detectar y escalar valores críticos: módulo *Alertas críticas* (ASII-21); este módulo solo define los umbrales.
- Rangos de referencia por sexo y edad, y perfiles o paneles compuestos (por ejemplo, hemograma con varios analitos): se evalúan en la Semana 5 (ver R2 y R3).
- Precios y facturación de pruebas.

## Supuestos y restricciones

- **El catálogo es por hospital:** vive en la base HOSPITAL del tenant activo. El código LOINC opcional permite comparar pruebas entre hospitales de la red sin compartir la tabla.
- **Versiones y vigencia:** cada fila es una versión con `version_no`. Dos versiones activas del mismo código no pueden tener vigencias solapadas (propuesta: exclusión GiST con `btree_gist`, que ya está instalado en HOSPITAL).
- **Sin altas retroactivas:** `valid_from` no puede ser anterior a hoy, para que una orden ya emitida nunca cambie de definición.
- **Rangos coherentes:** crítico bajo < referencia baja ≤ referencia alta < crítico alto. Los rangos solo aplican a resultados numéricos.
- **Ayuno:** si la prueba requiere ayuno, las horas de ayuno son obligatorias y mayores que cero.
- **Sin borrado:** las versiones se cierran o desactivan, nunca se borran, porque las órdenes y los resultados las referencian.
- **Inmutabilidad clínica (propuesta):** una vez que una versión se usa en una orden, sus requisitos, unidad y rangos no se modifican; solo se cierra su vigencia.
- **Transacción local:** el alta o el cierre, la auditoría y `outbox_events` van en la conexión `hospital`. Si la auditoría falla, `ROLLBACK`.

## Trazabilidad entre los diagramas

| UC | Objetivo | Actividad (decisión o paso) | Secuencia (mensajes) |
|---|---|---|---|
| UC-LAB-01 | Registrar prueba en el catálogo | Partición A: «Jefatura de laboratorio elige Nueva prueba», «Capturar código, nombre y sección…» → «BEGIN … INSERT lab_tests» → «COMMIT · 201 Prueba registrada» | 1–2, 4–7, 19–20, 27–29 |
| UC-LAB-02 | Definir requisitos de muestra | A: «… requisitos de muestra (UC-LAB-02): tipo, recipiente, volumen…», «¿Campos obligatorios y requisitos de muestra completos?» | 8–10 |
| UC-LAB-03 | Definir resultado, rangos y vigencia | A: «Capturar tipo de resultado, unidad, rangos…», «¿Resultado numérico con unidad y crítico bajo < …?», «¿Vigencia desde ≥ hoy…?» | 11–15 |
| UC-LAB-04 | Rechazar alta inválida o solapada | A: «422 Requisito de muestra faltante…», «422 RANGES_INCOHERENT», «422 VALIDITY_INVALID», «¿El código ya tiene una versión activa cuya vigencia se solapa?» → «409 LAB_TEST_VALIDITY_OVERLAP», «Exclusión GiST de vigencia violada» | 10, 13, 15–18, 23–24 |
| UC-LAB-05 | Consultar catálogo vigente | Partición B: «Médico, técnico u Órdenes de laboratorio buscan…», «Filtrar status = active y vigencia…», «¿Hay pruebas vigentes?» | 30–31, 33–38 |
| UC-LAB-06 | Consultar requisitos de muestra de una prueba | B: «Usuario abre el detalle…», «¿La prueba existe en el tenant?», «¿Está vigente a la fecha consultada?» → detalle histórico o requisitos | 39–47 |
| UC-LAB-07 | Cerrar vigencia o desactivar prueba | Partición C completa: «¿La versión existe… y su vigencia sigue abierta?», «¿Motivo escrito…?», «¿Hay órdenes pendientes…?» → «UPDATE lab_tests…» → «COMMIT · 200» | `PATCH /api/v1/lab-tests/{labTest}/validity` (se detalla en la Semana 5) |
| UC-LAB-08 | Validar acceso y permisos | A y C: «¿JWT, tenant y permiso laboratorio.catalogo.administrar?». B: «¿JWT o token de servicio, tenant y permiso laboratorio.catalogo.ver?» | 2–4, 31–33, 40–41 |
| UC-LAB-09 | Registrar auditoría (`local_access_audits`, HOSPITAL) | A: «+ local_access_audits + outbox_events», «Falla de auditoría» → ROLLBACK + 500. C: «+ auditoría con motivo», «¿Auditoría registrada?» | 21–22, 25–26 |

## Flujo y resultados

### A · Registrar prueba en el catálogo

1. **Acceso (UC-LAB-08):** JWT, tenant y permiso `laboratorio.catalogo.administrar`; sin ellos, `401` o `403` con la traza de RBAC.
2. **Formato:** campos obligatorios y tipos válidos; si no, `422` con errores por campo.
3. **Requisitos de muestra (UC-LAB-02):** tipo de muestra y recipiente presentes, volumen mínimo mayor que cero y horas de ayuno si la prueba lo exige; si no, `422 SPECIMEN_REQUIREMENT_INVALID`.
4. **Resultado y rangos (UC-LAB-03):** unidad obligatoria si el resultado es numérico y rangos coherentes; si no, `422 RANGES_INCOHERENT`.
5. **Vigencia:** `valid_from` ≥ hoy y `valid_to` > `valid_from`; si no, `422 VALIDITY_INVALID`.
6. **Solapamiento (UC-LAB-04):** si el código ya tiene una versión activa cuya vigencia se solapa, `409 LAB_TEST_VALIDITY_OVERLAP` y se muestra la versión vigente; primero se cierra su vigencia (UC-LAB-07).
7. **Persistencia y auditoría (UC-LAB-09):** `INSERT lab_tests` con `version_no = n + 1`, `INSERT local_access_audits` e `INSERT outbox_events` en una transacción. Si otra alta concurrente viola la exclusión GiST, `ROLLBACK` y `409`; si falla la auditoría, `ROLLBACK` y `500`.
8. **Resultado:** `201`; la prueba aparece en el catálogo desde su `valid_from`.

### B · Consultar catálogo vigente y requisitos

1. **Acceso:** usuario con JWT o servicio (Órdenes) con token de servicio, tenant y permiso `laboratorio.catalogo.ver`; sin ellos, `401` o `403`.
2. **Filtros:** texto y fecha válidos; si no, `422`.
3. **Búsqueda (UC-LAB-05):** versiones `active` con `valid_from ≤ fecha < valid_to` (o sin `valid_to`). Si no hay coincidencias, `200` con lista vacía.
4. **Detalle (UC-LAB-06):** `404 LAB_TEST_NOT_FOUND` si no existe; si existe pero no está vigente a la fecha, `200` con `vigente: false` («no se puede ordenar»); si está vigente, `200` con requisitos, preparación, unidad y rangos.

### C · Cerrar vigencia o desactivar prueba

1. **Acceso:** permiso `laboratorio.catalogo.administrar`; sin él, `401` o `403`.
2. **Estado:** `404` si no existe; `409 LAB_TEST_ALREADY_CLOSED` si ya está inactiva o con vigencia cerrada.
3. **Datos:** motivo obligatorio y `valid_to` ≥ hoy y > `valid_from`; si no, `422`.
4. **Órdenes pendientes:** se advierte que las órdenes emitidas conservan su versión; no se cancelan.
5. **Persistencia y auditoría:** `UPDATE lab_tests` (`valid_to` o `status = 'inactive'`) y auditoría con motivo en la misma transacción. Si la auditoría falla, `ROLLBACK` y `500`.
6. **Resultado:** `200`; la versión deja de ofrecerse para nuevas órdenes desde `valid_to`.

## Riesgos y pendientes detectados en el esquema

| # | Hallazgo | Impacto | Propuesta |
|---|---|---|---|
| R1 | `lab_test` está prevista en el slice H6, pero no tiene migración (tarea 3.6) ni columnas definidas. | Sin acuerdo, Órdenes, Recepción, Resultados y Alertas modelarían la prueba de formas distintas. | Proponer las columnas del [Anexo A](#anexo-a--propuesta-de-la-tabla-lab_tests-hospital) y validarlas con el grupo y con quienes tienen ASII-16, 18, 19, 20 y 21 antes de implementar (Semanas 5–7). |
| R2 | La propuesta tiene un solo rango de referencia por prueba; no distingue sexo ni edad. | Rangos inadecuados para pediatría o por sexo, lo que puede generar falsos anormales o falsos críticos. | El spec del esquema fija exactamente 27 entidades HOSPITAL, así que una tabla nueva de rangos requiere cambiar el spec. Alternativa: una columna `jsonb` de rangos por sexo y edad con forma validada. Se decide en la Semana 5. |
| R3 | No hay perfiles ni paneles (una prueba con varios analitos, como el hemograma). | El catálogo solo modela pruebas de un analito. | Evaluar en la Semana 5 una relación padre-hijo (`panel_id`) o un `jsonb` de analitos, coordinado con ASII-16 y ASII-19. |
| R4 | La relación entre versión de prueba y órdenes (`lab_order_items.lab_test_id`) aún no existe. | La regla «las órdenes conservan su versión» depende de que la orden guarde el `lab_test_id` de la versión y no solo el código. | Acordarlo con *Órdenes de laboratorio* (ASII-16) al definir H6. |
| R5 | Los umbrales críticos los usa *Alertas críticas* (ASII-21) para detectar valores críticos. | Si cambian sin versión, una alerta histórica no se podría explicar. | Mantener umbrales en la versión de la prueba y que la alerta guarde el `lab_test_id` usado. |
| R6 | `local_access_audits` (slice H8) aún no tiene migración (tarea 3.7). | Sin ella no se garantiza «alta y auditoría en la misma transacción». | Reutilizar la propuesta del Anexo A de Notas SOAP (ASII-11) y validarla con el grupo. |

## Dependencias con otros módulos

- **Depende de:**
  - *RBAC* (ASII-02): permisos `laboratorio.catalogo.*`.
  - Esquema HOSPITAL: migración del slice H6 (`lab_tests`), `local_user_refs` (ya existe), `local_access_audits` y `outbox_events` (slice H8).
- **Dependen de este módulo:**
  - *Órdenes de laboratorio desde EMR* (ASII-16): solo ofrece pruebas vigentes y guarda la versión.
  - *Recepción de muestras* (ASII-18): verifica los requisitos de muestra.
  - *Ingreso de resultados* (ASII-19) y *Validación por bioquímico* (ASII-20): usan tipo de resultado, unidad y rangos.
  - *Alertas críticas* (ASII-21): usa los umbrales críticos.

## Anexo A — Propuesta de la tabla `lab_tests` (HOSPITAL)

> **Estado:** propuesta de documentación (Semana 1). No se implementa esta semana. Se debe validar con la persona encargada del esquema de base de datos y con los módulos de laboratorio, o, si no hay responsable asignado, con el grupo. Corresponde a la entidad `lab_test` del slice H6 (tarea 3.6, `000600_create_laboratory_tables.php`).

Sigue las convenciones de las migraciones HOSPITAL existentes: tabla en plural, PK `uuid` con `gen_random_uuid()`, FK locales con `ON DELETE RESTRICT`, textos acotados con `CHECK`, índices en las FK, exclusión GiST con `btree_gist` y protección contra borrado.

```sql
CREATE TABLE lab_tests (
    lab_test_id              uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    code                     text NOT NULL CONSTRAINT lab_tests_code_check CHECK (code ~ '^[A-Z0-9][A-Z0-9._-]{1,31}$'),
    loinc_code               text,
    name                     text NOT NULL CONSTRAINT lab_tests_name_check CHECK (btrim(name) <> ''),
    section                  text NOT NULL CONSTRAINT lab_tests_section_check CHECK (section IN ('hematology', 'chemistry', 'immunology', 'microbiology', 'urinalysis', 'coagulation', 'other')),
    specimen_type            text NOT NULL CONSTRAINT lab_tests_specimen_type_check CHECK (specimen_type IN ('whole_blood', 'serum', 'plasma', 'urine', 'stool', 'swab', 'csf', 'other')),
    container                text NOT NULL CONSTRAINT lab_tests_container_check CHECK (btrim(container) <> ''),
    min_volume_ml            numeric NOT NULL CONSTRAINT lab_tests_min_volume_check CHECK (min_volume_ml > 0),
    fasting_required         boolean NOT NULL DEFAULT false,
    fasting_hours            integer,
    preparation_instructions text,
    max_transport_minutes    integer CONSTRAINT lab_tests_transport_check CHECK (max_transport_minutes IS NULL OR max_transport_minutes > 0),
    storage_condition        text NOT NULL DEFAULT 'room_temperature' CONSTRAINT lab_tests_storage_check CHECK (storage_condition IN ('room_temperature', 'refrigerated', 'frozen')),
    result_type              text NOT NULL CONSTRAINT lab_tests_result_type_check CHECK (result_type IN ('numeric', 'text', 'ordinal', 'qualitative')),
    unit                     text,
    reference_low            numeric,
    reference_high           numeric,
    critical_low             numeric,
    critical_high            numeric,
    turnaround_hours         integer CONSTRAINT lab_tests_turnaround_check CHECK (turnaround_hours IS NULL OR turnaround_hours > 0),
    version_no               integer NOT NULL DEFAULT 1 CONSTRAINT lab_tests_version_check CHECK (version_no > 0),
    supersedes_id            uuid REFERENCES lab_tests(lab_test_id) ON DELETE RESTRICT,
    valid_from               date NOT NULL,
    valid_to                 date,
    status                   text NOT NULL DEFAULT 'active' CONSTRAINT lab_tests_status_check CHECK (status IN ('active', 'inactive')),
    created_by_user_ref_id   uuid NOT NULL REFERENCES local_user_refs(local_user_ref_id) ON DELETE RESTRICT,
    created_at               timestamptz NOT NULL DEFAULT now(),
    updated_at               timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT lab_tests_fasting_hours_check CHECK ((fasting_required AND fasting_hours > 0) OR (NOT fasting_required AND fasting_hours IS NULL)),
    CONSTRAINT lab_tests_numeric_unit_check CHECK (result_type <> 'numeric' OR (unit IS NOT NULL AND btrim(unit) <> '')),
    CONSTRAINT lab_tests_ranges_numeric_check CHECK (result_type = 'numeric' OR num_nonnulls(reference_low, reference_high, critical_low, critical_high) = 0),
    CONSTRAINT lab_tests_ranges_order_check CHECK (
        (reference_low IS NULL OR reference_high IS NULL OR reference_low <= reference_high)
        AND (critical_low IS NULL OR reference_low IS NULL OR critical_low < reference_low)
        AND (critical_high IS NULL OR reference_high IS NULL OR critical_high > reference_high)),
    CONSTRAINT lab_tests_validity_check CHECK (valid_to IS NULL OR valid_to > valid_from),
    CONSTRAINT lab_tests_supersedes_check CHECK ((supersedes_id IS NULL AND version_no = 1) OR (supersedes_id IS NOT NULL AND version_no > 1)),
    CONSTRAINT lab_tests_code_version_unique UNIQUE (code, version_no),
    CONSTRAINT lab_tests_code_active_validity_exclusion EXCLUDE USING gist (code WITH =, daterange(valid_from, valid_to, '[)') WITH &&) WHERE (status = 'active')
);
CREATE INDEX lab_tests_supersedes_id_index ON lab_tests (supersedes_id);
CREATE INDEX lab_tests_created_by_user_ref_id_index ON lab_tests (created_by_user_ref_id);
CREATE INDEX lab_tests_section_index ON lab_tests (section) WHERE status = 'active';
-- Protección contra borrado, como en las demás tablas clínicas
CREATE TRIGGER lab_tests_retention BEFORE DELETE ON lab_tests FOR EACH ROW EXECUTE FUNCTION preserve_lab_record();
```

Notas de la propuesta:

- `preserve_lab_record()` sería la función común de protección contra borrado del slice H6, igual que `preserve_h4_observation()` en H4.
- La regla «sin altas retroactivas» (`valid_from ≥ hoy`) queda en la aplicación, porque una restricción `CHECK` con `current_date` impediría restaurar respaldos.
- La inmutabilidad de requisitos y rangos de una versión ya usada en órdenes se implementaría con un trigger cuando exista `lab_order_items` (R4).
