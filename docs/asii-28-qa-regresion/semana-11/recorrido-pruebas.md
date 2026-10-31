# Recorrido y comprobaciones nuevas — 29/10/2026

El verificador abre el HTML local en Edge mediante DevTools. Interacciones de
formulario usan eventos DOM del navegador (requestSubmit/click), no participantes.
Tab/Enter se envían como eventos de teclado; resultados se leen del DOM visible.

| Paso / pantalla | Acción y esperado | Observado | Estado / requisitos |
|---|---|---|---|
| 1 Panel | Entrar; seis vistas, una activa, navegación y etiquetas válidas | Seis vistas, cero IDs duplicados, enlaces rotos o controles sin label | Demostrado: estructura; no RF de panel inventado |
| 2 Menú | Tab de Panel a Campañas, Enter y foco en encabezado | #campaigns, foco campaigns-title; outline 3 px | Demostrado: navegación local |
| 3 Campañas | Buscar inexistente y limpiar; abrir detalle | Vacío visible y recuperación; detalle de Campaña A accesible | Simulado: RF-02 |
| 4 Detalle | Abrir caso asociado | Ejecución muestra C-demo-01 con sus pasos/esperado | Simulado: RF-02/03, RNF-01 |
| 5 Nuevo caso | Enviar vacío; error y foco | Resumen case-errors enfocado | Demostrado validación local; RF-01 |
| 6 Nuevo caso | Completar propósito/pasos/esperado; simular alta/asociación | C-demo-2 visible en detalle y aviso de simulación | Simulado: RF-01/02; sin PHP |
| 7 Ejecución | Enviar incompleto | run-errors visible/enfocado, sin nuevo resultado | Demostrado validación local; RF-03/05 |
| 8 Evidencia | Abrir/cerrar diálogo | Ejemplo textual evidencia-demo-01, sin archivo real | Simulado: RF-05 |
| 9 Ejecución | Observación, Fallido y evidencia ficticia; registrar | E-demo-1 fallido confirmado y acción de defecto disponible | Simulado: RF-03/04, RNF-02/05 |
| 10 Defecto | Enviar descripción/clasificación vacías | Resumen defect-errors enfocado | Demostrado validación local; RF-06 |
| 11 Defecto | Completar y registrar | D-demo-1 vinculado a E-demo-1 | Simulado: RF-06/07, RNF-01 |
| 12 Cancelación | Cancelar formulario de defecto ya vacío | No elimina ejecución ni defecto confirmados | Demostrado acotado; RF-08/RNF-03 indirectos |
| 13 Aprobado | Registrar segunda ejecución aprobada e intentar defecto | Campos de defecto bloqueados; historia previa sigue en memoria | Simulado: RF-04/06/08, RNF-03/05 |
| 14 Panel | Regresar y revisar | 2 casos, 2 ejecuciones y 1 defecto simulados | Simulado: RF-07/08 |
| 15 Reflow | Medir 1280/390/320 | Sin scroll horizontal en las tres muestras | Demostrado: layout de prototipo |
| 16 Recarga | Recargar página | 1 caso inicial, 0 ejecuciones y 0 defectos | Demostrado: no hay persistencia |
| 17 Runtime | Observar excepciones JS durante recorrido | Ninguna observada | Demostrado en recorrido acotado |

Los errores/success son interacción del prototipo, no reglas backend certificadas.
RNF-04 (sustitución del contrato de ejecución) queda pendiente: JavaScript local
no prueba adaptadores PHP. RNF-01/02/03/05 solo tienen simulación, sin integridad
persistente ni validación de comparación manual. No se comparan strings para
juzgar aprobado/fallido: lo declara el analista de demostración.

## Hallazgos de Semana 9 incorporados

UX-01: abrir caso va a ejecución seleccionada; crear es acción diferente.
UX-02: cancelar y consultar trazabilidad son controles separados.
UX-03: etiquetas Obligatorias explícitas. UX-04: errores asociados y resumen
enfocable verificado, sin lector de pantalla. UX-05: referencia ficticia y diálogo
que explica su carácter de ejemplo, sin repositorio privado. UX-06: sigue pendiente
recuperar respuesta perdida/idempotencia real; aquí no existe transporte.
No se cierran hallazgos de las ramas anteriores desde este PR.

Pendientes: confirmación de descarte con texto escrito no se ejercitó en el
recorrido automatizado; sí cancelación con formulario vacío. Seguridad, estados
de campaña, severidades definitivas, archivos, zoom y tecnologías asistivas no evaluados.
