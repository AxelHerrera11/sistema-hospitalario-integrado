# Métricas de calidad — Notas SOAP y diagnósticos (Semana 14)

Siete métricas para «registro de nota SOAP y asociación de diagnóstico clínico». Cada una tiene **fórmula**, **umbral** (lo que se considera aceptable), **fuente** (de dónde sale el dato), **responsable** y **acción** si se incumple. Los responsables están «por confirmar» (ver [README](./README.md)).

## Tabla de métricas

| ID | Métrica | Fórmula | Umbral | Fuente | Frecuencia | Responsable | Acción si se incumple |
|---|---|---|---|---|---|---|---|
| **Q-01** | Cobertura de reglas por prueba | RF con al menos una prueba automática ÷ 7 RF | **100 %** | Matriz RF → prueba de la Semana 2 y salida de `micro-his/notas-soap/tests/run.php` y `tests/api.php` | Cada PR | Autor | No se integra; se escribe la prueba que falta |
| **Q-02** | Hallazgos críticos abiertos de revisión | Hallazgos Críticos con estado ≠ Verificado | **0** (y 0 Mayores sin responsable ni fecha) | Acta y registro de hallazgos de la revisión formal (Semana 13) | Al cerrar cada revisión | Moderador | Re-revisión si hay más de 3 Críticos o más de 8 Mayores |
| **Q-03** | Fallas inseguras o sin código | POST con `5xx` ÷ POST totales, y respuestas sin `code` o `trace_id` ÷ respuestas totales | `5xx` **≤ 1 %**; sin `code` o `trace_id` **0 %** | Bitácora de `ApiKernel` (`trace_id`, `status`, `path`) | Semanal | Autor | Análisis de causa raíz en 2 días y prueba de regresión |
| **Q-04** | Latencia al asociar un diagnóstico | p95 de `POST /soap-notes/{id}/diagnoses` | **≤ 300 ms** con el catálogo en caché | Bitácora de `ApiKernel` con duración (**hoy no se registra; propuesto añadirla**) | Semanal | Autor | Revisar la consulta al catálogo y la caché; abrir hallazgo Mayor |
| **Q-05** | Rechazo de accesos indebidos | Pruebas negativas que devuelven `401`/`403` ÷ pruebas negativas (sin identidad, sin permiso, tenant ajeno, sin asignación) | **100 %**; 0 respuestas `200` a quien no atiende el episodio | `tests/api.php` (casos negativos) y eventos `denied` de `local_access_audits` | Cada PR y mensual | Revisora y María | Bloquear el release; abrir hallazgo Crítico |
| **Q-06** | Integridad clínica | Filas devueltas por las consultas Q-INT1 y Q-INT2 (más abajo) | **0** | Tablas `clinical_notes` y `diagnoses` (copia o usuario de solo lectura) | Semanal | Javier y Autor | Incidente: no se borra; se corrige con una versión nueva y se refuerza el trigger |
| **Q-07** | Trazabilidad y privacidad de la auditoría | Notas y diagnósticos sin evento de auditoría ÷ total, y eventos con texto clínico | **0 %** y **0 eventos** | `local_access_audits` y las tablas del módulo (consultas Q-TRZ y Q-PRIV) | Semanal | Autor y Javier | Hallazgo Crítico; la escritura sin traza se investiga como incidente |

## Consultas de medición

Para PostgreSQL, solo lectura. Las tres primeras usan columnas que existen en la migración `000400` (`signed_at`, `created_at`, `type`, `status`). Las dos últimas dependen de `local_access_audits`, cuya migración **aún no existe** (slice H8), así que son provisionales.

```sql
-- Q-INT1: diagnósticos añadidos después de la firma de la nota
SELECT d.diagnosis_id, d.clinical_note_id
FROM diagnoses d JOIN clinical_notes n ON n.clinical_note_id = d.clinical_note_id
WHERE n.status = 'signed' AND d.created_at > n.signed_at;

-- Q-INT2: notas con más de un diagnóstico principal activo, o con el mismo código repetido
SELECT clinical_note_id FROM diagnoses WHERE status = 'active' AND type = 'primary' AND clinical_note_id IS NOT NULL
GROUP BY clinical_note_id HAVING count(*) > 1
UNION
SELECT clinical_note_id FROM diagnoses WHERE status = 'active' AND clinical_note_id IS NOT NULL
GROUP BY clinical_note_id, code HAVING count(*) > 1;

-- Q-TRZ: notas sin evento de auditoría de registro (provisional)
SELECT n.clinical_note_id FROM clinical_notes n
LEFT JOIN local_access_audits a ON a.resource_id = n.clinical_note_id::text AND a.action = 'nota_soap.registrada'
WHERE a.audit_event_id IS NULL;

-- Q-PRIV: eventos del módulo con claves de metadatos fuera de la lista permitida (provisional)
SELECT audit_event_id FROM local_access_audits
WHERE (action LIKE 'nota_soap.%' OR action LIKE 'diagnostico.%')
  AND EXISTS (SELECT 1 FROM jsonb_object_keys(metadata::jsonb) k WHERE k NOT IN ('version_no', 'clinical_note_id', 'code', 'type'));
```

Q-INT1 y Q-INT2 detectan hoy lo que la base **no impide** (ver los puntos de atención P-1 y P-2 de la revisión de la Semana 13): son el control detectivo mientras llega el trigger preventivo.

## Cómo se interpreta

- **Verde:** la métrica cumple el umbral. No se hace nada.
- **Rojo:** no cumple. Se aplica la acción de la tabla antes de integrar y se anota en el seguimiento.
- **Sin dato:** la fuente aún no existe (Q-04, Q-07). Se trata como rojo para planificar: la acción es **crear la fuente**.

## Estado inicial

Medición del 2026-10-20, hecha por lectura y ejecución local, sin acceso a datos reales de producción:

| ID | Estado | Motivo |
|---|---|---|
| Q-01 | Sin medir | Falta la matriz RF → prueba cruzada con las pruebas actuales |
| Q-02 | Sin medir | La revisión de la Semana 13 está en preparación |
| Q-03 | Sin dato | La bitácora de `ApiKernel` existe como gancho, sin destino definido |
| Q-04 | Sin dato | La duración no se registra |
| Q-05 | Parcial | Hay pruebas negativas de permisos; no hay prueba de tenant ajeno |
| Q-06 | Rojo en el esquema de pruebas | Se insertó un diagnóstico en una nota firmada, un código repetido y dos principales (revisión de la Semana 13) |
| Q-07 | Sin dato | La migración de `local_access_audits` no existe |

Un plan honesto empieza diciendo que la mayoría de las métricas no tienen todavía fuente: crear esas fuentes es parte del calendario.
