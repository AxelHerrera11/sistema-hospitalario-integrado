# Signos vitales y alertas por valores anormales — Semana 14: plan de aseguramiento de la calidad (SQA)

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** plan SQA con siete métricas, matriz de riesgos, controles preventivos y detectivos, responsables y calendario de seguimiento.

## Ficha de la asignación

- **Semana:** Semana 14 — Confiabilidad y aseguramiento de calidad
- **Tarea individual:** Defina el plan SQA de Signos vitales y alertas por valores anormales: calidad de «captura de signos vitales, evaluación de umbrales y generación de alerta», confiabilidad, seguridad, trazabilidad y riesgos de signos vitales, unidades, umbrales, valores anormales y alertas clínicas. Asigne responsable, umbral, fuente y acción para cada métrica y control.
- **Entregable / evidencia:** plan SQA con 5–7 métricas, matriz de riesgos, controles preventivos/detectivos, responsables y calendario de seguimiento.
- **Issue:** Refs #23
- **Alcance:** plan. **No se implementa código ni se modifica la base de datos**: lo que requiere una migración o una decisión clínica queda como acción para quien corresponda.

## Objetivo de calidad

Una alerta clínica es una decisión derivada de una medición. El plan SQA busca que **ninguna alerta se invente, ninguna se pierda sin que la persona lo sepa, y ningún valor anormal exista sin su traza**. Cada métrica y cada control protege una de esas tres garantías.

## Atributos de calidad y su evidencia

| Atributo | Qué se asegura | Requisito | Métrica |
|---|---|---|---|
| **Corrección funcional** | Cada RF tiene una prueba automática que lo ejercita | RF-SV-01 a 07 | Q-01 |
| **Calidad del código** | Los defectos graves se encuentran y cierran antes de integrar | Revisión formal de la Semana 13 | Q-02 |
| **Confiabilidad** | Ante una caída o un fallo del canal, la respuesta dice la verdad sobre lo guardado | RNF-SV-03 | Q-03 |
| **Rendimiento** | Registrar no retrasa la atención | RNF-SV-04 | Q-04 |
| **Seguridad** | Solo accede quien tiene permiso, identidad verificable, tenant y asignación | RNF-SV-01 | Q-05 |
| **Corrección clínica de las alertas** | Todo signo con umbral definido se evalúa; un valor anormal siempre alerta | RF-SV-03, 04 · RNF-SV-02 | Q-06 |
| **Trazabilidad y privacidad** | Cada escritura tiene su auditoría, sin valores clínicos | RF-SV-07 · RNF-SV-03 | Q-07 |

Las métricas, con su fórmula, umbral, fuente, responsable y acción, están en [metricas.md](./metricas.md). Los riesgos, los controles y el calendario, en [riesgos-controles.md](./riesgos-controles.md).

## Roles

| Rol | Responsabilidad en el plan | Quién |
|---|---|---|
| **Responsable del módulo (autor)** | Ejecuta las métricas Q-01, Q-03, Q-04 y Q-06, mantiene el plan y reporta | Josué Fernando Hicho García |
| **Revisora de PR** | Verifica tamaño (menos de 400 líneas), pruebas y checklist en cada PR | Yamilet |
| **Moderador de revisiones** | Convoca y cierra las revisiones formales (Semana 13) | Por designar |
| **Base de datos (ASII-09)** | Migraciones, tablas de auditoría y de salida (outbox); ejecuta las consultas de integridad | Javier |
| **Seguridad y permisos (ASII-02)** | Catálogo `signos_vitales.*`, identidad por JWT, tenant | María |
| **Alertas críticas (ASII-21)** | Canal de notificación y su reintento | Por designar |
| **Dueño del perfil de umbrales** | Define y mantiene el perfil `adulto-2026.1` y los factores de conversión, contrastándolos con NEWS2 y ADA | Josué Fernando Hicho García (autor; **sin formación clínica certificada**) |
| **Revisión clínica externa** | Valida el perfil antes de cualquier uso asistencial | Por designar; no hay nadie disponible hoy |
| **QA y regresión (ASII-28)** | Pruebas negativas, accesibilidad y regresión | Equipo de QA |

Los nombres de Javier, María, Yamilet y el equipo de QA vienen de las dependencias ya documentadas en las semanas anteriores; **nadie ha aceptado todavía las tareas de este plan**, así que cada asignación es una propuesta. El dueño de Alertas críticas no está definido.

**Limitación sobre los umbrales.** El perfil `adulto-2026.1` lo define y mantiene el autor, que **no es médico ni tiene formación clínica certificada**. Es un perfil de referencia académico, contrastado con NEWS2 (Royal College of Physicians, 2017) y con los niveles de hipoglucemia de la ADA, **sin revisión clínica profesional y no apto para uso asistencial**. Una revisión clínica externa sigue pendiente; hasta entonces, el riesgo R-03 permanece en nivel alto y ninguna cifra del módulo debe presentarse como clínicamente validada.

## Reglas del proceso

1. Todo PR del módulo tiene menos de 400 líneas propias, una revisora y sus pruebas; el tamaño lo comprueba la revisora.
2. Ningún PR se integra con un hallazgo Crítico de la revisión formal abierto (Q-02).
3. Cada requisito nuevo trae su prueba antes de integrar (Q-01).
4. Una métrica que se incumple abre una acción con responsable y fecha; si pasa de dos revisiones sin resolverse, se escala al moderador.
5. El plan se revisa en cada hito: pruebas de la Semana 15, integración con PostgreSQL y entrega con RBAC real.

## Estado de partida

Línea base medida el 2026-10-20 con PHP 8.3.33:

| Elemento | Valor |
|---|---|
| Verificaciones automáticas | 56: `run.php` 25, `repository.php` 9, `api.php` 14, `client.php` 8; todas pasan |
| RF con prueba | 7 de 7 |
| Revisión formal | Preparada; 8 puntos de atención, 2 corregidos (P-2 y P-3) |
| Cobertura del perfil de umbrales | **4 de 7 signos con umbral definido** tienen regla en el código (faltan temperatura, sistólica y diastólica) |
| Auditoría local | Tabla mínima de prueba; `local_access_audits` aún sin migración |

## Archivos

- [Métricas de calidad](./metricas.md): siete métricas con fórmula, umbral, fuente, responsable y acción, y las consultas de medición.
- [Riesgos, controles y calendario](./riesgos-controles.md)
- [Declaración de uso de IA](./DECLARACION_IA.md)
