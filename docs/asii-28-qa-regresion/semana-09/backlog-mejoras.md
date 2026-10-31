# Backlog priorizado y trazabilidad

Estado de B01–B06: **Propuesto, no implementado**. Responsable de ejecución de cada
fila: **Pendiente de asignación**; Madelin Cerón mantiene registro/seguimiento.
P1: riesgo de pérdida o barrera de completar tarea; P2: claridad/orientación.
Esfuerzo relativo S = cambio de diseño acotado; M = política/estado más validación.
No son horas estimadas ni asignaciones aceptadas por otros integrantes.

| ID / hallazgo | Mejora concreta | Prioridad / impacto / esfuerzo | Responsable | Criterio de aceptación comprobable | Estado |
|---|---|---|---|---|---|---|
| B06 / UX-06 | Estado incierto y acción Consultar resultado | P1 / evita repetición o pérdida futura / M | Pendiente de asignación | Ante respuesta perdida, prototipo no reenvía POST automáticamente, conserva datos y ofrece consulta con referencia; cancelar no borra registro confirmado | Propuesto |
| B04 / UX-04 | Relación de errores y recuperación de foco | P1 / acceso a corregir entrada / M | Pendiente de asignación | Prototipo activo relaciona error con campo, marca invalidez solo al fallar y enfoca resumen; teclado y lector identifican campo/mensaje sin perder texto | Propuesto |
| B05 / UX-05 | Ayuda para referencia privada de evidencia | P1 / permite completar ejecución / M | Pendiente de asignación | Contrato aprobado; texto con ejemplo ficticio y forma de obtenerlo; tarea de adjuntar referencia puede realizarse sin ruta local/datos sensibles | Propuesto |
| B01 / UX-01 | Separar consulta de caso y creación | P2 / reduce desvíos de navegación / S | Pendiente de asignación | Todo enlace Consultar abre detalle del caso indicado y conserva campaña; crear se rotula Diseñar nuevo caso | Propuesto |
| B02 / UX-02 | Separar Cancelar y Ver trazabilidad | P2 / decisión predecible / S | Pendiente de asignación | Dos acciones rotuladas; solo cancelar puede solicitar descarte; consultar no elimina descripción ni ejecución | Propuesto |
| B03 / UX-03 | Explicar campos obligatorios | P2 / menor ambigüedad / S | Pendiente de asignación | Leyenda visible y etiquetas inequívocas; no se requiere interpretar * para reconocer obligatoriedad | Propuesto |

Orden dentro de prioridad: primero impacto/riesgo; cambios rápidos P2 pueden
realizarse en paralelo tras revisión. No aplicar correcciones sobre la rama
pendiente de Semana 8 desde este PR: este incremento solo documenta evaluación.

## Vínculos a requisitos auténticos de Semana 2

| Requisito | Pantalla | Hallazgo / mejora | Evidencia / estado de la relación |
|---|---|---|---|
| RF-01 Diseñar caso | 03/04 | UX-01/03/04 → B01/03/04 | Enlace a creación, asteriscos y párrafo .error; núcleo CLI existe, UX pendiente |
| RF-02 Campaña/casos | 04 | UX-01 → B01 | Consultar diseño no identifica caso seleccionado; diseño de navegación, asociación real pendiente |
| RF-03 Ejecutar/observar | 05 | UX-04/06 → B04/06 | Entrada/error y ausencia de recuperación incierta; riesgo, no fallo de ejecución confirmado |
| RF-04 Resultado | 05 | UX-06 → B06 | Falta visual de comprobación tras guardar; resultado real pendiente |
| RF-05 Evidencia | 05 | UX-05 → B05 | Placeholder privado sin ejemplo; contrato pendiente |
| RF-06 Defecto desde fallo | 06 | UX-02/04 → B02/04 | Cancelación mezclada y error sin vínculo; no demuestra defecto de dominio |
| RF-07 Trazabilidad | 04/06 | UX-01/02 → B01/02 | Destinos/acciones ambiguos; consulta de grafo no implementada |
| RF-08 Historia | 05/06 | UX-06 → B06 | Riesgo de reenvío; sin pérdida histórica reproducida |
| RNF-01 Integridad | 04/06 | UX-01/02 → B01/02 | Relación UX indirecta: preservar contexto; integridad de almacenamiento no evaluada |
| RNF-02 Consistencia | 05 | UX-06 → B06 | Comprobación de resultado como mitigación UX, no prueba de coherencia de dominio |
| RNF-03 Historia inmutable | 05/06 | UX-06 → B06 | Evitar repetir operación incierta; inmutabilidad real no verificable |
| RNF-04 LSP | 03/05 | UX-05 → B05 | Indirecta: ayuda no exige formato exclusivo por adaptador; no valida contrato de ejecución |
| RNF-05 Resultado claro | 05 | UX-06 → B06 | Separar resultado de negocio aprobado/fallido de estado de transporte incierto; pendiente |

Ningún hallazgo crea RF/RNF nuevos ni certifica los existentes. Accesibilidad y
usabilidad son criterios de esta evaluación; no se inventa un RNF de WCAG en Semana 2.

## Protocolo de tareas para participantes — pendiente, sin resultados

Solicitar consentimiento y participantes autorizados sin asignar nombres reales.
En un prototipo habilitado, pedir localizar campaña/caso, identificar campos
obligatorios, registrar observación/evidencia y explicar Cancelar frente a consultar
trazabilidad; simular pérdida de respuesta sin datos clínicos. Alternar teclado y
puntero según preferencias; incorporar lector de pantalla con usuario autorizado
cuando corresponda. No usar esta maqueta disabled para medir éxito de guardado.
Registrar errores/observaciones y tiempos solo después de sesiones reales, sin
presuponer satisfacción. Acordar antes tareas, muestras, privacidad y criterio de éxito.
Estado: no reclutado/no ejecutado. Semana 10 debe priorizar revisión del backlog,
prototipo interactivo, zoom/targets/anuncios y evaluación asistiva; aprobación pendiente.
