# Informe de evaluacion de usabilidad - ASII-26

**Objeto evaluado:** prototipo navegable de la Semana 8 (`../semana-08/prototipo/index.html`).
**Metodo:** inspeccion por la autora (recorrido cognitivo + evaluacion heuristica de Nielsen), escala
de severidad 0-4 (0 = no es problema, 4 = catastrofe). No se realizaron aun sesiones con usuarios
reales; el protocolo para hacerlo esta al final.

## 1. Recorrido cognitivo (tareas clave)

| Tarea | Objetivo | Ruta | Clics | Resultado |
|---|---|---|---|---|
| T1 | Saber que indicadores no cumplen la meta | Inicio (lectura de tarjetas) | 0 | Exito; icono + texto + color |
| T2 | Ver el detalle diario de ocupacion | Tarjeta -> Detalle | 1 | Exito |
| T3 | Cambiar al periodo de octubre | Selector de periodo | 1 | Con dificultad: no hay boton Aplicar ni indicio de recarga |

## 2. Analisis heuristico

| ID | Heuristica | Problema | Severidad | Recomendacion |
|---|---|---|---|---|
| HE-01 | H2 Coincidencia con el mundo real | "Tiempo de espera" mejora cuando baja, pero la tarjeta no lo indica. | 3 | Texto "menor es mejor" junto a la meta. |
| HE-02 | H1 Visibilidad del estado | Cambiar el periodo no da retroalimentacion. | 2 | Mostrar el estado cargando al cambiar periodo. |
| HE-03 | H10 Ayuda y documentacion | No se explica como se calcula cada indicador. | 2 | Enlace "Como se calcula" al Centro de documentacion (ASII-24). |
| HE-04 | H7 Flexibilidad y eficiencia | El detalle no muestra tendencia. | 1 | Grafico simple con texto alternativo. |
| HE-05 | H9 Recuperarse de errores | El estado de error no indica si reintentara solo. | 1 | Texto "Reintentaremos en 30 s" o boton unico. |

Los hallazgos de severidad >= 3 se corrigen en el prototipo de la Semana 11; los de severidad 1-2
quedan en el backlog del issue #209.

## 3. Protocolo de prueba con usuarios (siguiente iteracion)

- 5 participantes del rol objetivo, sesiones de 20 min, pensamiento en voz alta.
- Mismas tareas T1-T3; se mide exito, tiempo y errores por tarea.
- Cuestionario SUS al final (meta: >= 70 puntos).
- Consentimiento informado y datos ficticios: no se usan datos reales de pacientes.
