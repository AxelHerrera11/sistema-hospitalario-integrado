# Tablero de indicadores (dashboard base) - Semana 10

> Tema guia: **Diseno para movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
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
| Layout | menu inferior con 4 accesos del rol + "Mas" | rail lateral de iconos (72 px) | menu lateral completo (220 px) |
| Inicio / tablero | 1 columna; indicadores que no cumplen primero | 2 columnas | 3 columnas en orden fijo |
| Estados de pagina | mensaje + boton a todo el ancho | igual | mensaje centrado en el area de contenido |

## Escenarios de uso movil

| ID | Contexto | Situacion | Criterio de aceptacion |
|---|---|---|---|
| EM-01 | Direccion medica en ronda | Revisa en 5 s si algun indicador esta en rojo. | Tarjetas "No cumple" arriba con icono + texto; carga del tablero < 2 s en 4G. |
| EM-02 | Jefatura en reunion | Muestra el tablero desde una tablet en horizontal. | 2 columnas, sin cortar textos; orientacion libre (WCAG 1.3.4). |
| EM-03 | Usuario con datos moviles limitados | Abre el HIS fuera del hospital. | Esqueleto de carga ligero, sin imagenes pesadas; estado de error con Reintentar si la red falla. |

## Reglas de movilidad aplicadas

1. **Objetivos tactiles de 48 px** y separacion minima de 8 px (supera WCAG 2.5.8).
2. **Campos con `font-size: 16px`** para evitar el zoom automatico y teclado adecuado (`type=date`, `search`, `tel`).
3. **Zona del pulgar:** la accion principal queda abajo (`barra-accion` fija) y el menu en barra inferior.
4. **Red inestable:** estados de carga ligeros, reintento explicito y ningun dato clinico guardado sin cifrar en el dispositivo.
5. **Privacidad en pantallas pequenas:** se muestran iniciales o nombre corto en listas; el detalle requiere un toque.
6. **Orientacion libre** (WCAG 1.3.4) y `env(safe-area-inset-bottom)` para equipos con muesca.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-10/fuentes/*.puml -o ..
```
