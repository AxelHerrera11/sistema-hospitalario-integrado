# Recepción de muestras — semanas 1 a 15

Autor de esta entrega: María de los Ángeles López Fajardo, `Mar-03`.
Módulo oficial ASII-18: recepción de muestras y códigos identificadores.
La asignación del README general se conserva; no se cambia la titularidad del módulo.

## Introducción

Una recepción incorrecta puede asociar resultados a otro paciente. El módulo verifica dos
identificadores frente a una orden confiable, aplica requisitos de catálogo y registra aceptación
o rechazo sin perder trazabilidad. Cada semana conecta análisis, microproyecto y código del HIS.

## Índice y evolución

| Semana | Evidencia | Implementación acumulada |
|---|---|---|
| 01 | [Actores, UML y alcance](semana-01/README.md) | Contrato funcional y excepciones |
| 02 | [RF/RNF y SOLID](semana-02/README.md) | Dominio del HIS en app/Modules/Reception |
| 03 | [Capas PHP](semana-03/README.md) | Micro-HIS vanilla independiente |
| 04 | [MVC y Repository](semana-04/README.md) | Controlador y prueba del micro |
| 05 | [API e integración](semana-05/README.md) | Laravel, PostgreSQL y RBAC |
| 06 | [Primera evaluación](semana-06/README.md) | Pruebas de reglas y errores |
| 07 | [Componentes](semana-07/README.md) | Proyección de órdenes y outbox |
| 08 | [UX por rol](semana-08/README.md) | Formulario Vue real |
| 09 | [Usabilidad](semana-09/README.md) | Errores, teclado y reenvíos |
| 10 | [Responsive](semana-10/README.md) | Escenarios móvil/escritorio |
| 11 | [Prototipo navegable](semana-11/README.md) | Demo con datos ficticios |
| 12 | [Segunda evaluación](semana-12/README.md) | Defensa y recuperación |
| 13 | [Revisión técnica](semana-13/README.md) | Checklist y registro de hallazgos |
| 14 | [SQA](semana-14/README.md) | Estrategia y automatización |
| 15 | [Pruebas y entrega](semana-15/README.md) | Evidencia real y matriz de cierre |

## Límites de la entrega

La [matriz de cumplimiento](matriz-cumplimiento.md) contrasta cada obligación del README original.
La [declaración de IA](DECLARACION_IA.md) documenta herramienta, instrucciones y validación pendiente.
Cada semana desarrolla su artefacto completo; las ramas complementarias evitan recortarlo para caber en 400 líneas.

El micro vive en `micro-his/recepcion-muestras`. La implementación Laravel vive en
`app/Modules/Reception`, `app/Http`, `routes`, `database` y `resources/js/modules/recepcion-muestras`.
El código del HIS no carga ni ejecuta archivos del micro. Las pruebas de ambos son independientes.
Los diagramas iniciales describen diseño; el contrato de semana 5 determina el comportamiento implementado.
La integración externa y las evaluaciones orales requieren evidencia de sus responsables; no se inventa.
No se modifica otro módulo para simular que sus capacidades ya existen.

## Conclusión y bibliografía

El criterio de cierre es una recepción persistida, autorizada y reproducible, con rechazo recuperable.
Referencia de requisitos: README general, Definition of Done y ejercicio individual ASII-18.
Issue verificado: [ASII-18 #6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
Ejecución y despliegue: [operación](operacion.md). Resultados: [semana 15](semana-15/README.md).
Referencia SOLID: [MVP Cluster](https://mvpcluster.com/diseno-de-software-2/).
Los registros de ejecución y la matriz final distinguen pruebas realizadas y validación externa pendiente.
