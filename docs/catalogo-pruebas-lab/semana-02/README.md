# Catálogo de pruebas de laboratorio — Semana 2

## Ficha de la asignación

- **Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`)
- **Módulo:** ASII-17 — Catálogo de pruebas de laboratorio
- **Semana:** Semana 2 — Proceso y modelo de diseño; principios SOLID
- **Principio aplicado:** OCP — *Open/Closed Principle*
- **Tarea:** aplicar OCP al flujo «alta y consulta de prueba con requisitos de muestra y vigencia», con RF/RNF, criterios de aceptación, diseño antes/después y evidencia verificable.
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. MVP Cluster. <https://mvpcluster.com/diseno-de-software-2/>

## Resumen

Se aplica OCP a **la validación del tipo de resultado**: el `switch` del servicio se reemplaza por una `ResultTypeSpec` por tipo en un registro, más reglas comunes `LabTestRule` (muestra y vigencia). Un tipo nuevo (ordinal, por ejemplo proteinuria de negativo a 4+) es una clase nueva y no modifica el validador. Una demo PHP lo comprueba.

## Archivos

- [RF/RNF y criterios de aceptación](./rf-rnf-criterios.md) · [Aplicación de OCP](./solid-ocp.md) · [Evidencia verificable](./evidencia-verificable.md)
- [Demo](./evidencia/demo-ocp.php): `php docs/catalogo-pruebas-lab/semana-02/evidencia/demo-ocp.php`
- Diagramas: [antes](./01-diseno-antes.svg) ([fuente](./fuentes/01-diseno-antes.mmd)) · [después](./02-diseno-despues.svg) ([fuente](./fuentes/02-diseno-despues.mmd))
- [Declaración de uso de IA](./DECLARACION_IA.md) · Base: [Semana 1](../semana-01/README.md)

**Fuera de alcance:** micro-HIS por capas (Semana 3), MVC y Repository (Semana 4), contrato API (Semana 5) y la migración real de `lab_tests`.
