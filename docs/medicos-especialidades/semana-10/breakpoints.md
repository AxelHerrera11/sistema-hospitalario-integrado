# Breakpoints y reglas de diseño responsive

Se usan los mismos tres breakpoints que propone Admisión (ASII-07) en su Semana 10, para que las pantallas de Médicos, Citas y Admisión se comporten igual.

| Breakpoint | Ancho | Dispositivo típico en el hospital | Layout |
|---|---|---|---|
| `sm` | < 640 px | Teléfono del médico | Una columna; acción principal fija abajo |
| `md` | 640–1023 px | Tablet en el mostrador de recepción | Dos columnas: lista de médicos y franjas del elegido |
| `lg` | ≥ 1024 px | Computadora de recepción o de administración | Layout de escritorio de la Semana 8 |

**Reglas comunes:**
- **Objetivos táctiles:** 44 × 44 px como mínimo y 8 px entre franjas, para no tocar la hora de al lado.
- **Acción principal:** botón fijo abajo en `sm`, alcanzable con el pulgar.
- **Franjas:** botones con la hora en texto (`08:00`), nunca solo un color; las ocupadas no se muestran.
- **Texto:** 16 px como mínimo en los campos, para que iOS no haga zoom al enfocarlos, y zoom del navegador al 200 % sin scroll horizontal (WCAG 1.4.10).
- **Mensajes:** los de error y de estado van en la parte visible de la pantalla con `role="status"`, nunca solo en un aviso que desaparece.
- **Datos:** la disponibilidad no muestra datos de pacientes (RNF-MED-03), así que puede verse en una tablet de mostrador sin exponerlos.
