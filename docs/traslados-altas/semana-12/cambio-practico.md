# Cambio práctico documentado — retroceder con Escape y devolver el foco

**Tipo:** ejercicio **simulado**, propuesto por la autora. No responde a ninguna solicitud del docente.

| | Antes (develop `decdf39`) | Después (este PR) |
|---|---|---|
| «Cancelar» en los datos | Oculta la sección; el foco queda en el botón **oculto** | El foco va al título de la situación |
| «Volver» en la confirmación | Igual: el foco queda en un elemento oculto | El foco va a la cama, o al título si es un alta |
| Tecla Escape | Sin efecto | Retrocede un paso: confirmación → datos → situación |
| Escape mientras se procesa | — | Sin efecto: no interrumpe un envío en curso |
| Datos escritos | Se conservan | Se conservan |

**Motivo:** heurística 3 (control y libertad) y WCAG 2.4.3 (orden del foco). Dejar el foco en un elemento oculto desorienta a quien usa el teclado o un lector de pantalla.

**Impacto:** solo `public/ui/app.js` y un `tabindex="-1"` en `index.html`; la API y el micro-HIS no cambian.

**Evidencia:** [`evidencia/teclado.mjs`](./evidencia/teclado.mjs) da **1/4 antes** (develop servido con `git archive`) y **4/4 después**. Sin regresión: recorrido 9/9 y `ui-mensajes` 7/7.
