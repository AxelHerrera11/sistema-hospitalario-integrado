# Dashboard de ocupación hospitalaria - Semana 2

## Ficha de la asignación

- **Estudiante:** FAJARDO LÓPEZ, JAVIER ALEXANDER (`Javifa2324`)
- **Correo:** `jfajardol3@miumg.edu.gt`
- **Módulo:** ASII-09 - Dashboard de ocupación hospitalaria
- **Issue:** [#36](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/36)
- **Flujo:** consolidación y consulta de ocupación por sala y estado de cama
- **Principio:** ISP - *Interface Segregation Principle*
- **Fuente obligatoria:** Rodríguez González, L. M. *Principios básicos del diseño de software*. [MVP Cluster](https://mvpcluster.com/diseno-de-software-2/)

## Objetivo

Formalizar requisitos verificables para consultar una fotografía consistente de la ocupación del hospital y aplicar ISP para que cada consumidor dependa únicamente de las operaciones de lectura que utiliza.

## Entregables

- [RF, RNF y criterios de aceptación](./rf-rnf-criterios.md)
- [Aplicación de ISP: diseño antes y después](./solid-isp.md)
- [Evidencia verificable](./EVIDENCIA.md)
- [Fuente Mermaid del diseño anterior](./fuentes/01-diseno-antes-isp.mmd)
- [Fuente Mermaid del diseño propuesto](./fuentes/02-diseno-despues-isp.mmd)
- [Declaración de uso de IA](./DECLARACION_IA.md)
- [Base de Semana 1](../semana-01/README.md)

## Alcance

Esta semana incluye RF/RNF trazados a los casos de uso de Semana 1, criterios medibles, escenarios Gherkin y la segregación de interfaces de consulta. Conserva estas decisiones:

- `bed_stays` determina la ocupación clínica en la fecha de corte.
- La estancia abierta del issue describe la consulta actual; Semana 1 la formaliza para cualquier corte `T` mediante su intervalo de vigencia.
- `beds.status` es operativo y una discrepancia se informa, no se oculta ni corrige.
- El dashboard solo consulta datos agregados del hospital activo.
- El dashboard no asigna, traslada, libera ni modifica camas.

Quedan fuera la implementación PHP por capas (Semana 3), MVC y Repository (Semana 4), el contrato HTTP definitivo (Semana 5) y la integración Laravel/Vue.

## Estado

Entrega documental de Semana 2. Los umbrales propuestos deberán validarse con el equipo durante la implementación.
