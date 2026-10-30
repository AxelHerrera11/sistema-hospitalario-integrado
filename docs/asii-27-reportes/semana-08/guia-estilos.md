# Guia de estilos UI - ASII-27

Adopta el [contrato UI base](../../asii-26-dashboard-base/contrato-ui-base.md) del HIS (layout,
menu por permiso y estados de pagina) y lo concreta en tokens reutilizables por el modulo.

## Color

| Token | Valor | Uso |
|---|---|---|
| `--primario` | `#1d4ed8` | Acciones principales, enlaces, foco |
| `--primario-osc` | `#1e3a8a` | Hover de botones, encabezado |
| `--texto` | `#0f172a` | Texto principal |
| `--texto-2` | `#475569` | Texto secundario y etiquetas |
| `--fondo` | `#f8fafc` | Fondo de la aplicacion (contrato UI base) |
| `--superficie` | `#ffffff` | Tarjetas, formularios, tablas |
| `--borde` | `#cbd5e1` | Bordes y separadores (no transmite informacion) |
| `--exito` | `#15803d` | Confirmaciones y metas cumplidas |
| `--aviso` | `#b45309` | Advertencias y metas en riesgo |
| `--error` | `#b91c1c` | Errores y acciones destructivas |

Reglas: el color **nunca** es el unico portador de informacion (los estados llevan texto e icono);
el texto normal mantiene contraste >= 4.5:1 (se verifica en la Semana 9).

## Tipografia y espaciado

- Fuente del sistema (`system-ui`), base **16 px**, interlineado 1.5; `h1` 1.5rem, `h2` 1.15rem.
- Escala de espaciado: 4, 8, 12, 16, 24 px. Contenido maximo 960 px.

## Componentes

| Componente | Regla |
|---|---|
| Boton primario / secundario / peligro | alto minimo 44 px; un solo primario por pantalla; peligro solo en acciones destructivas |
| Campo de formulario | etiqueta visible siempre arriba, `*` para obligatorio, ayuda debajo, error en rojo con texto |
| Alerta | borde izquierdo de color + texto; `role="status"` para exito y `role="alert"` para error |
| Badge de estado | texto + color; nunca solo color |
| Tabla | encabezados `th`; en movil cada fila se vuelve tarjeta con etiqueta por celda |
| Foco | contorno de 3 px `--primario`, visible en todos los elementos interactivos |

**Especificos del modulo:** Catalogo de reportes, formulario de filtros con ayuda del limite de rango, tabla de vista previa con los filtros visibles y botones de exportacion (deshabilitado con motivo cuando el formato no esta disponible).

## Voz y mensajes

Mensajes en segunda persona, cortos y accionables: que paso + que hacer ("No se pudo cargar el tablero.
Reintentar"). Nunca se muestran codigos internos, SQL ni trazas.
