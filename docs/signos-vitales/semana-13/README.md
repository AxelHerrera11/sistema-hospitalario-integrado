# Signos vitales y alertas por valores anormales — Semana 13: plan de revisión técnica formal

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales · **Entrega:** plan de revisión, checklist de 15 ítems, roles, criterios de entrada y salida y formato de hallazgos.

## Ficha de la asignación

- **Semana:** Semana 13 — Calidad del software y revisiones técnicas formales
- **Tarea individual:** Prepare una revisión técnica formal de Signos vitales y alertas por valores anormales, enfocada en reglas de dominio, contrato API, permisos/tenant, integridad de signos vitales, unidades, umbrales, valores anormales y alertas clínicas y dependencias estrictamente necesarias para «captura de signos vitales, evaluación de umbrales y generación de alerta».
- **Entregable / evidencia:** plan de revisión, checklist de 12–15 ítems, roles de autor/moderador/revisores, criterios de entrada/salida y formato de hallazgos.
- **Issue:** Refs #23
- **Alcance:** **preparación** de la revisión. No se corrige código: los hallazgos de la reunión se registran con el [formato](./formato-hallazgos.md) y se corrigen en PR aparte.

## Objetivo

Que otra persona, con este documento y sin hablar con el autor, pueda revisar el flujo que **decide una alerta clínica** (registrar la medición, evaluarla y notificar) y decidir si se puede firmar como «aprobado». El tipo de revisión es una **inspección** (Fagan): hay roles fijos, criterios de entrada y de salida y un acta; no es una demostración ni una discusión de diseño.

## Objeto de revisión

