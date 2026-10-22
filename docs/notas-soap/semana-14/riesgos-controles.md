# Matriz de riesgos, controles y calendario — Notas SOAP y diagnósticos (Semana 14)

## Cómo se puntúa

**Probabilidad (P)** e **Impacto (I)** van de 1 a 3. **Nivel = P × I:** 6 o 9 *Alto*, 3 o 4 *Medio*, 1 o 2 *Bajo*. El impacto es 3 si afecta a un dato clínico, a la identidad de quien escribió o al paciente; 2 si degrada el trabajo sin dañar el dato; 1 si es de comodidad. Un control **preventivo** evita que el riesgo ocurra; uno **detectivo** lo encuentra cuando ocurrió. Las métricas Q-nn están en [metricas.md](./metricas.md).

## Matriz

| ID | Riesgo | Área | P | I | Nivel | Control preventivo | Control detectivo | Responsable | Métrica |
|---|---|---|---|---|---|---|---|---|---|
| R-01 | Se inserta un diagnóstico en una nota ya firmada: la base solo protege el `UPDATE` de la nota | Nota, diagnóstico | 2 | 3 | **Alto (6)** | Trigger en `diagnoses` que rechace notas `signed` y releer la nota dentro de la transacción | Consulta Q-INT1 semanal | Javier, Autor | Q-06 |
| R-02 | Código duplicado o dos principales por una carrera entre `find` y `INSERT` | Diagnóstico | 2 | 3 | **Alto (6)** | Índice único por (`clinical_note_id`, `code`) e índice único parcial del principal activo | Consulta Q-INT2 semanal | Javier | Q-06 |
| R-03 | El tenant no se aplica en el micro-HIS: acceso a notas de otro hospital | Seguridad | 2 | 3 | **Alto (6)** | Filtrar por tenant en cada consulta y rechazar `X-Tenant-ID` ajeno | Prueba negativa de tenant en cada PR; eventos `denied` | María, Autor | Q-05 |
| R-04 | La identidad es una cabecera `X-User-ID` sin firma: se puede suplantar al autor | Autoría | 2 | 3 | **Alto (6)** | Identidad desde un JWT firmado y validado por el módulo | Revisión mensual de eventos `denied` y de actores atípicos | María | Q-05 |
| R-05 | El alta de la nota no informa `medical_record_id` (obligatorio en la migración): falla o ancla mal | Expediente | 2 | 3 | **Alto (6)** | El caso de uso recibe el expediente del contexto, no del cliente; prueba contra PostgreSQL | Q-01 con la prueba de alta en el esquema real | Autor | Q-01 |
| R-06 | La unicidad de versión por expediente impide una segunda nota o la cadena de correcciones | Versionado | 3 | 2 | **Alto (6)** | Mover la unicidad a la cadena de corrección (petición pendiente a Javier) | Prueba de dos notas en el mismo expediente | Javier | Q-06 |
| R-07 | Se confirma una nota en el expediente del paciente equivocado desde la UI | Expediente | 2 | 3 | **Alto (6)** | Confirmación con nombre, expediente y fecha de nacimiento (B-01) | Revisión de usabilidad y pruebas manuales con dos pestañas | Autor | Q-02 |
| R-08 | Se pierde el borrador por recarga o sesión caducada | Nota | 3 | 2 | **Alto (6)** | Aviso al salir y aviso de caducidad; borrador en servidor (`clinical_note_drafts`, pendiente de Javier) | Prueba manual de recarga en cada release | Autor | Q-02 |
| R-09 | Pérdida de la traza: una escritura sin auditoría o con texto clínico en ella | Trazabilidad | 1 | 3 | Medio (3) | Auditoría en la misma transacción; metadatos con lista permitida | Consultas Q-TRZ y Q-PRIV | Autor, Javier | Q-07 |
| R-10 | Autoría ambigua tras un relevo de guardia: no se sabe quién es el autor vigente | Autoría | 2 | 2 | Medio (4) | Entidad de traspaso de autoría (petición a Javier) y regla de «autor original vs vigente» | Conciliación de `author_user_ref_id` con `care_assignments` | Javier | Q-06 |
| R-11 | El catálogo CIE-10 no tiene origen definido o no responde | Diagnóstico | 2 | 2 | Medio (4) | Falla segura: `503 ICD10_UNAVAILABLE` y nada se guarda | Tasa de `503` por semana (Q-03) | Autor | Q-03 |
| R-12 | La interfaz no es accesible: un médico no puede completar la nota con teclado o lector | UX | 2 | 2 | Medio (4) | Proponer `RNF-SOAP-07` (WCAG 2.2 AA) y las correcciones P0 y P1 de la Semana 9 | axe-core sin violaciones graves; recorrido con teclado a 320, 360 y 430 px | QA | Q-02 |
| R-13 | Quien no atiende el episodio distingue `404` de `403` y enumera identificadores de notas | Seguridad | 1 | 2 | Bajo (2) | Autorizar el episodio antes de revelar si la nota existe | Prueba negativa con una nota ajena | Autor | Q-05 |

