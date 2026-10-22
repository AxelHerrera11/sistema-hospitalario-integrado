# Alertas críticas y notificaciones internas — Semana 2

## Ficha de la asignación

- **Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`)
- **Módulo:** ASII-21 — Alertas críticas y notificaciones internas
- **Semana:** Semana 2 — Proceso y modelo de diseño; principios SOLID
- **Principio aplicado:** SRP — *Single Responsibility Principle*
- **Tarea:** aplicar SRP al flujo «detección, envío, acuse y escalamiento de una alerta crítica», con RF/RNF, criterios de aceptación, diseño antes/después y evidencia verificable.
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. MVP Cluster. <https://mvpcluster.com/diseno-de-software-2/>

## Resumen

`CriticalAlertManager` tenía cuatro razones para cambiar (política clínica, organigrama, canal y plazos). Se separa en una clase por responsabilidad y un orquestador delgado. Una demo PHP comprueba que cambiar el canal (TI) o el plazo (calidad) no toca el detector ni el registrador.

## Archivos

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md) · [Aplicación de SRP](./solid-srp.md) · [Evidencia verificable](./evidencia-verificable.md)
- [Demo](./evidencia/demo-srp.php): `php docs/alertas-criticas/semana-02/evidencia/demo-srp.php`
- Diagramas: [antes](./01-diseno-antes.svg) ([fuente](./fuentes/01-diseno-antes.mmd)) · [después](./02-diseno-despues.svg) ([fuente](./fuentes/02-diseno-despues.mmd))
- [Declaración de uso de IA](./DECLARACION_IA.md) · Base: [Semana 1](../semana-01/README.md)
