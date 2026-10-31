# Guion de defensa preparada

Duración orientativa: 10–12 minutos incluyendo demostración; no acredita exposición realizada.
Autora y responsable de preparación: Madelin Jazmín Cerón Molina. Refs #12.

| Diapositiva / tiempo orientativo | Explicación para defender |
|---|---|
| 1 / 30 s | Presentar el módulo y distinguir evidencia ejecutada de diseño futuro. |
| 2 / 1 min | QA funcional compara comportamiento observado con requisitos/esperado. Una campaña agrupa casos seleccionados para revisar regresiones después de cambios. No exige datos clínicos reales. |
| 3 / 1 min | Un caso manual define propósito, pasos y esperado. La ejecución es un intento concreto con observaciones y resultado; repetirlo debe añadir historia. Recorrer la matriz sin atribuir cumplimiento completo al prototipo. |
| 4 / 1 min | Controller valida formato; DesignCase coordina; ManualCase impone definición completa; Repository abstrae guardar/buscar. El consumidor no cambia al sustituir InMemory por PDO. |
| 5 / 1 min | El flujo conserva contexto de campaña/caso. Los wireframes son estáticos; la navegación interactiva posterior implementa solo una demostración local. |
| 6 / 1 min | Explicar UX-01–06 y mejoras parciales. Foco y errores fueron comprobados técnicamente; faltan personas, lector de pantalla y zoom real. |
| 7 / 3–4 min | Seguir recorrido-prototipo.md. Resultado aprobado/fallido describe la ejecución; defecto es un registro vinculado al fallo, no otro estado de resultado. Mostrar evidencia ficticia y pérdida de memoria al recargar. |
| 8 / 1 min | Separar las dos suites de 17 y sus alcances. Mostrar que el CLI sí guarda/recupera durante el proceso; no existe API QA ni persistencia integrada del HTML. |

## Preguntas probables y respuestas

| Pregunta | Respuesta basada en evidencia |
|---|---|
| ¿Por qué no basta con una lista de defectos? | Sin vínculo al caso, campaña, observación y esperado no puede reconstruirse qué prueba originó el fallo; RF-07 propone esa trazabilidad. |
| ¿Crear un caso en HTML lo guarda en PHP? | No. Son arreglos JS locales; recargar los reinicia. RF-01 real se demuestra aparte con demo.php y SQLite en memoria. |
| ¿La campaña se crea desde el prototipo? | No. Hay una campaña ficticia predefinida; crear campañas arbitrarias y asociar casos entre campañas está pendiente. |
| ¿Qué significa aprobado? | El analista declara coincidencia entre observado y esperado. La selección HTML no demuestra verificación semántica automática ni garantiza RNF-02 completo. |
| ¿Aprobado permite registrar defecto por este flujo? | No; la comprobación HTML bloquea el formulario. No existe todavía regla equivalente en dominio backend de ejecuciones. |
| ¿Qué demuestra LSP? | La misma batería verifica garantías del CaseRepository con mapa/PDO: lectura, duplicados y errores equivalentes. RNF-04 exige registro de ejecuciones y permanece pendiente. |
| ¿Qué propone REST? | #1400 documenta POST/GET manual-cases, formatos y errores. No hay adaptador HTTP QA; status/body CLI no prueba respuestas de red. |
| ¿Qué aporta cada capa? | Presentation adapta entrada/salida; Application coordina; Domain valida invariantes/define puerto; Persistence implementa almacenamiento. Ver capas de Semana 4. |
| ¿Qué cambió entre wireframe y prototipo? | Consulta distinta de creación, cancelación separada, obligatoriedad visible, validación y mensajes con foco. No se modifica ni se cierra retrospectivamente el informe de Semana 9. |
| ¿Qué criterios UX se usaron? | Contexto visible, consistencia de etiquetas, prevención de errores, recuperación y navegación por teclado; responsive reduce columnas. Las pruebas técnicas no miden satisfacción de usuarios. |
| ¿Los 34 resultados prueban todo el módulo? | No se suman como una cobertura global: 17 PHP prueban el núcleo de casos; 17 HTML comprueban una simulación. RF-02–08 siguen sin backend. |
| ¿Se conserva todo el historial? | Los arreglos añaden ejecuciones en la sesión, pero la pantalla resume la última y recargar borra todo; falta consulta histórica íntegra y persistente de RF-07/08 y RNF-03. |
| ¿Qué falta para integrar? | Acordar propiedad tenant, permisos, políticas de evidencia/severidad y contratos; implementar backend/HTTP y pruebas de seguridad/contrato antes de conectar UI. No reutilizar datos sensibles. |
| ¿Qué se hará en Semana 13? | Priorizar esas decisiones, revisión autorizada de tareas y accesibilidad; planificar integración según aprobación, sin prometerla como terminada. |

Fuentes: [requisitos exactos](../semana-02/rf-rnf-criterios.md),
[Repository real](../semana-04/contratos-repositorio.md),
[presentación y enlaces a antecedentes](./presentacion.md), [matriz](./matriz-trazabilidad.md).
No se asignan revisores, correctores de otros módulos ni aprobaciones sin autorización.
