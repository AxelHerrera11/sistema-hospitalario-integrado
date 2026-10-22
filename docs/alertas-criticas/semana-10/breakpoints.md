# Breakpoints y reglas de diseño responsive

| Breakpoint | Ancho | Dispositivo típico en el hospital | Layout |
|---|---|---|---|
| `sm` | < 640 px | Teléfono del personal en sala | Una columna; acción principal fija abajo |
| `md` | 640–1023 px | Tablet en el carro de enfermería | Dos columnas cuando hay lista y detalle |
| `lg` | ≥ 1024 px | Computadora de la estación | Layout de escritorio de la Semana 8 |

**Reglas comunes:**
- **Objetivos táctiles:** de 44 × 44 px como mínimo y 8 px entre controles.
- **Acción principal:** botón fijo abajo (*sticky*) en `sm`, alcanzable con el pulgar.
- **Tablas:** pasan a tarjetas apiladas en `sm`, con los datos clínicos clave en la primera línea.
- **Texto:** 16 px como mínimo en los campos, para que iOS no haga zoom al enfocarlos, y zoom del navegador al 200 % sin scroll horizontal (WCAG 1.4.10).
- **Mensajes:** los de error y de estado van en la parte visible de la pantalla, nunca solo en un *toast* que desaparece.
- **Sin datos sensibles en notificaciones del sistema operativo:** solo «Nueva alerta» o «Acción requerida».
