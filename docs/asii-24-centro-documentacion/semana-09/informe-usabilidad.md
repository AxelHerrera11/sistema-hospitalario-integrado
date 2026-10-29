# Informe de evaluacion de usabilidad - ASII-24

**Objeto evaluado:** prototipo navegable de la Semana 8 (`../semana-08/prototipo/index.html`).
**Metodo:** inspeccion por la autora (recorrido cognitivo + evaluacion heuristica de Nielsen), escala
de severidad 0-4 (0 = no es problema, 4 = catastrofe). No se realizaron aun sesiones con usuarios
reales; el protocolo para hacerlo esta al final.

## 1. Recorrido cognitivo (tareas clave)

| Tarea | Objetivo | Ruta | Clics | Resultado |
|---|---|---|---|---|
| T1 | Encontrar y descargar el manual de enfermeria | Catalogo -> tarjeta -> Descargar PDF | 2 | Exito |
| T2 | Buscar el protocolo de alergias | Escribir en Buscar | 1 | Con dificultad: no hay boton ni mensaje cuando no hay coincidencias |
| T3 | Publicar un manual para Farmacia | Publicar -> completar -> Publicar | 2 | Exito; el error de rol faltante solo aparece al enviar |

## 2. Analisis heuristico

| ID | Heuristica | Problema | Severidad | Recomendacion |
|---|---|---|---|---|
| HE-01 | H5 Prevencion de errores | Se puede enviar el formulario sin roles; el aviso aparece despues (422). | 3 | Validar el grupo de roles en linea y deshabilitar Publicar con explicacion visible. |
| HE-02 | H1 Visibilidad del estado | La busqueda no informa "0 resultados". | 2 | Estado vacio con sugerencias de busqueda. |
| HE-03 | H5 Prevencion de errores | No hay vista previa del documento antes de publicar. | 2 | Paso de revision antes de confirmar. |
| HE-04 | H1 Visibilidad del estado | Las tarjetas no muestran fecha de actualizacion. | 1 | Agregar fecha y version en la tarjeta. |
| HE-05 | H1 Visibilidad del estado | Descargar PDF no indica tamano del archivo. | 1 | Mostrar "PDF, 1.2 MB". |

Los hallazgos de severidad >= 3 se corrigen en el prototipo de la Semana 11; los de severidad 1-2
quedan en el backlog del issue #208.

## 3. Protocolo de prueba con usuarios (siguiente iteracion)

- 5 participantes del rol objetivo, sesiones de 20 min, pensamiento en voz alta.
- Mismas tareas T1-T3; se mide exito, tiempo y errores por tarea.
- Cuestionario SUS al final (meta: >= 70 puntos).
- Consentimiento informado y datos ficticios: no se usan datos reales de pacientes.
