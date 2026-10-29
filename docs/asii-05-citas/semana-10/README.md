# Gestion de citas medicas y agenda - Semana 10

> Tema guia: **Diseno para movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- Propuesta responsive (este documento) y hoja de estilos mobile-first [`responsive.css`](./responsive.css).
- Adaptacion de interfaz: [movil 375 px](./01-movil.svg) | [fuente](./fuentes/01-movil.puml) y
  [escritorio](./02-escritorio.svg) | [fuente](./fuentes/02-escritorio.puml).
- Escenarios de uso movil con criterio de aceptacion.

## Estrategia

**Mobile-first progresivo:** se disena primero para la pantalla mas restringida (375 px, una mano,
red inestable) y se agregan columnas y paneles con `min-width`. El contenido y las acciones son los
mismos en todos los tamanos; solo cambia su disposicion (no se esconden funciones en movil).

| Punto de quiebre | Ancho | Navegacion | Contenido |
|---|---|---|---|
| Movil | < 768 px | barra inferior fija (3-4 accesos) | 1 columna, acciones a todo el ancho |
| Tablet | 768-1023 px | rail lateral de iconos | 2 columnas |
| Escritorio | >= 1024 px | menu lateral completo | tablas y paneles laterales |

## Adaptacion de la interfaz por pantalla

| Pantalla | Movil | Tablet | Escritorio |
|---|---|---|---|
| Agenda del dia | tarjetas apiladas; filtro por doctor en hoja inferior | 2 columnas de tarjetas | tabla completa con filtros visibles |
| Nueva cita | un campo por fila; franjas libres como carrusel horizontal | franjas en cuadricula | formulario en 2 columnas + agenda del doctor al lado |
| Detalle / cancelar | acciones apiladas a todo el ancho; confirmacion como hoja inferior | acciones en fila | dialogo modal centrado |

## Escenarios de uso movil

| ID | Contexto | Situacion | Criterio de aceptacion |
|---|---|---|---|
| EM-01 | Medico entre consultas | Revisa sus siguientes 3 citas en el pasillo con una mano. | Agenda filtrada a "mis citas", hora grande, sin scroll horizontal. |
| EM-02 | Recepcion movil en jornada de salud | Agenda citas desde una tablet con conexion intermitente. | Guarda borrador local y muestra "Sin conexion: se enviara al reconectar"; el servidor sigue validando el solape (409). |
| EM-03 | Coordinacion fuera del hospital | Cancela una cita por emergencia desde el celular. | Confirmacion en hoja inferior con boton de 48 px y deshacer durante 5 s antes de enviar. |

## Reglas de movilidad aplicadas

1. **Objetivos tactiles de 48 px** y separacion minima de 8 px (supera WCAG 2.5.8).
2. **Campos con `font-size: 16px`** para evitar el zoom automatico y teclado adecuado (`type=date`, `search`, `tel`).
3. **Zona del pulgar:** la accion principal queda abajo (`barra-accion` fija) y el menu en barra inferior.
4. **Red inestable:** estados de carga ligeros, reintento explicito y ningun dato clinico guardado sin cifrar en el dispositivo.
5. **Privacidad en pantallas pequenas:** se muestran iniciales o nombre corto en listas; el detalle requiere un toque.
6. **Orientacion libre** (WCAG 1.3.4) y `env(safe-area-inset-bottom)` para equipos con muesca.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-05-citas/semana-10/fuentes/*.puml -o ..
```
