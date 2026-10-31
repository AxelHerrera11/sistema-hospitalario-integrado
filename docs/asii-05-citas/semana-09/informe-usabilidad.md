# Informe de evaluacion de usabilidad - ASII-05

**Objeto evaluado:** prototipo navegable de la Semana 8 (`../semana-08/prototipo/index.html`).
**Metodo:** inspeccion por la autora (recorrido cognitivo + evaluacion heuristica de Nielsen), escala
de severidad 0-4 (0 = no es problema, 4 = catastrofe). No se realizaron aun sesiones con usuarios
reales; el protocolo para hacerlo esta al final.

## 1. Recorrido cognitivo (tareas clave)

| Tarea | Objetivo | Ruta | Clics | Resultado |
|---|---|---|---|---|
| T1 | Agendar una cita para Ana Lopez el 02/11 a las 11:00 | Agenda -> Nueva cita -> completar -> Guardar | 3 | Exito; el formulario no muestra horarios libres antes de elegir la hora |
| T2 | Cancelar la cita recien creada | Ver detalle -> Cancelar cita -> Si, cancelar | 3 | Exito; la confirmacion explica la consecuencia |
| T3 | Reprogramar una cita | Detalle -> Reprogramar | 2 | Con dificultad: abre un formulario de cita nueva y se pierde el contexto |

## 2. Analisis heuristico

| ID | Heuristica | Problema | Severidad | Recomendacion |
|---|---|---|---|---|
| HE-01 | H5 Prevencion de errores | La hora se elige sin ver la disponibilidad del doctor; el solape solo se descubre al guardar (409). | 3 | Mostrar franjas libres al elegir doctor y fecha. |
| HE-02 | H3 Control y libertad | Reprogramar abre el formulario de cita nueva sin datos precargados. | 2 | Precargar paciente y doctor; mostrar la cita original. |
| HE-03 | H1 Visibilidad del estado | Confirmar asistencia vuelve a la agenda sin mensaje de exito. | 2 | Mostrar alerta `role=status` con el nuevo estado. |
| HE-04 | H6 Reconocer antes que recordar | El paciente se busca en un campo de texto libre sin sugerencias visibles. | 2 | Autocompletado con nombre + DPI parcial. |
| HE-05 | H7 Flexibilidad y eficiencia | La agenda no se puede filtrar por doctor. | 1 | Filtro por doctor y especialidad. |

Los hallazgos de severidad >= 3 se corrigen en el prototipo de la Semana 11; los de severidad 1-2
quedan en el backlog del issue #207.

## 3. Protocolo de prueba con usuarios (siguiente iteracion)

- 5 participantes del rol objetivo, sesiones de 20 min, pensamiento en voz alta.
- Mismas tareas T1-T3; se mide exito, tiempo y errores por tarea.
- Cuestionario SUS al final (meta: >= 70 puntos).
- Consentimiento informado y datos ficticios: no se usan datos reales de pacientes.
