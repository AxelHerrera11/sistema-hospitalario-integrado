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

- La identidad está autenticada y autorizada para la operación solicitada.
- El episodio de hospitalización existe, está abierto y tiene una estancia actual sin `ended_at`.
- La solicitud aporta el identificador de esa estancia como valor esperado.
- El sistema determina `T` al ejecutar y comprueba que produzca intervalos válidos.

## Flujos principales

### UC-TA-01 - Registrar traslado interno

1. El sistema autoriza la solicitud y determina `T`.
2. En una transacción HOSPITAL bloquea y relee el episodio y su estancia actual.
3. Confirma que la estancia esperada sigue siendo la actual y que `T > started_at`.
4. Valida el estado operativo de la cama destino y que ningún `bed_stays` se solape con `[T, infinito)`.
5. Cierra la estancia origen en `T` y crea la estancia destino desde `T` en la misma transacción; el episodio permanece abierto.
6. Confirma la transacción y responde con el traslado registrado.

### UC-TA-02 - Registrar alta hospitalaria

1. El sistema autoriza la solicitud, determina `T`, inicia la transacción y relee con bloqueo.
2. Valida episodio, estancia esperada e intervalo.
3. Cierra la estancia y el episodio en `T` dentro de la misma transacción HOSPITAL.
4. Confirma la transacción y responde con el alta registrada.

## Excepciones

- Una solicitud no autorizada se rechaza antes de abrir la transacción.
- Episodio cerrado, estancia ausente o distinta de la esperada, `T` inválido o cama destino no operativa/no disponible producen rechazo; si la transacción ya inició, se ejecuta `ROLLBACK` y se liberan bloqueos.
- Un fallo confirmado de persistencia antes de `COMMIT` revierte todos los cambios y libera bloqueos.
- Si se pierde la conexión durante `COMMIT`, el resultado queda incierto para el cliente: no se afirma que hubo rollback; se debe consultar el estado actual antes de reintentar.
- Todo camino de rechazo o error finaliza sin continuar hacia una respuesta de éxito.

## Postcondiciones

- **Traslado exitoso:** la estancia origen termina en `T`, la destino inicia en `T` y el episodio sigue abierto.
- **Alta exitosa:** estancia y episodio terminan en `T`.
- **Rechazo o rollback confirmado:** no queda un cambio parcial.
- Liberar una cama significa terminar la ocupación de ese episodio; su disponibilidad operativa posterior depende del catálogo de camas.

## Reglas y supuestos

- **R-TA-01:** la ocupación se deriva de los intervalos de `bed_stays`; `beds.status` por sí solo no demuestra disponibilidad.
- **R-TA-02:** el destino se valida para todo `[T, infinito)`, no únicamente en el instante `T`.
- **R-TA-03:** comparar la estancia origen esperada con la actual detecta solicitudes desactualizadas.
- **R-TA-04:** las escrituras atómicas pertenecen solo a HOSPITAL.
- **R-TA-05:** `T` es determinado por el sistema al ejecutar; este es un supuesto pendiente de formalización.
- **R-TA-06:** `ROLLBACK` es una excepción de ejecución, no un caso de uso obligatorio.

## Trazabilidad

| Caso | Objetivo | Actividad | Secuencia | Reglas |
|---|---|---|---|---|
| `UC-TA-01` | Cambiar la cama sin cerrar el episodio | Rama **Traslado**: validar `[T, infinito)`, cerrar origen y crear destino | Rama **Traslado** y mensajes HOSPITAL de cierre/creación | R-TA-01 a R-TA-05 |
| `UC-TA-02` | Cerrar ocupación y episodio por alta | Rama **Alta**: cerrar estancia y episodio | Rama **Alta** y actualización atómica HOSPITAL | R-TA-01, R-TA-03 a R-TA-05 |
| Ambos | Evitar cambios parciales o reintentos inseguros | Rechazo con rollback; resultado incierto durante commit | Bloques `break` y alternativas de persistencia/commit | R-TA-04, R-TA-06 |
