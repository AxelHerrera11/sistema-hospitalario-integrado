# Notas SOAP y diagnósticos — Semana 13: plan de revisión técnica formal

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** plan de revisión, checklist de 15 ítems, roles, criterios de entrada y salida y formato de hallazgos.

## Ficha de la asignación

- **Semana:** Semana 13 — Calidad del software y revisiones técnicas formales
- **Tarea individual:** Prepare una revisión técnica formal de Notas SOAP y diagnósticos, enfocada en reglas de dominio, contrato API, permisos/tenant, integridad de notas SOAP, diagnósticos, autoría clínica, versionado y expediente y dependencias estrictamente necesarias para «registro de nota SOAP y asociación de diagnóstico clínico».
- **Entregable / evidencia:** plan de revisión, checklist de 12–15 ítems, roles de autor/moderador/revisores, criterios de entrada/salida y formato de hallazgos.
- **Issue:** Refs #22
- **Alcance:** **preparación** de la revisión. No se corrige código: los hallazgos de la reunión se registran con el [formato](./formato-hallazgos.md) y se corrigen en PR aparte.

## Objetivo

Que otra persona, con este documento y sin hablar con el autor, pueda revisar el flujo que **escribe datos clínicos** (registrar la nota y asociar un diagnóstico) y decidir si se puede firmar como «aprobado». El tipo de revisión es una **inspección formal** con preparación individual, reunión con moderador y seguimiento de las correcciones.

## Objeto de revisión

