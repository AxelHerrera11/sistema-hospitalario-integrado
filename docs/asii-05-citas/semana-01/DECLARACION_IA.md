# Declaración de uso de IA — ASII-05 Semana 1 (UML y alcance)

**Tarea Odoo:** Semana 1 — UML de casos de uso y narrativa de alcance.
**Fecha:** 18/10/2026.

## Herramienta

- OpenAI Codex / opencode (agente `big-pickle`), asistente de desarrollo en el repositorio.

## Propósito

Modelar en PlantUML y narrar el alcance de la Semana 1 del módulo de gestión de citas, adaptándolo al formato del repositorio grupal (`docs/asii-05-citas/semana-01/`) y tomando como fuente el contrato API de citas y la migración real de `appointments`.

## Prompts relevantes

1. «Redacta los tres diagramas PlantUML (casos de uso, actividad y secuencia) y la narrativa de alcance y trazabilidad de ASII-05 Semana 1 al formato del repositorio del grupo, con fuentes `.puml`, SVG y matriz de trazabilidad.»
2. «Mantén el proceso "solicitud, validación de disponibilidad, confirmación o cancelación de cita", con decisiones, excepciones (409/422) y trazabilidad entre los tres diagramas, alineado al contrato API y a las exclusiones GiST de `appointments`.»

## Contenido generado y validación

- **Generado por IA:** los tres diagramas UML, los estilos `skinparam`, la redacción de la narrativa y de esta declaración, y los comandos de regeneración.
- **Aportado por la estudiante:** criterios del proceso de citas, transiciones de estado y revisión del alcance frente al contrato API del módulo.
- **Verificación humana:** pendiente de revisión integral de la narrativa, los supuestos y la trazabilidad antes de la defensa. Los SVG se generan con PlantUML 1.2026.8.
