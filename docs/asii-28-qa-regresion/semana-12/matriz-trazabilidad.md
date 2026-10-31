# Matriz de trazabilidad RF/RNF

Fuente normativa del módulo: [Semana 2](../semana-02/rf-rnf-criterios.md), leída antes
de construir las trece filas. Los criterios siguientes son síntesis, no requisitos nuevos.
Estados: **I** implementado y probado (alcance indicado); **S** simulado; **D** documentado/propuesto; **P** pendiente.
Componentes de ejecución/campaña/defecto son propuestas de Semana 3, no clases PHP existentes.
WF-01 panel, WF-02 campañas, WF-03 caso, WF-04 detalle, WF-05 ejecución, WF-06 defecto.
HTML usa respectivamente #panel/#campaigns/#case/#detail/#execution/#defect, sin equivaler a rutas HTTP.

| ID | Criterio de aceptación sintetizado | Componente | Pantalla / flujo | Evidencia / validación nueva | Estado y brecha |
|---|---|---|---|---|---|
| RF-01 | Guardar caso completo; rechazar sin pasos/esperado | CaseController → DesignCase → ManualCase → CaseRepository | WF-03 → WF-04; crear caso | PHP 17/17 incluye vacíos/lectura; demo 201/422; HTML error/alta | I en CLI; S en HTML; sin HTTP ni almacenamiento durable demostrado |
| RF-02 | Campaña conserva asociación de casos seleccionados | CrearCampania/AsociarCaso propuestos | WF-02 → WF-04 → WF-03 | HTML alta añade C-demo-2 a campaña predefinida | S parcial; crear campaña/selección entre campañas P; diseño D |
| RF-03 | Ejecución vinculada a campaña/caso con observaciones/comparación | RegistrarEjecucion propuesto | WF-04 → WF-05 | HTML abre contexto, rechaza incompleto y crea E-demo-1 | S; dominio/persistencia de ejecución P |
| RF-04 | Coincidencia implica aprobado; diferencia, fallido | EjecucionManual propuesta | WF-05 → WF-06 si fallo | HTML registra ambos resultados declarados | S; comparación semántica no comprobada, backend P |
| RF-05 | Evidencia identificada y consultable desde ejecución | ReferenciaEvidencia propuesta | WF-05 diálogo y confirmación | HTML diálogo y referencia ficticia evidencia-demo-01 | S parcial; no repositorio privado ni adjunto real, P |
| RF-06 | Defecto ligado a fallo/caso/campaña; aprobado no genera defecto | RegistrarDefecto propuesto | WF-05 → WF-06 | HTML D-demo-1→E-demo-1; aprobado bloquea formulario | S; regla backend y vínculo persistente P |
| RF-07 | Consultar casos, ejecuciones, resultados, evidencia/defectos | ConsultarTrazabilidad propuesto | WF-04; volver desde WF-06 | HTML detalle/resumen y contadores; no prueba historial completo | S parcial; consulta histórica íntegra P |
| RF-08 | Nueva ejecución no altera anteriores | EjecucionManual/RepositorioQA propuestos | WF-05 reejecución → WF-04 | HTML 2 ejecuciones/1 defecto; suite no compara todos los campos previos | S parcial; preservación íntegra no validada, backend P |
| RNF-01 | Navegar defecto→ejecución→caso/campaña y su evidencia | Relaciones de dominio propuestas | WF-06 → WF-04/05 | Confirmación HTML liga D-demo-1/E-demo-1 y contexto | S parcial; integridad referencial persistente P |
| RNF-02 | Resultado coherente; sin observado/esperado no se guarda | EjecucionManual propuesta | WF-05 error/confirmación | HTML incompleto rechazado y declaración de resultado | S parcial; no prueba coherencia semántica, dominio P |
| RNF-03 | Segunda ejecución deja idénticos datos de primera | RepositorioQA/ejecuciones propuestos | WF-05 → historial WF-04 | Contadores HTML no demuestran igualdad histórica; recarga borra | D; criterio completo P, no aprobado |
| RNF-04 | Misma batería pasa para implementaciones de registro de ejecución | RegistroEjecucionManual conceptual; CaseRepository real distinto | WF-05; sin pantalla que pruebe LSP | PHP verifica InMemory/PDO de casos, no ejecuciones | D/P para requisito; I solo analogía acotada de casos |
| RNF-05 | Cada ejecución terminada tiene un resultado único aprobado/fallido | EjecucionManual propuesta | WF-05 confirmación; panel | HTML selección única y ambos caminos comprobados | S; invariante backend P |

## Fuentes de diseño y evidencia

- [Arquitectura propuesta](../semana-03/arquitectura.md) y [capas/Repository reales](../semana-04/README.md).
- [Presentación](./presentacion.md): enlaces fijados a componentes, user flow, wireframes, responsive y hallazgos de PR abiertos.
- [Recorrido](./recorrido-prototipo.md): versión exacta HTML/script, método, 17 comprobaciones nuevas y cuatro capturas publicadas.
- UX-01/02/03 atendidos en navegación/instrucciones locales; UX-04/05 parcialmente y UX-06 pendiente de contrato/recuperación real. No se cierran hallazgos previos.

La matriz no convierte las simulaciones en cumplimiento de aceptación productiva.
Se requiere revisión de Madelin Cerón sobre sus siguientes entregas y revisión externa
por asignar; no se atribuyen aprobaciones ni responsabilidades a otros estudiantes.
