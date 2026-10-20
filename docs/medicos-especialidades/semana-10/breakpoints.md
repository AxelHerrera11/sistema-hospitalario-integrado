# Anchos de referencia y reglas móviles

El diseño cubre teléfonos de 320 a 430 px. Se revisa en tres anchos:

| Ancho | Equipo de referencia | Qué se comprueba |
|---|---|---|
| 320 px | Teléfono pequeño | Nada se corta; las franjas pasan a dos por línea |
| 360 px | Teléfono Android común | Ancho base de los wireframes y del prototipo |
| 430 px | Teléfono grande | Hasta cuatro franjas por línea; la jerarquía no cambia |

**Reglas comunes:**
- **Objetivos táctiles:** 44 × 44 px como mínimo y 8 px entre franjas, para no tocar la hora de al lado.
- **Acción principal:** botón fijo abajo, alcanzable con el pulgar; una sola acción principal por pantalla.
- **Una columna:** sin tablas de varias columnas ni scroll horizontal, también con zoom al 200 % (WCAG 1.4.10).
- **Franjas:** botones con la hora en texto (`08:00`), nunca solo un color.
- **Texto:** 16 px como mínimo en los campos, para que el teléfono no haga zoom al enfocarlos.
- **Foco visible:** contorno de 3 px de alto contraste en todo control (H9 de la Semana 9).
- **Mensajes:** errores y estados visibles en la pantalla con `role="status"`, nunca solo en un aviso que desaparece.
- **Datos:** la disponibilidad no muestra datos de pacientes (RNF-MED-03), así que puede verse en un teléfono sin exponerlos.
