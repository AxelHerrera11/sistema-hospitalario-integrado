# Admisión hospitalaria y asignación de cama — Semana 2

## Ficha de la asignación

- **Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`)
- **Módulo:** ASII-07 — Admisión hospitalaria y asignación de cama (módulo absorbido tras la reorganización del grupo)
- **Semana:** Semana 2 — Proceso y modelo de diseño; principios SOLID
- **Principio aplicado:** OCP — *Open/Closed Principle* (abierto a extensión, cerrado a modificación)
- **Tarea individual:** Aplique OCP al diseño del flujo «admisión de paciente y asignación de una cama disponible». Defina RF/RNF y criterios de aceptación; muestre el diseño antes/después, justifique responsabilidades y dependencias y aporte evidencia verificable.
- **Entregable / evidencia:** RF/RNF, criterios de aceptación, ejemplo SOLID y evidencia verificable.
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. MVP Cluster. <https://mvpcluster.com/diseno-de-software-2/>

## Resumen

Esta entrega define nueve requerimientos funcionales y ocho no funcionales medibles de la admisión y la asignación de cama, con escenarios de aceptación, y aplica OCP a **las reglas de elegibilidad de cama**. La cadena de `if` del servicio, que además se repetía en la búsqueda, se reemplaza por reglas que implementan `BedEligibilityRule` y un único `BedEligibilityChecker`, usado al buscar y al asignar. Un requisito nuevo (camas pediátricas solo para menores de 15 años) se resuelve con una clase nueva y sin modificar el verificador. Una demo PHP ejecutable lo comprueba.

## Archivos de evidencia

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Aplicación de OCP (diseño antes y después)](./solid-ocp.md)
- [Evidencia verificable](./evidencia-verificable.md)
- [Demo ejecutable](./evidencia/demo-ocp.php): `php docs/admision-camas/semana-02/evidencia/demo-ocp.php`
- [Diagrama antes de OCP](./fuentes/01-diseno-antes.mmd) · [Diagrama después de OCP](./fuentes/02-diseno-despues.mmd)
- [Declaración de uso de IA](./DECLARACION_IA.md)
- Base de esta semana: [UML y narrativa de la Semana 1](../semana-01/README.md)

## Alcance de la Semana 2

**Incluido**

- Requerimientos funcionales trazados a los casos de uso UC-ADM-01 a UC-ADM-09.
- Requerimientos no funcionales de seguridad, concurrencia, consistencia, rendimiento, usabilidad, disponibilidad del dato, privacidad y mantenibilidad.
- Criterios de aceptación y escenarios Gherkin.
- Diseño antes y después aplicando OCP, con la justificación de responsabilidades y dependencias.
- Demo PHP 8.2+ sin framework que reproduce el comportamiento y demuestra la extensión.

**Fuera de alcance**

- Micro-HIS en PHP por capas (Semana 3).
- MVC y Repository con adaptadores PDO e InMemory (Semana 4).
- Contrato API formal (Semana 5).
- Columna o catálogo que identifique las salas pediátricas: pendiente de coordinar con ASII-06.
