# Contrato del slice H8 — Auditoría local, outbox e inbox (ASII-09, módulo de verificación de BD)

> Documento para el módulo de signos vitales (ASII-13) y para alertas críticas (ASII-21).
> Responde a los tres puntos de base de datos planteados el 20 de octubre de 2026 y publica el
> DDL del slice H8, que hasta ahora no existía en `develop`.

## 0. Estado de esta entrega

| Elemento | Estado |
|---|---|
| Diseño de `local_access_audits` | Confirmado: es el Anexo A publicado en el PR #376 (ASII-11), con la aclaración de que `correlation_id` es un **uuid** y el `trace_id` del RBAC viaja en `metadata` |
| Migración del slice H8 | **Escrita y probada** en este PR: `database/migrations/hospital/2026_08_11_000800_create_local_audit_and_outbox_tables.php` |
| Pruebas | 14 pruebas y 122 aserciones sobre PostgreSQL 16, más la suite `Database` completa en verde (73 pruebas, 860 aserciones) |
| Dueño del slice H8 | **PENDIENTE de acuerdo del grupo**: el slice no tiene dueño asignado (el PR #413 de `doctor_schedules` tiene el mismo tipo de bloqueo) |
| Número de archivo `000800` | **Propuesta**: `000500`–`000700` están reservados para H5, H6 y H7, y `000900` para `doctor_schedules` |
| Canal de publicación hacia ASII-21 | **PENDIENTE**: se coordina el contrato del evento; la tabla `outbox_events` ya existe con este PR, así que la publicación real es posible sin esperar a nadie |

## 1. Las tres tablas

### 1.1 `local_access_audits` — bitácora local de acceso

| Columna | Tipo | Regla |
|---|---|---|
| `audit_event_id` | `uuid` PK | `gen_random_uuid()` |
| `correlation_id` | `uuid` **NOT NULL** | Identificador de la petición; enlaza auditoría y outbox |
| `actor_user_ref_id` | `uuid` NOT NULL FK → `local_user_refs` | `ON DELETE RESTRICT` |
| `module` | `text` NOT NULL | No vacío. Ejemplo: `signos_vitales` |
| `action` | `text` NOT NULL | No vacío. Ejemplo: `signo_vital.registrado` |
| `resource_type` | `text` NOT NULL | No vacío. Ejemplo: `vital_sign` |
| `resource_id` | `uuid` | Polimórfico, sin FK |
| `local_patient_id` | `uuid` FK → `local_patients` | Opcional, `ON DELETE RESTRICT` |
| `care_episode_id` | `uuid` FK → `care_episodes` | Opcional, `ON DELETE RESTRICT` |
| `decision` | `text` NOT NULL | `allowed` o `denied` (por defecto `allowed`) |
| `reason` | `text` | Motivo de la corrección o de la denegación |
| `metadata` | `jsonb` NOT NULL | Objeto. **Nunca texto clínico** |
| `occurred_at` | `timestamptz` NOT NULL | Momento del evento |
| `created_at` | `timestamptz` NOT NULL | Inserción |

Índices: `correlation_id`, `actor_user_ref_id`, `(resource_type, resource_id)` y `occurred_at`.

**Solo inserciones.** Un trigger (`local_access_audits_append_only_trigger`) rechaza `UPDATE` y
`DELETE` con excepción de PostgreSQL `P0001`.

### 1.2 `outbox_events` — eventos salientes

| Columna | Tipo | Regla |
|---|---|---|
| `outbox_event_id` | `uuid` PK | `gen_random_uuid()` |
| `event_id` | `uuid` NOT NULL **UNIQUE** | Idempotencia del evento |
| `correlation_id` | `uuid` NOT NULL | Mismo valor que en la auditoría |
| `aggregate_type` / `aggregate_id` | `text` / `uuid` | Recurso del que nace el evento |
| `event_type` | `text` NOT NULL | No vacío. Ejemplo: `signo_vital.alerta_generada` |
| `payload_minimal` | `jsonb` NOT NULL | Objeto con identificadores mínimos, sin texto clínico |
| `metadata` | `jsonb` NOT NULL | Objeto |
| `occurred_at` | `timestamptz` NOT NULL | |
| `published_at` | `timestamptz` | Solo si `status = 'published'` |
| `status` | `text` NOT NULL | `pending`, `publishing`, `published`, `failed` |
| `attempts` | `integer` NOT NULL | `>= 0` |
| `error_code` | `text` | |

Índices: parcial sobre `occurred_at` cuando `status = 'pending'`, e índice por `correlation_id`.

**El payload es inmutable.** Un trigger (`outbox_events_payload_immutable_trigger`) permite
actualizar solo `status`, `published_at`, `attempts` y `error_code`; cualquier cambio en el
contenido del evento se rechaza. El `DELETE` también se rechaza.

### 1.3 `inbox_messages` — mensajes entrantes

| Columna | Tipo | Regla |
|---|---|---|
| `message_id` | `uuid` PK | `gen_random_uuid()` |
| `event_id` | `uuid` NOT NULL **UNIQUE** | Idempotencia de la recepción |
| `source_node_id` | `uuid` | Nodo emisor (referencia lógica) |
| `event_type` | `text` NOT NULL | No vacío |
| `payload_minimal` / `metadata` | `jsonb` NOT NULL | Objetos |
| `received_at` / `available_at` | `timestamptz` NOT NULL | |
| `processed_at` / `failed_at` | `timestamptz` | segun el estado |
| `status` | `text` NOT NULL | `pending`, `processing`, `processed`, `failed` |
| `attempts` | `integer` NOT NULL | `>= 0` |
| `error_code` | `text` | |

Refleja la tabla gemela `central_inbox_messages` de la base CENTRAL: mismo vocabulario de estados
e idempotencia por `event_id`.

## 2. Cómo lo usa el módulo de signos vitales

**Nombres de evento** (convención `modulo.accion`):

| Evento | `module` | `action` / `event_type` | Cuándo |
|---|---|---|---|
| Signo registrado | `signos_vitales` | `signo_vital.registrado` | Se inserta el `vital_sign` |
| Alerta generada | `signos_vitales` | `signo_vital.alerta_generada` | El valor sale del umbral configurado |
| Sin evaluación | `signos_vitales` | `signo_vital.sin_evaluacion` | No hay umbral aplicable para la medición |

**Correlación:** un solo `correlation_id` (uuid) por petición. Ese mismo valor va en la fila de
auditoría y en la del outbox, de modo que un evento y su traza se consultan juntos. El `trace_id`
que devuelve el middleware RBAC (cadena tipo `trace-a1b2c3`) **no** es un uuid: se guarda en
`metadata`, por ejemplo `{"trace_id": "trace-a1b2c3"}`.

**Ejemplo en una sola transacción** (auditoría y evento salen juntos o no sale ninguno):

```php
$db->transaction(function () use ($request, $vitalSign, $correlationId): void {
    $db->insert(
        "INSERT INTO local_access_audits
           (correlation_id, actor_user_ref_id, module, action, resource_type, resource_id,
            local_patient_id, care_episode_id, decision, metadata)
         VALUES (?, ?, 'signos_vitales', 'signo_vital.registrado', 'vital_sign', ?, ?, ?, 'allowed', ?)",
        [$correlationId, $actorUserRefId, $vitalSign->id, $patientId, $episodeId, json_encode(['trace_id' => $traceId])],
    );

    $db->insert(
        "INSERT INTO outbox_events
           (event_id, correlation_id, aggregate_type, aggregate_id, event_type, payload_minimal)
         VALUES (?, ?, 'vital_sign', ?, 'signo_vital.alerta_generada', ?)",
        [$eventId, $correlationId, $vitalSign->id, json_encode(['vital_sign_id' => $vitalSign->id])],
    );
});
```

Si la transacción falla, no queda ni auditoría ni evento: hay una prueba que lo verifica.

**Publicación:** el publicador del outbox toma las filas `status = 'pending'` ordenadas por
`occurred_at`, marca `status = 'publishing'` y, al entregar, `status = 'published'` con
`published_at`. Si falla, `status = 'failed'` con `error_code` y `attempts` incrementado. Al ser
único el `event_id`, reintentar no duplica el evento.

## 3. Respuestas a los tres puntos planteados

1. **Auditoría local:** el diseño es el del Anexo A con tres precisiones: `correlation_id` es uuid
   (el `trace_id` va en `metadata`), existen `decision`, `reason`, `occurred_at` y
   `local_patient_id` opcional, y la politica de solo-insertar se protege con trigger. **La migración está
   escrita en este PR**; lo que falta es el acuerdo del grupo sobre el dueño del slice H8 y el
   número de archivo.
2. **`vital_signs` (000401):** `heart_rate`, `respiratory_rate`, `systolic_bp` y `diastolic_bp` son
   `integer`; se aceptan enteros. `medical_record_id` es `NOT NULL` con `ON DELETE RESTRICT`:
   se responde `409 MEDICAL_RECORD_NOT_FOUND` y **no** se crea la historia clínica automáticamente
   (es del módulo de expediente). `status` puede grabarse directo en `validated` con
   `validated_at = now()`, pero la fila validada es inmutable: las correcciones se insertan como
   versión nueva (`supersedes_id` + `correction_reason` + `version_no = n+1`).
3. **Alertas:** no se crea tabla de alertas en el módulo de signos vitales. El evento se publica
   por el outbox hacia ASII-21, y con este PR la tabla `outbox_events` ya existe: el adaptador real
   puede conectarse cuando el contrato de publicación con ASII-21 quede acordado.

## 4. SQLSTATEs que puede encontrar un módulo consumidor

| SQLSTATE | Causa | Respuesta sugerida |
|---|---|---|
| `23503` | Actor, paciente o episodio inexistente | `404` del recurso o `409` del prerrequisito, nunca dejar escapar el 23503 |
| `23505` | `event_id` repetido en outbox o inbox | Tratar como **idempotencia**: no es error |
| `23514` | `CHECK` incumplido (decisión, `metadata` no objeto, `published_at` sin `occurred_at`) | `422` con el código del módulo |
| `P0001` | Trigger de auditoría o de payload inmutable | `409` o `500` según el caso; nunca `UPDATE` sobre auditoría |

## 5. Cómo se verifica

```bash
# comando: php vendor/bin/phpunit tests/Database/Hospital/LocalAuditOutboxIntegrityTest.php
# resultado: OK (14 tests, 122 assertions)

# comando: php vendor/bin/phpunit --testsuite Database
# resultado: OK (73 tests, 860 assertions)
```

## 6. Acuerdos pendientes

| # | Acuerdo | Quién decide | Dónde |
|---:|---|---|---|
| H8-1 | ¿Quién es el dueño del slice H8 (`local_access_audits`, `outbox_events`, `inbox_messages`)? | Grupo, coordinado por Yamilet | Issue #36 |
| H8-2 | ¿Se confirma el número `2026_08_11_000800`? | Grupo | Issue #36 |
| H8-3 | ¿Se confirma el diseño de la auditoría local tal como está en el Anexo A? | ASII-11 y ASII-13 | Issue #36 |
| H8-4 | Contrato de publicación del outbox hacia alertas críticas | ASII-21 (Alertas críticas) | Issue del módulo de alertas |
