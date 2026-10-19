# Alergias clínicas y alerta visual en expediente — Semana 10: adaptación responsive y móvil

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** escenarios móviles y prioridades de pantalla.

## Objetivo

Adaptar las pantallas de la [Semana 8](../semana-08/README.md) a teléfono y tablet según cómo se usan en el hospital, con las reglas de [breakpoints.md](./breakpoints.md) y las mejoras de la [Semana 9](../semana-09/README.md).

## Escenarios móviles

| # | Quién y dónde | Qué necesita | Respuesta del diseño |
|---|---|---|---|
| 1 | Enfermería, junto a la cama, con el teléfono | Confirmar que el paciente no es alérgico antes de administrar un antibiótico | Banner arriba del expediente, siempre visible, con el alérgeno y la severidad |
| 2 | Médico de guardia, en urgencias | Registrar una alergia que refiere el paciente | Formulario corto en una columna con el botón «Guardar» fijo abajo |
| 3 | Médico en Prescripciones, en tablet | Ver el banner al lado de la receta | Banner sobre el formulario de receta; con `critical` bloquea «Firmar» hasta confirmar |
