# Matriz de riesgos, controles y calendario — Signos vitales y alertas (Semana 14)

## Cómo se puntúa

**Probabilidad (P)** e **Impacto (I)** van de 1 a 3. **Nivel = P × I:** 6 o 9 *Alto*, 3 o 4 *Medio*, 1 o 2 *Bajo*. El impacto es 3 si el fallo **inventa u oculta una alerta**, afecta a la identidad de quien registró o expone una medición a quien no debe. La probabilidad es 3 si el comportamiento **ya se observa** en el código.

## Matriz

| ID | Riesgo | Área | P | I | Nivel | Control preventivo | Control detectivo | Responsable | Métrica |
|---|---|---|---|---|---|---|---|---|---|
| R-01 | Temperatura, sistólica y diastólica no tienen regla de umbral: una fiebre de 41 °C o una presión de 230/130 se guarda con `201 OK` y **no genera alerta** | Umbrales, alertas | 3 | 3 | **Alto (9)** | Definir y validar las tres reglas del Anexo B con el responsable clínico y agregarlas como subtipos de `RangeRule` | Prueba de la Semana 15: un valor crítico por cada signo del Anexo B | Responsable clínico y Autor | Q-06 |
| R-02 | Sin unidad declarada, glucosa 5,5 mmol/L (normal) se evalúa como mg/dL y da una alerta crítica falsa | Unidades, alertas | 3 | 3 | **Alto (9)** | `Measurement` y `Unit` con unidad explícita y rechazo de la desconocida (diseño de la Semana 7) | Aviso suave en la UI; prueba de caja negra con valores en mmol/L | Autor | Q-03, Q-06 |
| R-03 | Los umbrales `adulto-2026.1` son ilustrativos: cada alerta depende de valores sin validación clínica | Umbrales | 3 | 3 | **Alto (9)** | Validación por el responsable clínico y versión del perfil en la alerta | Revisión del perfil en cada hito | Responsable clínico | Q-06 |
| R-04 | El tenant no se aplica en el micro-HIS (0 coincidencias en `src/`): acceso a mediciones de otro hospital | Seguridad | 2 | 3 | **Alto (6)** | Filtrar por tenant en cada consulta y rechazar `X-Tenant-ID` ajeno | Prueba negativa con tenant ajeno | Autor y María | Q-05 |
| R-05 | La identidad es la cabecera `X-User-ID`, sin firma: cualquiera puede suplantar al médico | Seguridad | 2 | 3 | **Alto (6)** | Identidad desde un JWT firmado y validado por el módulo (ASII-01, ASII-02) | Revisión mensual del permiso y de la bitácora | María | Q-05 |
| R-06 | El canal de notificación falla y la alerta solo queda en un registro: no hay cola con reintentos (RF-SV-07) | Alertas | 2 | 3 | **Alto (6)** | Cola con reintentos y salida por outbox hacia Alertas críticas | `notification: failed` en la respuesta y `notification_failed` en el registro | Autor y Alertas críticas | Q-03 |
| R-07 | No existe `local_access_audits`: la traza «ningún valor anormal sin traza» no se garantiza en la base real | Trazabilidad | 2 | 3 | **Alto (6)** | Migración del slice H8 y auditoría en la misma transacción | Consultas Q-TRZ y Q-PRIV | Javier | Q-07 |
| R-08 | El esquema local difiere del real: `recorded_by_user_ref_id`, `medical_record_id` obligatorio y estado inicial `draft` | Integridad | 3 | 2 | **Alto (6)** | Adaptador PostgreSQL alineado con `000200` y `000401` | Prueba contra PostgreSQL 16 antes de integrar | Autor y Javier | Q-01 |
| R-09 | Se pierde la medición por recarga, sesión vencida o conexión caída en el móvil | UX, datos | 3 | 2 | **Alto (6)** | Aviso al salir, aviso de sesión y `Idempotency-Key`; borrador en servidor (pendiente de Javier) | Tasa de `notification_failed` y de reenvíos | Autor | Q-03 |
| R-10 | Frecuencia cardiaca y respiratoria se aceptan con decimales y la base las guarda como `integer` | Integridad | 2 | 2 | Medio (4) | Aceptar enteros en esos signos (decisión con Javier) | Prueba de límites de la Semana 15 | Autor y Javier | Q-01 |
| R-11 | Quien no atiende el episodio distingue `404` de `403` y enumera identificadores de mediciones | Seguridad | 1 | 2 | Bajo (2) | Autorizar el episodio antes de revelar si la medición existe | Prueba negativa de la Semana 15 | Autor | Q-05 |
| R-12 | La interfaz no es accesible: contraste insuficiente, coma decimal mal leída o foco perdido | UX | 2 | 3 | **Alto (6)** | Correcciones P0 y P1 de la Semana 9; propuesta RNF-SV-06 | axe y recorrido con teclado | Autor y QA | Q-01 |

