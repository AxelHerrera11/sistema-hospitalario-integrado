# Hallazgos verificables

Fuente fijada y procedimiento en [README](./README.md). WF corresponde a section
#wf-1–6 del HTML de Semana 8. Estado de todos: **Abierto; sin corrección aplicada**.
Severidad cualitativa: alta = riesgo de pérdida/duplicación; media = desvío o barrera
de tarea; baja = ambigüedad menor. No es severidad de defectos del sistema clínico.

| ID / pantalla | Categoría / clasificación | Descripción y evidencia comprobable | Impacto / severidad | Recomendación |
|---|---|---|---|---|
| UX-01 / WF-04→03 | Usabilidad / defecto confirmado de navegación de maqueta | El enlace “Consultar diseño de ejemplo” tiene href=#wf-3; destino h2 “Diseñar caso manual”, con campos vacíos/placeholder y Guardar caso. No hay detalle de caso consultado | Se pierde la distinción consultar/crear y el contexto del caso; media | Cambiar destino a vista de detalle o rotularlo Diseñar nuevo caso; conservar origen en navegación |
| UX-02 / WF-06 | Usabilidad / defecto confirmado de rotulado | Un único enlace “Cancelar / consultar trazabilidad”, href=#wf-4, combina dos acciones. Las reglas describen cancelar sin borrar ejecución y consultar como intenciones distintas | No se anticipa si se descarta texto o solo se consulta; media | Separar Cancelar defecto y Ver trazabilidad; definir confirmación de descarte cuando corresponda |
| UX-03 / WF-03/05/06 | Usabilidad / defecto confirmado de instrucciones | Etiquetas purpose/steps/expected, observaciones/evidencia y descripción llevan *, pero el HTML no incluye leyenda “* obligatorio” ni equivalente. El DOM confirma ausencia | Convención sin explicación explícita, especialmente para usuarios nuevos; baja | Incluir leyenda visible y texto Obligatorio en las etiquetas relevantes, sin depender del asterisco |
| UX-04 / WF-03/05/06 | Accesibilidad / riesgo de diseño | Mensajes .error están en párrafos sin id ni asociación; DOM: cero aria-describedby/aria-errormessage/aria-invalid. Los controles están deshabilitados; no hubo validación real | Al activar controles podría perderse relación accesible campo-error y foco de recuperación; media | Diseñar asociación campo-error y resumen enfocable; usar aria-invalid solo cuando exista error, probar con lector de pantalla |
| UX-05 / WF-05 | Usabilidad / riesgo de diseño | run-evidence solo orienta “Referencia privada autorizada, no ruta local”; no hay ejemplo de referencia aceptada ni ayuda de origen. Reglas/flujo exigen evidencia antes de confirmar | El analista no sabe cómo obtener una referencia válida y puede bloquearse; media | Acordar contrato y mostrar ejemplo ficticio/ayuda para obtener referencia; separar instrucción de mensaje de error |
| UX-06 / WF-03/05/06 | Usabilidad / riesgo de diseño | Reglas de Semana 8 indican comprobar estado antes de reenviar un guardado incierto; HTML solo ofrece Guardar/Confirmar y Cancelar, sin estado incierto o acción para consultar resultado | En futuro prototipo con guardado, una respuesta perdida puede inducir repetición o descarte; alta | Diseñar estado Resultado pendiente de comprobar, referencia de operación y consulta segura; no prometer idempotencia inexistente |

Son **tres defectos observables de maqueta y tres riesgos**, no seis errores
funcionales ni seis incumplimientos normativos WCAG. UX-04 se limita al diseño,
no afirma que la ausencia de ARIA viole por sí sola WCAG ni que disabled sea fallo.
UX-06 no afirma pérdida real: no hubo backend ni escritura que reproducir.

## Aspectos no verificables (no cuentan para los seis hallazgos)

Validación/envío/guardado reales, recuperación de sesión, aislamiento de campañas,
lector de pantalla, zoom de navegador, tamaños de objetivos con sus excepciones,
anuncios dinámicos y satisfacción/tiempos de participantes: pendientes de material
interactivo y protocolo autorizado. La simultaneidad de vacío/error/éxito es un
catálogo explicado en Semana 8; no se registra como defecto para inflar hallazgos.