| Artefacto | Ubicación | Estado |
|---|---|---|
| Código del micro-HIS | `micro-his/signos-vitales/`, último cambio en el commit `c4787ea` de la rama de la Semana 13 (base: PR #550) | Se congela en ese commit |
| Contrato API v1 y OpenAPI | `docs/signos-vitales/semana-05/` (PR #546) | Congelado |
| Esquema real | `database/migrations/hospital/2026_08_11_000401_create_allergy_and_vital_sign_tables.php` y `000200` | Se revisa solo lo que toca la medición y el episodio |
| Pruebas | `tests/run.php` (19), `repository.php` (9), `api.php` (14) y `client.php` (8) | Pasan con PHP 8.3.33 |

**Tamaño:** 922 líneas de código de producción dentro del alcance (Domain 257, Application 160, Presentation 215, Persistence 199, `app.php` y `public/index.php` 79, esquema 12). Se revisa a 150–200 líneas por hora, así que son **tres sesiones de 90 minutos**: (1) dominio y aplicación, (2) contrato y permisos, (3) persistencia, integridad y unidades.

## Alcance y dependencias estrictamente necesarias

| Dentro | Fuera |
|---|---|
| Reglas de `VitalSigns`, `VitalSignRule`, `RangeRule` y `ThresholdEvaluator` | Interfaz de usuario y prototipo (Semanas 7 a 12) |
| `RecordVitalSigns`, `GetVitalSign` y los puertos | Cliente HTTP (`src/Client`), salvo el contrato que consume |
| Rutas, permisos, `trace_id` y errores de `ApiKernel` | Corrección de mediciones y reconocimiento de alertas: están en el diseño, no en el código |
| `PdoVitalSignRepository`, la auditoría y el esquema de la medición | Módulos de admisión, pacientes y camas |

**Dependencias necesarias, y solo estas cuatro:**

| Dependencia | Por qué es necesaria | Se revisa que |
|---|---|---|
| `care_episodes` y `care_assignments` (solo lectura, `CareEpisodeReader`) | Decide el episodio abierto, el médico tratante y la edad del perfil | La consulta use asignación `active`, y que no se revele si el episodio existe |
| Comprobador de permisos RBAC (`PermissionChecker`) | Autoriza la acción de la ruta | Si no responde, la ruta falla cerrada |
| Canal de notificación (`AlertNotifier`) | Avisa a los tratantes | Su fallo no cambie el resultado de una medición ya guardada |
| `local_access_audits` | Traza de cada escritura | Solo guarda identificadores, nunca valores clínicos |

Cualquier otra dependencia que aparezca en la lectura es un hallazgo de tipo *Mejora* («dependencia innecesaria»).

## Roles

| Rol | Responsabilidad | Quién |
|---|---|---|
| **Autor** | Presenta el artefacto, responde preguntas, no defiende ni corrige en la reunión | Josué Fernando Hicho García |
| **Moderador** | Dirige la reunión, controla el tiempo, decide si la sesión continúa; no revisa | Por designar: docente o compañero que no sea el autor |
| **Revisor 1 — dominio y contrato** | Ítems C-01 a C-07 | Yamilet (revisora asignada en los PR) — por confirmar |
| **Revisor 2 — permisos, integridad y datos** | Ítems C-08 a C-15; las preguntas de base de datos van a quien coordina ASII-09 | Por confirmar; Javier (ASII-09) como consultor de base de datos |
| **Secretario** | Registra los hallazgos con el formato; puede ser el moderador | Por designar |

El autor no puede ser moderador ni revisor de su propio código. Quienes figuran como «por confirmar» no han aceptado todavía.

## Proceso y calendario

| Fase | Actividad | Duración | Salida |
|---|---|---|---|
| 1. Entrada | El moderador comprueba los criterios de entrada | 15 min | Acta de entrada |
| 2. Introducción | El autor explica el flujo y los puntos de atención | 15 min | Dudas resueltas |
| 3. Preparación | Cada revisor lee con el [checklist](./checklist.md) y anota hallazgos | 2 h por revisor, 2 días antes | Hallazgos previos |
| 4. Reunión | Tres sesiones de 90 minutos; se lee el hallazgo, se clasifica y no se discute la solución | 4,5 h | Lista consolidada |
| 5. Corrección | El autor corrige en PR aparte, de menos de 400 líneas | según severidad | PR vinculados |
| 6. Seguimiento | El moderador verifica cada corrección | 30 min | Acta de salida |

## Criterios de entrada

La revisión no empieza mientras no se cumpla **todo** lo siguiente:

1. Los artefactos del cuadro anterior están congelados en el commit indicado.
2. Las cuatro suites de pruebas y las [sondas](./evidencia/sondas.php) terminan con código de salida 0 en un equipo ajeno al autor.
3. El checklist, el formato de hallazgos y los puntos de atención del autor están entregados a los revisores.
4. Los revisores y el moderador están confirmados, y ninguno es el autor.
5. No hay un PR abierto que modifique esos archivos sin haber sido anunciado.
6. Cada revisor dispone de 2 horas de preparación.

## Criterios de salida

La revisión termina cuando:

1. Los 15 ítems del checklist están marcados por el revisor que los tiene asignados.
2. Cada hallazgo está clasificado (tipo y severidad) y tiene responsable; los Mayores, también fecha.
3. **No queda ningún Crítico abierto**: o se corrigió y el moderador repitió la evidencia, o la decisión es *Re-revisión*.
4. Hay acta con la decisión (**Aprobada**, **Aprobada con condiciones** o **Re-revisión**) y firmas del moderador y del autor.
5. Se registró el tiempo de preparación y la tasa de hallazgos por hora, para mejorar la siguiente revisión.

## Puntos de atención del autor

Ocho observaciones reproducidas contra el código real, ligadas a los ítems C-01, C-04, C-06, C-08 a C-13. Las tres que el autor considera más graves, y que probablemente salgan Críticas o Mayores:

| # | Resumen | Evidencia (sonda) |
|---|---|---|
| P-2 | Un fallo del canal de notificación tras el `COMMIT` responde `500 VITAL_SIGN_PERSISTENCE_ERROR` con la medición ya guardada: el médico reintenta y duplica | `500`, 2 eventos de auditoría |
| P-7 | Sin unidad declarada, glucosa 5,5 (normal en mmol/L) da una alerta crítica falsa | `201 ALERT_GENERATED critical` |
| P-3 | Una `measured_at` de 2099 se acepta con `201` | `201 OK` |

El detalle de las ocho está en el [checklist](./checklist.md#puntos-de-atención-declarados-por-el-autor).

## Cómo verificar

```bash
php docs/signos-vitales/semana-13/evidencia/sondas.php
```

Resultado con PHP 8.3.33 (código de salida 0). La sonda describe el comportamiento actual; **no** es una prueba que deba pasar, es evidencia de lo que se ve hoy:

```text
P-1  C-10     la palabra «tenant» aparece 0 veces en src/ (RNF-SV-01 exige X-Tenant-ID)
P-2  C-11     canal de notificación caído: respuesta 500 VITAL_SIGN_PERSISTENCE_ERROR, pero la medición quedó guardada (signo_vital.registrado) y auditada: 2 eventos
P-3  C-04     measured_at futura: 201 OK
P-3  C-04     measured_at de 1900: 201 OK
P-4  C-08     GET de un id inexistente: 404; de uno existente sin asignación: 403 (distinguibles: permite enumerar ids)
P-5  C-09     cualquier cliente que envíe X-User-ID: dra.lopez lee la medición: 200 (no hay JWT ni firma en el prototipo)
P-6  C-12     schema.sql difiere de 000401 en 3 puntos comprobados (recorded_by, medical_record_id ausente, estado inicial 'validated'); está declarado como esquema simplificado
P-7  C-13     glucosa 5,5 (un valor normal en mmol/L) se evalúa como mg/dL: 201 ALERT_GENERATED critical (alerta crítica falsa)
P-8  C-01     FC 138,5 se acepta (la columna heart_rate es integer); SpO2 0 se rechaza: 422 VITAL_SIGN_IMPLAUSIBLE (la base lo admite)
```

## Archivos

- [Checklist de 15 ítems](./checklist.md) y puntos de atención del autor.
- [Formato de hallazgos](./formato-hallazgos.md) con un ejemplo diligenciado.
- [Sondas](./evidencia/sondas.php)
- [Declaración de uso de IA](./DECLARACION_IA.md)
