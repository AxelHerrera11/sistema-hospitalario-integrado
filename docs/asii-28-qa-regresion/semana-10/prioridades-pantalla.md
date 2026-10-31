# Prioridades por pantalla y trazabilidad

Actor respaldado: Analista QA; responsable de corrección consulta defectos según
Semana 1, sin permisos técnicos inventados. Datos, catálogos y operaciones futuras
requieren contrato/autorización; no se asignan personas reales.

| Pantalla / objetivo | Información esencial primero | Secundaria / reorganización y contracción | Primaria / secundaria | Justificación |
|---|---|---|---|---|
| M01 Consultar tareas | Casos/defectos pendientes y campaña | Actividad reciente en details; contadores [n] en texto corto | Consultar campañas / diseñar nuevo caso | Priorizar tarea, no una tabla/dash saturado |
| M02 Localizar campaña/caso | Búsqueda, tarjeta con estado/cantidad y caso seleccionado | Filtros y trazabilidad dentro de details; nunca tabla ancha | Consultar caso A y ejecutar / diseñar nuevo caso, volver | Consultar no lleva a creación; contexto del caso en M04 |
| M03 Diseñar caso | Propósito, pasos, esperado y obligatoriedad | Requisito/precondiciones como contexto propuesto desplegable | Guardar caso / cancelar nuevo caso | No ocultar RF-01; teclado/táctil con labels superiores |
| M04 Registrar ejecución | Caso/campaña, progreso, pasos/esperado, observación/resultado/evidencia | Recuperación incierta en details; ayuda de evidencia junto al campo | Registrar resultado / cancelar ejecución, ver defecto de ejemplo | Separar resultado de negocio y respuesta de transporte; no reenvío automático |
| M05 Registrar defecto | Origen fallido, descripción y error | Severidad por acordar; evidencia/estado en texto; recuperación desplegable | Guardar defecto / cancelar defecto / ver trazabilidad | No mezclar cancelación con consulta ni eliminar ejecución al cancelar |

## Hallazgos originales y alcance de mejora

Fuente: [seis hallazgos de Semana 9, commit 3161aa92](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/3161aa928478678b69d1fade23ad6264d7c47ef8/docs/asii-28-qa-regresion/semana-09/hallazgos.md).
Se consulta también [maqueta de Semana 8](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/blob/508deb1b276d574cec5c6f179b3f067d875b8572/docs/asii-28-qa-regresion/semana-08/wireframes.html), sin duplicar sus commits.

| Hallazgo / problema | Solución propuesta en Semana 10 | Pantalla / evidencia verificable | Estado de validación |
|---|---|---|---|
| UX-01 Consulta conducía a crear | Acción Consultar caso A y ejecutar → caso A; crear es Diseñar nuevo caso | M02 a #mov-4, distinto de #mov-3 | Rotulado/destino comprobables; consulta de datos real pendiente |
| UX-02 Cancelar/consultar combinados | Enlaces separados y destinos distintos | M05 Cancelar defecto→M04; Ver trazabilidad→M02 | Presentación atendida; descarte/conservación de texto sin implementación |
| UX-03 Asterisco sin leyenda | Texto Obligatorio y explicación visible, sin asteriscos | M03/M04/M05 labels | Presentación atendida; no valida formulario real |
| UX-04 Error sin relación programática | IDs de error y aria-describedby; ejemplos inválidos explícitos | M03 steps-error; M04 evidence-help/error; M05 description-error | Parcial: asociaciones inspeccionables, lector/foco de error pendientes |
| UX-05 Referencia de evidencia ambigua | Ayuda para obtener referencia y ejemplo ficticio | M04 evidence/evidence-help | Parcial: formato/repositorio privado todavía por acordar |
| UX-06 Respuesta de guardado incierta | Ejemplo de recuperación, referencia de operación y acción Consultar resultado | M04/M05 details; botón disabled | Parcial: no existe consulta, idempotencia ni reintento implementados |

No se cierran hallazgos de Semana 9 ni se corrige su rama. Todo es diseño nuevo.

## Requisitos auténticos de Semana 2

| Pantalla | RF/RNF relacionado | Objetivo móvil / UX | Criterio de aceptación del diseño | Estado |
|---|---|---|---|---|
| M01 | RF-02, RF-07 | Acceder a campañas/trazabilidad; UX-01 indirecto | Acciones claras y resumen sin cifras reales | Propuesta visual |
| M02 | RF-02, RF-07; RNF-01 | Conservar contexto de caso/campaña; UX-01/02 | Tarjeta sin tabla horizontal; consulta y creación distintas | Propuesta visual, sin integridad real probada |
| M03 | RF-01; RNF-04 indirecto | Diseñar con el mismo contrato; UX-03/04 | purpose/steps/expected visibles, sin formato exclusivo por adaptador | Solo núcleo CLI existente; móvil propuesto |
| M04 | RF-03, RF-04, RF-05, RF-08; RNF-02, RNF-03, RNF-05 | Observar, comparar y conservar historia; UX-04/05/06 | Pasos/esperado legibles, ayuda/error y estado incierto distinto de aprobado/fallido | Propuesto, sin ejecución/almacenamiento reales |
| M05 | RF-06, RF-07; RNF-01 | Defecto desde origen fallido y trazabilidad; UX-02/03/04/06 | Descripción/errores y acciones inequívocas con referencia de origen | Propuesto; no valida regla de dominio de defecto |

La tabla cubre los ocho RF y cinco RNF sin crear IDs nuevos. RNF-04 sigue referido
al contrato completo de ejecución pendiente, no queda acreditado por una maqueta.
Severidad, precondiciones, requisito persistido, responsable y estados de campaña/
defecto son extensiones o catálogos por formalizar, no columnas existentes.
