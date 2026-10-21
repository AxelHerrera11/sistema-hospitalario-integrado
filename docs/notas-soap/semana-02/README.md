# Notas SOAP y diagnósticos — Semana 2

## Ficha de la asignación

- **Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`)
- **Módulo:** ASII-11 — Notas SOAP y diagnósticos
- **Semana:** Semana 2 — Proceso y modelo de diseño; principios SOLID
- **Principio aplicado:** SRP — *Single Responsibility Principle* (responsabilidad única)
- **Tarea individual:** Aplique SRP al diseño del flujo «registro de nota SOAP y asociación de diagnóstico clínico». Defina RF/RNF y criterios de aceptación; muestre el diseño antes/después, justifique responsabilidades y dependencias y aporte evidencia verificable.
- **Entregable / evidencia:** RF/RNF, criterios de aceptación y ejemplo SOLID.
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. MVP Cluster. <https://mvpcluster.com/diseno-de-software-2/>

## Resumen

Se definen siete RF y seis RNF medibles del registro de la nota y la asociación de diagnósticos, con escenarios de aceptación. SRP se aplica separando el `SoapNoteService` de la Semana 1, que tenía cinco razones para cambiar, en dos casos de uso (`SoapNoteRegistrar`, `DiagnosisAssociator`) que se apoyan en una clase por regla (acceso, formato, reglas de diagnóstico) e interfaces para lo externo (catálogo CIE-10, asignaciones y persistencia). Una demo PHP lo comprueba.

## Archivos de evidencia

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Aplicación de SRP (diseño antes y después)](./solid-srp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Demo ejecutable](./evidencia/demo-srp.php): `php docs/notas-soap/semana-02/evidencia/demo-srp.php`
- [Declaración de uso de IA](./DECLARACION_IA.md)
- Base de esta semana: [UML y narrativa de la Semana 1](../semana-01/README.md)

## Alcance

**Incluido:** RF/RNF trazados a UC-SOAP-01 a UC-SOAP-07, criterios Gherkin, diseño antes/después con SRP (diagramas Mermaid dentro de [solid-srp.md](./solid-srp.md)) y demo PHP 8.2+ sin framework.

**Fuera de alcance:** firma y corrección en detalle (`SoapNoteSigner`, Semana 5), capas del micro-HIS (Semana 3), Repository con PDO (Semana 4) e integración con Laravel, PostgreSQL o Vue.
