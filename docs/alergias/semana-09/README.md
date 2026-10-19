# Alergias clínicas y alerta visual en expediente — Semana 9: evaluación de usabilidad y accesibilidad

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** checklist, hallazgos y mejoras propuestas.

## Alcance

Se evalúan las pantallas de la [Semana 8](../semana-08/README.md): expediente con banner crítico, banner «no disponible» y formulario «Registrar alergia».

## Método

- **Evaluación heurística:** las 10 heurísticas de Nielsen sobre las pantallas de la Semana 8.
- **Inspección WCAG 2.1 AA:** teclado, foco, contraste, etiquetas, mensajes, zoom al 200 % y uso de color.
- **Recorridos simulados:** solo con teclado (Tab, Enter, Esc) y con lector de pantalla (VoiceOver o NVDA, leyendo el orden previsto del DOM).
- **Severidad:**
  - **Alta:** puede inducir una decisión clínica errónea o bloquear la tarea;
  - **Media:** la dificulta;
  - **Baja:** es cosmética.
- **Prioridad:** severidad × frecuencia de uso del rol.

## Heurísticas de Nielsen

| # | Heurística | Resultado | Observación |
|---|---|---|---|
| 1 | Visibilidad del estado | Cumple | Los cuatro niveles del banner son distinguibles y el de carga nunca dice «Sin alergias» |
| 2 | Relación con el mundo real | Cumple | La severidad se muestra en lenguaje clínico («Amenaza la vida») |
| 3 | Control y libertad | Cumple | El formulario se cancela sin guardar; el banner crítico no se oculta, a propósito |
| 4 | Consistencia y estándares | Cumple | El mismo banner en el Expediente y en Prescripciones |
| 5 | Prevención de errores | Parcial | El duplicado se detecta al guardar; no se sugiere mientras se escribe |
| 6 | Reconocer antes que recordar | Parcial | El alérgeno es texto libre; un buscador del catálogo de medicamentos reduciría errores |
| 7 | Flexibilidad y eficiencia | Cumple | Registrar una alergia toma un solo formulario |
| 8 | Diseño minimalista | Parcial | Con cinco o más alergias el banner crece y empuja el expediente |
| 9 | Ayudar a reconocer y corregir errores | Cumple | El 409 enlaza la alergia existente |
| 10 | Ayuda y documentación | Parcial | Falta explicar qué hacer con «no disponible» |
