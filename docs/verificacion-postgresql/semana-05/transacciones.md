# Unidades transaccionales y su garantia en la base (Semana 5)

Cada unidad describe el error que evita la transacción y la restricción del esquema que
la hace necesaria. Los scripts son ilustrativos de la intención; la implementación
real depende del framework de cada módulo.

## 1. Admisí3n con asignación de cama

```text
BEGIN;
  INSERT INTO care_episodes (...);        -- episodio de cuidado
  INSERT INTO bed_stays (...);            -- estancia abierta en la cama
  INSERT INTO care_assignments (...);     -- profesional responsable
COMMIT;
```

- **Falla que evita:** que exista un episodio sin cama o una cama ocupada por un
  episodio que no se creó.
- **Restricciones que participan:** FK local con `ON DELETE RESTRICT`,
  `bed_stays_open_care_episode_unique` (una estancia abierta por episodio) y
  `bed_stays_bed_active_period_exclusion` (no dos estancias activas en la misma cama).

## 2. Traslado o alta de cama

```text
BEGIN;
  UPDATE bed_stays SET ended_at = :now WHERE bed_stay_id = :stay AND ended_at IS NULL;
  INSERT INTO bed_stays (...) VALUES (...);  -- nueva estancia, si es traslado
  UPDATE beds SET status = :estado WHERE bed_id = :cama;
COMMIT;
```

- **Falla que evita:** cama con dos estancias activas, o cama liberada sin estancia
  cerrada.
- **Restricciones que participan:** `bed_stays_bed_active_period_exclusion` y
  `bed_stays_period_check` (`ended_at > started_at`).

## 3. Orden de laboratorio con sus items

```text
BEGIN;
  INSERT INTO lab_orders (...);
  INSERT INTO lab_order_items (...);
COMMIT;
```

- **Falla que evita:** una orden sin items o con items huérfanos.
- **Restricciones que participan:** FK local, unicidad por `lab_test_id` en la orden y
  estado inicial `REQUESTED` con `CHECK` de dominio.
- **Estado:** propuesta; las tablas `lab_orders` y `lab_order_items` todavia no tienen
  migracióón y las restricciones las define el dueño del módulo.

## 4. Resultado de laboratorio con alerta crítica

```text
BEGIN;
  INSERT INTO lab_result_versions (...);
  INSERT INTO crítical_alerts (...) ON CONFLICT (source_id, source_version_id) DO NOTHING;
COMMIT;
```

- **Falla que evita:** un resultado critico sin alerta, o alertas duplicadas por
  reintento.
- **Restricciones que participan:** clave idempotente de la alerta y FK local al
  resultado.
- **Estado:** propuesta; las dos tablas siguen sin migracióón en `develop`.

## 5. Firma de nota clínica

```text
BEGIN;
  UPDATE clinical_notes SET status = 'signed', signed_at = :now WHERE clinical_note_id = :nota AND status = 'draft';
  INSERT INTO local_access_audits (...);
COMMIT;
```

- **Falla que evita:** nota firmada sin auditoría, o auditoría de una firma que no
  ocurrióó.
- **Restricciones que participan:** trigger `clinical_notes_signed_immutable` y la
  unicidad de la versióón por expediente.
- **Estado:** la parte clínica está implementada; `local_access_audits` (slice H8)
  todavia no.

## 6. Publicación de evento (outbox)

```text
BEGIN;
  INSERT INTO outbox_events (..., idempotency_key) VALUES (...);
COMMIT;
```

- **Falla que evita:** un evento perdido o duplicado en el destino.
- **Restricciones que participan:** clave de idempotencia única y
  `central_inbox_messages` con clave única en CENTRAL.
- **Estado:** propuesta; `outbox_events` es parte del slice H8 pendiente.

## Regla general

Una operación que escribe más de una tabla, o que cambia un estado del que depende
otro, se ejecuta en una transaccióón. La base de datos no sustituye la transaccióón:
la base garantiza que los estados imposibles no se puedan escribir, y la transaccióón
garantiza que no queden escrituras a medias.