## Resumen por nivel

| Nivel | Riesgos | Qué se hace |
|---|---|---|
| **Alto** (10) | R-01 a R-09 y R-12 | Control preventivo planificado antes de integrar con PostgreSQL y RBAC; detectivo activo desde la primera medición |
| **Medio** (1) | R-10 | Se vigila en la revisión quincenal |
| **Bajo** (1) | R-11 | Se atiende junto con R-04, que toca el mismo código |

R-04, R-05, R-07, R-08, R-10 y R-11 salen de los puntos de atención de la [revisión técnica de la Semana 13](../semana-13/README.md); R-06 es lo que queda del punto P-2 tras su [corrección](../semana-13/correcciones/README.md). R-01 se confirmó midiendo el código con valores de temperatura y presión que dan `201 OK` sin alerta; R-02 y R-03 son los de las Semanas 6 y 7; R-09 y R-12 vienen de las Semanas 9 a 11.

## Controles: quién y cuándo

| Control | Tipo | Responsable | Cuándo |
|---|---|---|---|
| PR de menos de 400 líneas con revisora | Preventivo | Autor y Yamilet | Cada PR |
| Prueba por cada RF antes de integrar (Q-01) | Preventivo | Autor | Cada PR |
| Revisión técnica formal con checklist de 15 ítems | Preventivo y detectivo | Moderador | Antes de integrar con PostgreSQL |
| Validación clínica del perfil de umbrales y de la conversión | Preventivo | Responsable clínico | Antes de activar las alertas con datos reales |
| Pruebas negativas de seguridad, con tenant ajeno (Q-05) | Preventivo | Revisora y María | Cada PR |
| Consultas Q-TRZ, Q-PRIV y Q-INT | Detectivo | Javier y Autor | Semanal |
| Bitácora de `ApiKernel` con duración y registro `notification_failed` (Q-03, Q-04) | Detectivo | Autor | Semanal |
| Prueba de cobertura del perfil de umbrales (Q-06) | Detectivo | Autor | Cada PR que toque reglas |
| Revisión de accesibilidad con axe y teclado | Detectivo | QA | Antes de cada entrega de UI |

## Calendario de seguimiento

Fechas propuestas; **ninguna ha sido aceptada por su responsable**.

| Fecha | Actividad | Responsable | Salida |
|---|---|---|---|
| Cada PR | Verificar tamaño, pruebas, Q-01 y Q-05 | Yamilet | Revisión del PR |
| Semanal (lunes) | Medir Q-03, Q-04, Q-06 y Q-07; correr las consultas de integridad | Autor y Javier | Tabla de métricas actualizada |
| Semana del 26/10/2026 | Pruebas de unidad, caja blanca y caja negra (Semana 15) y medición de Q-06 | Autor | Matriz RF/RNF → prueba y defectos |
| Semana del 02/11/2026 | Reunión de revisión técnica formal (tres sesiones de 90 min) | Moderador | Acta y decisión |
| Semana del 09/11/2026 | Validación del perfil de umbrales y de la conversión | Responsable clínico | Perfil firmado o corregido |
| Antes de integrar con PostgreSQL | Adaptador alineado con `000200` y `000401`, `local_access_audits` y tenant | Autor, Javier y María | R-04, R-07 y R-08 cerrados |
| Cada hito | Revisar el plan: riesgos nuevos, métricas fuera de umbral, controles inútiles | Autor | Versión nueva del plan |

## Qué pasa si una métrica se incumple

1. Se abre una acción con responsable y fecha en el registro de hallazgos.
2. Si es Crítica (una alerta oculta o inventada, una medición sin traza, un acceso indebido), se detiene la integración del módulo hasta verificar la corrección.
3. Si pasa dos revisiones sin resolverse, el moderador la escala al responsable del módulo y a la persona que corresponda del equipo.
