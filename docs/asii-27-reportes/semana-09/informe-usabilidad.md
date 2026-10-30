# Informe de evaluacion de usabilidad - ASII-27

**Objeto evaluado:** prototipo navegable de la Semana 8 (`../semana-08/prototipo/index.html`).
**Metodo:** inspeccion por la autora (recorrido cognitivo + evaluacion heuristica de Nielsen), escala
de severidad 0-4 (0 = no es problema, 4 = catastrofe). No se realizaron aun sesiones con usuarios
reales; el protocolo para hacerlo esta al final.

## 1. Recorrido cognitivo (tareas clave)

| Tarea | Objetivo | Ruta | Clics | Resultado |
|---|---|---|---|---|
| T1 | Exportar el CSV de citas de noviembre | Reportes -> Citas por servicio -> filtros -> Vista previa -> Exportar CSV | 4 | Exito |
| T2 | Entender por que no se puede exportar PDF | Vista previa -> boton PDF | 1 | Con dificultad: la razon solo esta en `title` (no accesible por teclado ni tactil) |
| T3 | Volver a cambiar un filtro tras exportar | Exportado -> Volver | 2 | Con dificultad: se vuelve al catalogo y se pierden los filtros |

## 2. Analisis heuristico

| ID | Heuristica | Problema | Severidad | Recomendacion |
|---|---|---|---|---|
| HE-01 | H1 Visibilidad del estado | El motivo de PDF deshabilitado solo esta en `title`. | 3 | Texto visible bajo el boton: "PDF disponible cuando se apruebe la libreria". |
| HE-02 | H5 Prevencion de errores | El limite de 366 dias solo se valida al enviar. | 2 | Validar al cambiar las fechas y mostrar el error junto al campo. |
| HE-03 | H3 Control y libertad | Tras exportar se pierden los filtros. | 2 | Volver a la vista previa con los mismos filtros. |
| HE-04 | H7 Flexibilidad y eficiencia | La vista previa muestra 2 de 48 filas sin paginacion visible. | 2 | Paginacion accesible y total de filas. |
| HE-05 | H7 Flexibilidad y eficiencia | El nombre del archivo no se puede ajustar. | 1 | Permitir sufijo opcional manteniendo el periodo. |

Los hallazgos de severidad >= 3 se corrigen en el prototipo de la Semana 11; los de severidad 1-2
quedan en el backlog del issue #210.

## 3. Protocolo de prueba con usuarios (siguiente iteracion)

- 5 participantes del rol objetivo, sesiones de 20 min, pensamiento en voz alta.
- Mismas tareas T1-T3; se mide exito, tiempo y errores por tarea.
- Cuestionario SUS al final (meta: >= 70 puntos).
- Consentimiento informado y datos ficticios: no se usan datos reales de pacientes.