| Artefacto | Ubicación | Estado |
|---|---|---|
| Código del micro-HIS | `micro-his/notas-soap/` en la rama de la Semana 5 (PR #405, commit `93b7b47`) | Congelado para la revisión |
| Contrato API v1 y OpenAPI | `docs/notas-soap/semana-05/` (PR #374, commit `9028a6f`) | Congelado |
| Esquema real | `database/migrations/hospital/2026_08_11_000400_create_medical_record_tables.php` | Se revisa solo lo que toca la nota y el diagnóstico |
| Pruebas | `tests/run.php` y `tests/api.php` | Se ejecutaron el 2026-10-20 con PHP 8.3.33: pasan |

**Tamaño:** unas 560 líneas de lógica (Domain 126, Application 187, Presentation 233, esquema 18) más 314 de persistencia y 140 del cliente. Se revisa a 150–200 líneas por hora, así que son **dos sesiones de 90 minutos**: (1) dominio, aplicación y contrato; (2) permisos, persistencia y base de datos.

## Alcance y dependencias estrictamente necesarias

| Dentro | Fuera |
|---|---|
| Reglas de `SoapSections`, `Diagnosis`, `DiagnosisPolicy` | Firma y corrección: están en el contrato, no en el código |
| `RegisterSoapNote`, `AssociateDiagnosis`, `GetSoapNote` y `TreatingPhysicianGuard` | Interfaz de usuario (Semanas 7 a 12) |
| Rutas, permisos, `trace_id` y errores de `ApiKernel` | Cliente HTTP (`src/Client`), salvo el contrato que consume |
| `PdoClinicalNoteStore`, auditoría y triggers de la nota y el diagnóstico | Módulos de pacientes, camas y admisión |

**Dependencias necesarias, y solo estas cuatro:**

| Dependencia | Por qué es necesaria | Se revisa que |
|---|---|---|
| `care_assignments` (solo lectura) | Decide el médico tratante del episodio | La consulta use estado `active` y la vigencia |
| `icd10_codes` (solo lectura) | Valida el código del diagnóstico | Un catálogo caído nunca guarde nada |
| Comprobador de permisos RBAC (`PermissionChecker`) | Autoriza la acción de la ruta | Si no responde, la ruta falla cerrada |
| `local_access_audits` | Traza de cada escritura | Solo guarda identificadores, nunca texto clínico |

Cualquier otra dependencia que aparezca en la lectura es un hallazgo de tipo *Mejora* («dependencia innecesaria»).

## Roles

| Rol | Responsabilidad | Quién |
|---|---|---|
| **Autor** | Presenta el artefacto, responde preguntas, no defiende ni corrige en la reunión | Josué Fernando Hicho García |
| **Moderador** | Dirige la reunión, controla el tiempo, decide si la sesión continúa; no revisa | Por designar: docente o compañero que no sea el autor |
| **Revisor 1 — dominio y contrato** | Ítems C-01 a C-06 | Yamilet (revisora asignada en los PR) — por confirmar |
| **Revisor 2 — permisos, integridad y datos** | Ítems C-07 a C-15; las preguntas de base de datos van a quien coordina ASII-09 | Por confirmar; Javier (ASII-09) como consultor de base de datos |
| **Secretario** | Registra los hallazgos con el formato; puede ser el moderador | Por designar |

El autor no puede ser moderador ni revisor de su propio código. Quienes figuran como «por confirmar» no han aceptado todavía.

## Proceso y calendario

| Fase | Actividad | Duración | Salida |
|---|---|---|---|
| 1. Entrada | El moderador comprueba los criterios de entrada | 15 min | Acta de entrada |
| 2. Introducción | El autor explica el flujo y los puntos de atención | 15 min | Dudas resueltas |
| 3. Preparación | Cada revisor lee con el [checklist](./checklist.md) y anota hallazgos | 2 h por revisor, 2 días antes | Hallazgos previos |
| 4. Reunión | Dos sesiones de 90 minutos; se lee el hallazgo, se clasifica y no se discute la solución | 3 h | Lista consolidada |
| 5. Corrección | El autor corrige en PR aparte, de menos de 400 líneas | según severidad | PR vinculados |
| 6. Seguimiento | El moderador verifica cada corrección | 30 min | Acta de salida |

## Criterios de entrada

La revisión no empieza mientras no se cumpla **todo** lo siguiente:

1. Los artefactos del cuadro anterior están congelados en el commit indicado.
2. Las dos suites pasan: `php micro-his/notas-soap/tests/run.php` y `php micro-his/notas-soap/tests/api.php`.
3. El OpenAPI es válido: `npx @apidevtools/swagger-cli@4.0.4 validate docs/notas-soap/semana-05/openapi.yaml`.
4. El autor entregó su autoevaluación con el checklist y los [puntos de atención](./checklist.md#puntos-de-atención-declarados-por-el-autor).
5. Los revisores recibieron el material al menos 2 días antes y confirmaron.
6. No hay cambios pendientes sobre los archivos revisados.

## Criterios de salida

La revisión termina como **Aprobada** si se cumple todo lo siguiente:

1. Los 15 ítems del checklist tienen resultado (Cumple, No cumple, No aplica con motivo).
2. **0 hallazgos Críticos** abiertos y **0 Mayores sin responsable ni fecha**.
3. Cada Crítico corregido fue reverificado por el moderador con la prueba que lo reproduce.
4. El acta quedó firmada por el moderador y los revisores.

Se repite la inspección completa (*re-revisión*) si hay **más de 3 hallazgos Críticos** o **más de 8 Mayores** en esta ronda, o si las correcciones cambian más del 20 % de las líneas revisadas.

## Métricas que se registran

Hallazgos por severidad y por ítem, hallazgos por cada 1000 líneas, líneas revisadas por hora, esfuerzo de preparación por revisor y tiempo de corrección. Sirven para decidir si el proceso funcionó, no para evaluar personas.

## Archivos

- [Checklist de 15 ítems](./checklist.md) con los puntos de atención del autor
- [Formato de hallazgos](./formato-hallazgos.md), con un ejemplo completo
- [Declaración de uso de IA](./DECLARACION_IA.md)

> Los enlaces a las Semanas 5 y 6 resuelven cuando los PR #374, #404 y #405 estén integrados en `develop`; esta rama solo añade el plan de la Semana 13.
