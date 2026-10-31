# Corrección de los hallazgos P-2 y P-3 de la revisión técnica (Semana 13)

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** corrección de dos defectos declarados en la [revisión técnica](../README.md), con pruebas y verificación.

## Alcance

Se corrigen los dos puntos de atención que la revisión marcó como más graves y reproducibles. **No** se tocan los demás (tenant, enumeración de ids, identidad por cabecera, esquema, unidades, decimales): siguen abiertos para la reunión.

| Hallazgo | Ítem | Severidad | Estado |
|---|---|---|---|
| **P-2** — un fallo del canal tras el `COMMIT` se informa como `500 VITAL_SIGN_PERSISTENCE_ERROR` con la medición ya guardada | C-06, C-11 | Crítico | **Corregido** |
| **P-3** — una `measured_at` futura se acepta con `201` | C-04 | Mayor | **Corregido** (la fecha antigua, por ejemplo 1900, **no**: el requisito solo prohíbe la futura) |

## P-2 — el canal de notificación ya no cambia el resultado de la medición

**Antes.** `RecordVitalSigns` llamaba a `AlertNotifier::notify()` sin proteger la llamada. Una excepción subía al controlador, que la traducía a `500 VITAL_SIGN_PERSISTENCE_ERROR` aunque la medición y su auditoría ya estaban confirmadas. El médico leía «no se registró nada», repetía el envío y duplicaba la medición y la alerta.

**Ahora.**

- `notify()` se ejecuta dentro de un `try`: un fallo devuelve `notification: "failed"` y la respuesta sigue siendo `201 ALERT_GENERATED`.
- El fallo se registra con el id de la medición y la clase de la causa (`onNotificationFailure`), **sin valores clínicos**. En el front controller sale a `error_log` como `notification_failed`.
- La respuesta tiene un campo nuevo, `notification`, con tres valores: `not_required` (sin alerta), `queued` y `failed`. Es **aditivo**: los clientes que no lo leen siguen funcionando. Está en el [contrato](../../semana-05/contrato-api.md) y el [OpenAPI](../../semana-05/openapi.yaml).
- La interfaz de usuario deberá mostrar `failed` como «Se guardó, pero no se pudo notificar: avise usted directamente»; es un pendiente de UX, no se implementa aquí.

## P-3 — la fecha de medición no puede ser futura

**Antes.** Solo se validaba el formato. Una `measured_at` de 2099 daba `201`; contra PostgreSQL la restricción `recorded_at >= measured_at` la rechazaría y el usuario vería un `500`.

**Ahora.** `measured_at` no puede superar la hora del servidor más 5 minutos (`RecordVitalSigns::CLOCK_SKEW`), tolerancia para el desfase entre el reloj del cliente y el del servidor. Pasada esa tolerancia responde `422 MEASURED_AT_INVALID` y no se guarda nada. La hora del servidor se inyecta (`clock`) para poder probar el límite sin depender del reloj real.

## Verificación

```bash
php micro-his/signos-vitales/tests/run.php   # 25 verificaciones (19 + 6 nuevas)
php micro-his/signos-vitales/tests/repository.php && php micro-his/signos-vitales/tests/api.php && php micro-his/signos-vitales/tests/client.php
php docs/signos-vitales/semana-13/evidencia/sondas.php
```

Las cuatro suites terminan con código de salida 0 (PHP 8.3.33). Pruebas nuevas en `run.php`:

| Prueba | Hallazgo |
|---|---|
| `measured_at` 5 min adelante → `201` (tolerancia) | P-3 |
| `measured_at` 5 min y 1 s adelante → `422 MEASURED_AT_INVALID`, sin guardar | P-3 |
| `measured_at` del año 2099 → `422 MEASURED_AT_INVALID` | P-3 |
| Canal caído tras el `COMMIT` → `201 ALERT_GENERATED` con `notification: failed`, no `500` | P-2 |
| La medición queda guardada una vez (2 eventos de auditoría) y el fallo se registra con su id | P-2 |
| Con el canal sano, `queued` con alerta y `not_required` sin alerta | P-2 |

**Sensibilidad de las pruebas (mutaciones).** Se cambió una línea, se ejecutó `run.php` y se restauró el archivo. Las tres mutaciones **se detectaron**:

| Mutación | Resultado |
|---|---|
| Quitar la comprobación de fecha futura | Falla «5 min y 1 s adelante» |
| Tolerancia de 0 minutos | Falla «5 min adelante → 201» |
| Capturar solo `LogicException` en lugar de `Throwable` | Falla «canal caído → 201» |

**Salida de la sonda tras la corrección:**

```text
P-2  C-11     canal de notificación caído: respuesta 201 ALERT_GENERATED; la medición quedó guardada y auditada (signo_vital.registrado): 2 eventos, notification failed
P-3  C-04     measured_at futura: 422 MEASURED_AT_INVALID
P-3  C-04     measured_at de 1900: 201 OK
```

## Cierre de los hallazgos

Según el [formato](../formato-hallazgos.md), un Crítico no se cierra sin evidencia repetida. La evidencia es la de arriba (pruebas y sonda); el estado pasa a *En corrección → Verificado* cuando el moderador repita los comandos. El resto de los puntos de atención (P-1, P-4 a P-8) sigue **Abierto**.

## Archivos

- [Declaración de uso de IA](./DECLARACION_IA.md)
- Código: `micro-his/signos-vitales/src/Application/RecordVitalSigns.php`, `app.php`, `public/index.php`, `tests/run.php`.
