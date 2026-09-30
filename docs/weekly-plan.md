# Plan semanal ASII — Sistema Hospitalario Integrado

Este plan organiza las semanas 1 a 18 del Proyecto Final de Análisis de Sistemas II. Cada estudiante debe aplicar las actividades a su módulo asignado y conservar evidencia para el issue y el pull request.

## Resumen de evaluación

| Bloque | Semanas | Puntos |
|---|---:|---:|
| Actividades Parcial 1: conceptos, UML y arquitectura | 1-5 | 6 |
| Actividades Parcial 2: componentes, UX y movilidad | 7-11 | 7 |
| Actividades Evaluación Final: calidad, pruebas, seguridad y despliegue | 13-17 | 7 |
| Proyecto individual final | 1-18 | 15 |

## Cronograma detallado

| Semana | Tema guía ASII | Actividad aplicada al módulo | Entregable | Puntos actividad |
|---:|---|---|---|---:|
| 1 | Conceptos generales, orientación a objetos y UML. | Identificar actores, procesos, límites y casos de uso del módulo dentro del HIS. | Diagrama UML de casos de uso + narrativa breve del alcance. | 1 |
| 2 | Proceso de diseño, conceptos de diseño y modelo de diseño. | Definir RF/RNF, criterios de aceptación y ejemplificar al menos un principio SOLID en el módulo asignado. Fuente SOLID: `https://mvpcluster.com/diseno-de-software-2/`. | Tabla RF/RNF + criterios de aceptación + ejemplo SOLID. | 1 |
| 3 | Diseño arquitectónico, vistas y patrones. | Diseñar la vista arquitectónica del módulo y sus dependencias con el HIS. | Diagrama C4/UML o vista de componentes de alto nivel. | 1 |
| 4 | Arquitectura en capas y patrón repositorio. | Definir responsabilidades por capa: UI, API, lógica de negocio, persistencia y objetos reutilizables. | Diagrama por capas + listado de responsabilidades y objetos reutilizables. | 1 |
| 5 | Cliente-servidor, API REST, microservicios e integración arquitectónica. | Diseñar contrato API preliminar y planificar integración técnica con issue, rama, worktree y PR. | Endpoints, payloads, respuestas, errores, permisos y plan de rama/worktree/PR. | 2 |
| 6 | Primera evaluación parcial. | Defender conceptos de UML, proceso de diseño, arquitectura, capas, repositorio y cliente-servidor. | Evaluación teórica + caso práctico arquitectónico. | Parcial |
| 7 | Diseño de componentes y refactorización. | Diseñar componentes backend/frontend y proponer una mejora para reducir acoplamiento o duplicación. | Diagrama de componentes + antes/después conceptual + justificación. | 2 |
| 8 | Diseño de experiencia de usuario. | Diseñar flujo UX del módulo por rol, incluyendo estados, errores y ayudas. | User flow + wireframes iniciales + reglas de interacción. | 1 |
| 9 | Evaluación del diseño, usabilidad y accesibilidad. | Evaluar el flujo con checklist de usabilidad/accesibilidad y proponer mejoras. | Checklist + hallazgos + mejoras priorizadas. | 1 |
| 10 | Diseño para movilidad. | Adaptar el flujo principal del módulo a uso responsive/móvil. | Propuesta responsive + escenarios móviles. | 1 |
| 11 | Mejores prácticas para diseño móvil/web. | Crear mockup o prototipo navegable desktop/móvil. | Mockup en Figma, Canva, Excalidraw o herramienta equivalente. | 2 |
| 12 | Segunda evaluación parcial. | Defender componentes, UX, usabilidad, accesibilidad y movilidad. | Evaluación teórica + caso práctico de diseño. | Parcial |
| 13 | Calidad del software y revisiones técnicas formales. | Crear plan de revisión técnica formal del módulo. | Checklist de revisión + responsables + evidencia esperada. | 1 |
| 14 | Confiabilidad y aseguramiento de calidad. | Crear plan SQA del módulo con métricas, riesgos y estrategia de control. | Plan de calidad + métricas propuestas. | 1 |
| 15 | Pruebas unitarias, caja blanca y caja negra. | Diseñar y ejecutar pruebas relacionadas con los RF/RNF del módulo. | Casos de prueba + evidencia de ejecución. | 1 |
| 16 | Pruebas de integración, validación, sistema y primera entrega funcional. | Integrar el módulo con el HIS, validar el flujo y corregir errores detectados. | Evidencia de integración + errores corregidos + bitácora inicial de despliegue. | 2 |
| 17 | Modelado y pruebas de seguridad. | Modelar amenazas y probar roles, permisos, tenant y datos clínicos sensibles. | Matriz de amenazas + pruebas de seguridad + evidencia de despliegue final. | 2 |
| 18 | Evaluación final. | Presentar defensa del proyecto, demo funcional y evidencia completa. | Demo, PR integrado, documentación y defensa individual. | Final |

## Fuente de principios SOLID para semana 2

La semana 2 debe incluir un ejemplo aplicado al módulo de al menos uno de estos principios, usando como fuente `https://mvpcluster.com/diseno-de-software-2/`:

| Principio | Aplicación esperada en el módulo |
|---|---|
| Single Responsibility | Cada clase, componente o servicio tiene una responsabilidad concreta y una razón clara para cambiar. |
| Open/Closed | El módulo permite extender comportamiento sin modificar clases o entidades estables innecesariamente. |
| Liskov Substitution | Las clases derivadas o variantes pueden sustituir a su base sin romper comportamiento esperado. |
| Interface Segregation | Se prefieren contratos pequeños y enfocados; ninguna clase debe depender de métodos que no necesita. |
| Dependency Inversion | La lógica de alto nivel depende de abstracciones y no de detalles concretos de infraestructura. |

## Entregas críticas

| Semana | Hito |
|---:|---|
| 6 | Parcial 1: arquitectura del módulo validada. |
| 12 | Parcial 2: componentes, UX y movilidad validados. |
| 16 | Primera entrega funcional integrada en servidor/staging/on-premise. |
| 17 | Entrega funcional corregida con pruebas de seguridad. |
| 18 | Evaluación final y defensa del proyecto. |

## Reglas de evidencia

- Toda actividad debe estar relacionada con el módulo asignado.
- La evidencia debe adjuntarse al issue o PR: capturas, diagramas, enlaces, comandos ejecutados y resultados.
- Las pruebas deben demostrar comportamiento, no solo que el proyecto compila.
- Los cambios clínicos deben considerar roles, permisos, tenant y datos sensibles.
- No se acepta PR sin evidencia mínima de análisis, diseño, implementación y pruebas.