## Resumen por nivel

| Nivel | Riesgos | Qué se hace |
|---|---|---|
| **Alto** (8) | R-01 a R-08 | Control preventivo planificado antes de integrar firma y corrección; detectivo activo desde la primera medición |
| **Medio** (4) | R-09 a R-12 | Se vigilan en la revisión quincenal |
| **Bajo** (1) | R-13 | Se atiende junto con R-03, que toca el mismo código |

Los riesgos R-01, R-02, R-03, R-04 y R-05 salen de los puntos de atención de la revisión técnica de la Semana 13 (PR #533). R-06 y R-10 son las peticiones a la base de datos de la Semana 6. R-07, R-08 y R-12 vienen de la evaluación de usabilidad de la Semana 9.

## Controles: quién y cuándo

| Control | Tipo | Responsable | Cuándo |
|---|---|---|---|
| PR de menos de 400 líneas con revisora | Preventivo | Autor y Yamilet | Cada PR |
| Prueba por cada RF antes de integrar (Q-01) | Preventivo | Autor | Cada PR |
| Revisión técnica formal con checklist de 15 ítems | Preventivo y detectivo | Moderador | Antes de firmar y corregir |
| Triggers e índices de R-01 y R-02 | Preventivo | Javier | A pedido; migración aparte |
| Pruebas negativas de seguridad (Q-05) | Preventivo | Revisora y María | Cada PR |
| Consultas Q-INT1, Q-INT2, Q-TRZ y Q-PRIV | Detectivo | Javier y Autor | Semanal |
| Bitácora de `ApiKernel` con duración y estado (Q-03, Q-04) | Detectivo | Autor | Semanal |
| Eventos `denied` y actores atípicos | Detectivo | María | Mensual |
| axe-core y recorrido con teclado | Preventivo y detectivo | QA | Cada release |

## Calendario de seguimiento

Las fechas son una **propuesta** a partir de hoy (2026-10-20) y quedan sujetas a la confirmación de cada responsable.

| Fecha | Actividad | Responsable | Salida |
|---|---|---|---|
| 2026-10-27 | Reunión de la revisión técnica formal (Semana 13) | Moderador | Acta y lista de hallazgos |
| 2026-11-03 | **Primera medición** de Q-01, Q-05 y Q-06; crear las fuentes de Q-03, Q-04 y Q-07 | Autor, Javier | Tabla de estado inicial actualizada |
| 2026-11-10 | Migración de R-01 y R-02 (triggers e índices), si Javier la acepta | Javier | PR de migración aparte |
| 2026-11-17 | Revisión quincenal de riesgos: se rescatan los Altos sin control activo | Autor, Revisora | Matriz actualizada |
| 2026-12-01 | Informe SQA: las siete métricas, riesgos cerrados y pendientes | Autor | Informe en PR |

**Cadencia permanente:** semanal (consultas y bitácora), por PR (Q-01, Q-05 y tamaño), quincenal (riesgos) y mensual (eventos `denied`).

## Criterios de salida del plan

El plan se da por cumplido cuando las siete métricas tienen fuente y dato, ninguna está en rojo, no queda un riesgo **Alto** sin control preventivo activo y el informe SQA fue revisado por la revisora del módulo.
