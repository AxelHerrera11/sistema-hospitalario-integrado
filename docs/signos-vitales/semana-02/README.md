# Signos vitales y alertas por valores anormales — Semana 2

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-13 — Signos vitales y alertas por valores anormales
- **Semana:** Semana 2 — Proceso y modelo de diseño; principios SOLID
- **Principio aplicado:** LSP — *Liskov Substitution Principle* (sustitución de Liskov)
- **Tarea individual:** Aplique LSP al diseño del flujo «captura de signos vitales, evaluación de umbrales y generación de alerta». Defina RF/RNF y criterios de aceptación; muestre el diseño antes/después, justifique responsabilidades y dependencias y aporte evidencia verificable.
- **Entregable / evidencia:** RF/RNF, criterios de aceptación y ejemplo SOLID.
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. MVP Cluster. <https://mvpcluster.com/diseno-de-software-2/>

## Resumen

Se definen siete RF y cinco RNF medibles de la captura, la evaluación de umbrales y la alerta, con escenarios de aceptación. LSP se aplica a la jerarquía de reglas de umbral: en el diseño antes, dos subclases lanzan excepciones que el tipo base no prevé y el evaluador las esquiva con `instanceof`. En el después, todas cumplen el contrato de `VitalSignRule` (signo ausente → `None`, nunca lanza) y el evaluador las trata por igual. Una demo PHP lo prueba con la misma prueba de contrato para cada subtipo.

## Archivos de evidencia

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Aplicación de LSP (diseño antes y después)](./solid-lsp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Demo ejecutable](./evidencia/demo-lsp.php): `php docs/signos-vitales/semana-02/evidencia/demo-lsp.php`
- [Declaración de uso de IA](./DECLARACION_IA.md)
- Base de esta semana: [UML y narrativa de la Semana 1](../semana-01/README.md)

## Alcance

**Incluido:** RF/RNF trazados a UC-SV-01 a UC-SV-07, criterios Gherkin, diseño antes/después con LSP (diagramas Mermaid dentro de [solid-lsp.md](./solid-lsp.md)) y demo PHP 8.2+ sin framework.

**Fuera de alcance:** reconocimiento de alertas y `critical_alert` (acuerdo C1 con laboratorio), canal de notificación real (C3), capas del micro-HIS (Semana 3), Repository con PDO (Semana 4) e integración con Laravel, PostgreSQL o Vue.
