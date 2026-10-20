# Narrativa de alcance y trazabilidad

**Módulo:** `ASII-08` | **Issue:** `#9` | **PR futuro:** hacia `develop` con `Refs #9`.

## Alcance

El módulo registra un traslado interno (`UC-TA-01`) o un alta hospitalaria (`UC-TA-02`) y conserva la consistencia entre el episodio y su ocupación. Usa las entidades existentes `care_episodes`, `bed_stays` y `beds` de HOSPITAL. La interfaz, el componente de autorización y el servicio de aplicación mostrados son propuestas, no implementaciones actuales.

## Exclusiones

- Decidir criterios clínicos, firmas o autorizaciones adicionales para un alta.
- Crear episodios, camas o salas; gestionar mantenimiento o limpieza.
- Trasladar entre hospitales o escribir en CENTRAL.
- Incluir auditoría externa o notificaciones en la garantía transaccional.
- Garantizar que una cama liberada quede operativamente disponible de inmediato.

## Actores

- **Personal autorizado para traslado:** solicita mover el episodio a una cama destino.
- **Personal autorizado para alta:** solicita cerrar la ocupación y el episodio.
- Los roles concretos están pendientes. La autorización se propone como componente interno del Sistema Hospitalario Integrado, no como actor ni servicio externo.

## Precondiciones

- La identidad está autenticada; el sistema todavía debe autorizar la operación solicitada.
- El episodio existe, es `inpatient`, está abierto y tiene una estancia actual sin `ended_at`.
- La solicitud aporta el identificador de esa estancia; el traslado aporta cama destino y `reason` no vacío, exigido por `bed_stays`.
- Como supuesto de diseño, el servicio fija una sola `T` de tipo `timestamptz` después de adquirir los bloqueos y la reutiliza en todas las escrituras; exige `T > care_episodes.started_at` y `T > bed_stays.started_at`.

## Flujos principales

### UC-TA-01 - Registrar traslado interno

1. El sistema autoriza la solicitud y abre una transacción HOSPITAL.
2. Según la estrategia propuesta, relee con `SELECT ... FOR UPDATE` el episodio, la estancia actual y la cama destino, en ese orden; después fija `T` una sola vez.
3. Confirma tipo `inpatient`, estado abierto, estancia esperada = actual, `reason` no vacío y las restricciones temporales de `T`.
4. Valida que el destino exista, sea distinto del origen, tenga `status = available` y no tenga `bed_stays` solapados con `[T, infinito)`.
5. Cierra el origen como `[started_at,T)` y crea el destino `[T,infinito)` para el mismo episodio; quedan contiguos y el episodio sigue abierto.
6. Solicita `COMMIT` y, solo ante éxito confirmado, responde con el traslado registrado.

### UC-TA-02 - Registrar alta hospitalaria

1. El sistema autoriza la solicitud y, según la estrategia propuesta, bloquea con `FOR UPDATE` episodio y estancia actual en una transacción HOSPITAL; después fija `T` una sola vez.
2. Valida tipo `inpatient`, episodio abierto, estancia esperada = actual y las restricciones temporales de `T`.
3. Cierra la estancia y el episodio en `T` dentro de la misma transacción HOSPITAL.
4. Solicita `COMMIT` y, solo ante éxito confirmado, responde con el alta registrada.

## Excepciones

- Una solicitud no autorizada se rechaza antes de abrir la transacción.
- Episodio incompatible, estancia ausente/desactualizada o `T` inválido producen rechazo; tras `BEGIN` se hace `ROLLBACK` y se liberan bloqueos.
- En un traslado, `reason` vacío o destino igual al origen, inexistente, no operativo (`status != available`) o solapado producen `ROLLBACK` y terminación antes de escribir.
- Un fallo confirmado de persistencia antes de `COMMIT` revierte todos los cambios y libera bloqueos.
- Un `COMMIT` exitoso y confirmado permite responder éxito.
- Un fallo confirmado del `COMMIT` cierra la transacción, ejecuta `ROLLBACK` si todavía sigue activa y responde error.
- Si se pierde la conexión durante `COMMIT`, el resultado queda incierto: no se garantiza `ROLLBACK` y se debe consultar el estado antes de reintentar.
- Todo camino de rechazo o error finaliza sin continuar hacia una respuesta de éxito.

