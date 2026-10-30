# Reglas de interacción y trazabilidad UX

Reglas propuestas, no controles ya implementados. Actor: Analista QA para WF-01–06;
responsable de corrección puede consultar WF-06 según Semana 1. Permisos técnicos
y ámbito se acordarán antes de exponer datos, sin inventar autorización vigente.

| Pantalla | Entrada y acción principal | Navegación / confirmación / resultado | Validación, vacío, error y cancelación |
|---|---|---|---|
| WF-01 | Consulta de actividad autorizada; abrir campaña/diseñar caso | WF-02/03/06; resultados [n] se sustituyen por datos solo en implementación futura | Vacío con orientación; error legible y reintento; sin cambios que descartar |
| WF-02 | Texto/filtros; abrir campaña | WF-04, volver WF-01; no confirmar lectura | Mantener filtros en error; sin resultados ofrece limpiar; cancelar búsqueda vuelve sin modificar campaña |
| WF-03 | purpose/steps/expected; guardar caso | Éxito muestra ID; volver panel o continuar asociación separada desde WF-04 | Campos vacíos/espacios rechazados; requisito/precondiciones no se envían al núcleo actual. Error conserva entrada; cancelar pide descarte si hay cambios |
| WF-04 | Campaña y selección de caso; consultar/ejecutar | WF-03 consulta de diseño o WF-05 ejecución; asociación confirma vínculo | Vacío ofrece asociar caso; sin permiso oculta datos/acciones; error permite lectura de nuevo. Volver al listado no guarda nada |
| WF-05 | Observaciones/comparación/evidencia; confirmar | Éxito muestra ID nuevo y resultado; aprobado vuelve WF-04, fallido permite WF-06 | Sin observación/evidencia no confirma; coherencia observado/esperado; cancelar confirma descarte. Si falla guardado, conservar entrada y comprobar estado antes de reenviar |
| WF-06 | Ejecución fallida, descripción, evidencia vinculada; guardar | Éxito muestra ID/vínculo; WF-04 trazabilidad, WF-05 origen | No aceptar origen aprobado; severidad/estado sin catálogo definitivo. Error no borra descripción; cancelar defecto no elimina ejecución fallida ya registrada |

## Criterios generales propuestos

Mismos nombres Guardar/Cancelar/Volver, encabezados numerados y orden de información:
contexto → pasos/datos → acción → confirmación. Campos con etiquetas visibles y
mensajes concretos junto al error; no comunicar aprobado/fallido solo por color.
Foco visible, orden de teclado lógico, controles nativos y título de destino tras
navegar. En futura UI, resumen de errores recibe foco y enlaza a campos; confirmar
guardado con mensaje de estado, sin simular éxito antes de respuesta del servidor.
Carga evita doble envío; errores de red no borran datos. Nunca reintentar POST de
forma automática sin idempotencia. Identidad/permisos y regla de negocio se aplican
en servidor, no únicamente en el diseño. No guardar borradores con datos sensibles.
El HTML estático deja botones deshabilitados para no fingir operaciones; solo sus
enlaces son navegables con teclado. No se certifica WCAG ni se afirma prueba humana.

## Trazabilidad con Semana 2

| ID | Descripción | WF | Acción / criterio de aceptación | Estado real |
|---|---|---|---|---|
| RF-01 | Diseñar caso completo | 03 | Guardar propósito, pasos y esperado; rechazar incompleto | CLI implementado, UX propuesta |
| RF-02 | Campaña/casos asociados | 02/04 | Asociar caso diseñado y conservar relación | Propuesto, sin funcionalidad |
| RF-03 | Ejecución/observaciones | 05 | Ejecutar caso asociado y registrar observación vinculada | Propuesto |
| RF-04 | Aprobado/fallido | 05 | Comparación coherente y un resultado terminado | Propuesto |
| RF-05 | Evidencia de ejecución | 05/06 | Consultar referencia ligada al origen | Propuesto |
| RF-06 | Defecto desde fallo | 06 | Guardar defecto solo desde ejecución fallida | Propuesto |
| RF-07 | Trazabilidad | 04/06 | Recorrer campaña/caso/ejecución/evidencia/defecto | Propuesto |
| RF-08 | Conservar ejecuciones | 04/05 | Registrar nueva ejecución sin sobrescribir anterior | Propuesto |
| RNF-01 | Integridad de vínculos | 04/05/06 | Mostrar origen real, no reconstruirlo por nombres | Propuesto |
| RNF-02 | Consistencia del resultado | 05 | No confirmar resultado incoherente con comparación registrada | Propuesto |
| RNF-03 | Historia inmutable | 04/05 | Historial previo consultable sin reescritura | Propuesto; mapa de casos no acredita ejecuciones |
| RNF-04 | Sustituibilidad LSP | 03/05 | UX no exige formato exclusivo por adaptador; mismo contrato | Puerto casos probado previamente, registro de ejecución pendiente |
| RNF-05 | Resultado inequívoco | 05 | Un único aprobado/fallido por ejecución terminada | Propuesto |

Severidad, estado de campaña/defecto, métricas/resumen, requisito/precondiciones
como campos persistidos y responsable requieren formalización; no reciben RF/RNF
inventados ni se representan como columnas/permisos existentes.
