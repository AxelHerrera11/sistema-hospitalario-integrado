# Métricas de calidad — Signos vitales y alertas (Semana 14)

Siete métricas para «captura de signos vitales, evaluación de umbrales y generación de alerta». Cada una tiene **fórmula**, **umbral** (lo aceptable), **fuente** (de dónde sale el dato), **responsable** y **acción** si se incumple. La línea base se midió el 2026-10-20 con PHP 8.3.33.

## Tabla de métricas

| ID | Métrica | Fórmula | Umbral | Fuente | Frecuencia | Responsable | Acción si se incumple | Línea base |
|---|---|---|---|---|---|---|---|---|
| **Q-01** | Cobertura de RF por prueba | RF con al menos una prueba automática ÷ 7 RF | **100 %** | Matriz RF → prueba de la Semana 15 y salida de `micro-his/signos-vitales/tests/*.php` | Cada PR | Autor | El PR no se integra hasta agregar la prueba | **7 de 7** |
| **Q-02** | Hallazgos Críticos abiertos de la revisión | Hallazgos Críticos con estado ≠ Verificado | **0** (y 0 Mayores sin responsable ni fecha) | Acta y registro de hallazgos de la revisión formal (Semana 13) | En cada revisión y antes de integrar | Moderador | Decisión «Re-revisión»; se corrige en PR aparte de menos de 400 líneas | P-1, P-5 y P-7 son candidatos a Crítico sin clasificar; P-2 corregido |
| **Q-03** | Respuestas inseguras o sin código | POST con `5xx` ÷ POST totales; respuestas sin `code` o `trace_id` ÷ totales; respuestas que contradicen lo guardado | `5xx` **≤ 1 %**; sin `code` o `trace_id` **0 %**; contradicciones **0** | Bitácora de `ApiKernel` y el registro `notification_failed` | Semanal | Autor | Investigar con el `trace_id` y abrir hallazgo; una contradicción es Crítica | Sin datos de uso: en las pruebas, 0 `5xx` fuera de los simulados |
| **Q-04** | Latencia al registrar | p95 de `POST /care-episodes/{id}/vital-signs` | **≤ 300 ms** (RNF-SV-04) | `tests/bench.php` como referencia en proceso; bitácora con duración (**hoy no se registra**; acción: agregarla) | Semanal | Autor | Perfilar la consulta de asignación y la auditoría; no se aceptan reintentos para ocultar la lentitud | p95 de 0,087 ms con PDO sobre SQLite, en proceso y sin red |
| **Q-05** | Rechazo de accesos indebidos | Pruebas negativas que devuelven `401`/`403`/`503` ÷ pruebas negativas (sin identidad, sin permiso, sin asignación, RBAC caído, **tenant ajeno**) | **100 %**; 0 respuestas `200` a un acceso indebido | `tests/api.php` y la prueba de caja negra de la Semana 15 | Cada PR | Revisora y María (ASII-02) | Hallazgo Crítico; el PR no se integra | 100 % en los casos que existen; **tenant sin código ni prueba** (P-1) |
| **Q-06** | Cobertura del perfil de umbrales | Signos con regla en el código ÷ signos con umbral definido en el Anexo B de la Semana 1 | **100 %** | Prueba de caja negra de la Semana 15: una medición fuera de umbral por cada signo del Anexo B | Cada PR que toque reglas | Autor y responsable clínico | Agregar la regla del signo o declarar por escrito que se excluye; hasta entonces, el valor anormal de ese signo **no genera alerta** | **4 de 7** (faltan temperatura, sistólica y diastólica) |
| **Q-07** | Trazabilidad y privacidad de la auditoría | Mediciones sin evento de auditoría ÷ total; eventos con valores clínicos | **0 %** y **0 eventos** | `local_access_audits` (consultas Q-TRZ y Q-PRIV); en pruebas, `InMemoryVitalSignRepository::$audits` | Semanal | Javier y Autor | Hallazgo Crítico; se repara la escritura y se concilia el faltante | En pruebas 0 %; la tabla real **no existe** todavía (slice H8) |

## Cómo se mide cada una

- **Q-01:** `php micro-his/signos-vitales/tests/run.php`, `repository.php`, `api.php` y `client.php` (56 verificaciones hoy) más las suites de la Semana 15. Se cuenta contra la matriz RF → prueba.
- **Q-02:** el secretario de la revisión lleva el registro con el [formato de hallazgos](../semana-13/formato-hallazgos.md).
- **Q-03:** la bitácora de `ApiKernel` (`trace_id`, actor, método, ruta, estado) y el `error_log` `notification_failed` que añade la [corrección de P-2](../semana-13/correcciones/README.md). Una respuesta que contradice lo guardado es, por ejemplo, un `500` con la medición ya escrita.
- **Q-04:** `php micro-his/signos-vitales/tests/bench.php` como referencia; la medición real exige registrar la duración en la bitácora.
- **Q-06:** la prueba de la Semana 15 envía, por cada signo del Anexo B, un valor `critical` y comprueba que se genera la alerta.

## Consultas de medición

Para PostgreSQL, solo lectura. Q-TRZ y Q-PRIV dependen de `local_access_audits`, cuyas columnas son una propuesta (Anexo A de Notas SOAP) hasta que exista la migración del slice H8.

```sql
-- Q-TRZ: mediciones sin evento de auditoría de registro (provisional)
SELECT v.vital_sign_id FROM vital_signs v
LEFT JOIN local_access_audits a ON a.resource_id = v.vital_sign_id::text AND a.action = 'signo_vital.registrado'
WHERE a.audit_event_id IS NULL;

-- Q-PRIV: eventos del módulo con claves de metadatos fuera de la lista permitida (provisional)
SELECT audit_event_id FROM local_access_audits
WHERE action LIKE 'signo_vital.%'
  AND EXISTS (SELECT 1 FROM jsonb_object_keys(metadata::jsonb) k WHERE k NOT IN ('vital_sign_id', 'severity', 'findings', 'threshold_profile_version'));

-- Q-INT: mediciones validadas cuyo autor no tenía asignación activa (debe ser 0; el trigger de 000401 lo impide)
SELECT v.vital_sign_id FROM vital_signs v
WHERE v.status = 'validated' AND NOT EXISTS (
  SELECT 1 FROM doctor_profiles p JOIN care_assignments a ON a.doctor_profile_id = p.doctor_profile_id
  WHERE p.local_user_ref_id = v.recorded_by_user_ref_id AND a.care_episode_id = v.care_episode_id);
```

Q-TRZ y Q-PRIV son el control detectivo de la regla «ningún valor anormal sin traza»; Q-INT comprueba que el trigger `enforce_vital_sign_integrity` sigue activo.

## Acciones abiertas por las métricas

| Métrica | Acción | Responsable |
|---|---|---|
| Q-04 | Registrar la duración de cada petición en la bitácora de `ApiKernel` | Autor |
| Q-05 | Implementar y probar el aislamiento por tenant, o documentar qué capa lo garantiza (P-1) | Autor y María |
| Q-06 | Definir y validar las reglas de temperatura, sistólica y diastólica del Anexo B | Responsable clínico y Autor |
| Q-07 | Migración de `local_access_audits` (slice H8) | Javier |