## Postcondiciones

- **Traslado exitoso:** origen `[inicio,T)` y destino `[T,infinito)` son contiguos, pertenecen al mismo episodio y este sigue abierto.
- **Alta exitosa:** estancia y episodio terminan en `T`.
- **Rechazo o rollback confirmado:** no queda un cambio parcial.
- Liberar una cama significa terminar la ocupación de ese episodio; su disponibilidad operativa posterior depende del catálogo de camas.

## Reglas existentes y propuestas

| Regla | Estado y evidencia |
|---|---|
| **R-TA-01 · `beds.status = available`** | **Propuesta para este flujo.** El esquema solo limita el catálogo a `available`, `occupied`, `maintenance` o `inactive`; no sincroniza estado y ocupación. La prueba incluso cambia a `maintenance` una cama ocupada (`database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php:24`; `tests/Database/Hospital/SchedulingAndBedIntegrityTest.php:47-55`). |
| **R-TA-02 · `reason` no vacío** | **Existente.** `bed_stays.reason` es `NOT NULL` y tiene `CHECK (btrim(reason) <> '')`; crear la estancia destino del traslado queda sujeto a esa restricción (`database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php:26-28`). |
| **R-TA-03 · bloqueos** | **Propuesta para este flujo.** No existe aún el servicio de traslados/altas. Se diseña `SELECT ... FOR UPDATE` en orden episodio, estancia y destino, más la comparación estancia esperada = actual, para serializar participantes cooperantes. |
| **R-TA-04 · estancia abierta única** | **Existente.** El índice parcial `bed_stays_open_care_episode_unique` admite como máximo una estancia sin `ended_at` por episodio (`database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php:31`; prueba: `tests/Database/Hospital/SchedulingAndBedIntegrityTest.php:62-70`). |
| **R-TA-05 · exclusión GiST** | **Existente.** `bed_stays_bed_active_period_exclusion` rechaza solapes por cama sobre rangos `[)` y permite adyacencia (`database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php:29`; prueba: `tests/Database/Hospital/SchedulingAndBedIntegrityTest.php:66-70`). |
| **R-TA-06 · atomicidad y `T`** | **Supuesto propuesto.** Una conexión/transacción HOSPITAL fija una sola `T` después de bloquear, valida `T` contra ambos inicios y la reutiliza; traslado produce `[inicio,T)` y `[T,infinito)`, alta cierra estancia y episodio en `T`. El esquema exige finales posteriores a sus inicios (`database/migrations/hospital/2026_08_11_000200_create_patient_and_care_tables.php:23-30`; `database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php:27-29`). |
| **R-TA-07 · errores** | **Diseño propuesto.** `ROLLBACK` es una excepción de ejecución; CENTRAL, auditoría externa y notificaciones quedan fuera de la transacción HOSPITAL. |

## Trazabilidad

| Caso | Objetivo | Actividad | Secuencia | Reglas |
|---|---|---|---|---|
| `UC-TA-01` | Cambiar la cama sin cerrar el episodio | Rama **Traslado**: validar `[T, infinito)`, cerrar origen y crear destino | Rama **Traslado** y mensajes HOSPITAL de cierre/creación | R-TA-01 a R-TA-06 |
| `UC-TA-02` | Cerrar ocupación y episodio por alta | Rama **Alta**: cerrar estancia y episodio | Rama **Alta** y actualización atómica HOSPITAL | R-TA-03, R-TA-04, R-TA-06 |
| Ambos | Evitar cambios parciales o reintentos inseguros | Rechazo con rollback; tres resultados de `COMMIT` | Bloques `break` y alternativas de persistencia/`COMMIT` | R-TA-06, R-TA-07 |
