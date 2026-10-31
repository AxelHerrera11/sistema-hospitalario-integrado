# Reportes exportables - Semana 10

> Tema guia: **Diseno para movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
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
| Catalogo de reportes | lista de 1 columna | 2 columnas | lista + descripcion lateral |
| Filtros | hoja inferior a pantalla completa; chips con lo aplicado | panel colapsable | panel fijo a la izquierda |
| Vista previa / exportar | tarjetas por fila; boton Exportar fijo abajo | tabla con scroll propio | tabla completa + paginacion |

## Escenarios de uso movil

| ID | Contexto | Situacion | Criterio de aceptacion |
|---|---|---|---|
| EM-01 | Jefatura fuera de oficina | Consulta el resumen de citas de la semana desde el celular. | Vista previa en tarjetas con totales arriba; no necesita descargar el CSV. |
| EM-02 | Administracion en tablet | Ajusta filtros y exporta para enviarlo por correo. | Chips con filtros aplicados visibles antes de exportar; nombre de archivo con el periodo. |
| EM-03 | Conexion lenta | Exporta un reporte grande. | Exportacion asincrona: "Te avisaremos cuando este listo", sin bloquear la pantalla. |

## Reglas de movilidad aplicadas

1. **Objetivos tactiles de 48 px** y separacion minima de 8 px (supera WCAG 2.5.8).
2. **Campos con `font-size: 16px`** para evitar el zoom automatico y teclado adecuado (`type=date`, `search`, `tel`).
3. **Zona del pulgar:** la accion principal queda abajo (`barra-accion` fija) y el menu en barra inferior.
4. **Red inestable:** estados de carga ligeros, reintento explicito y ningun dato clinico guardado sin cifrar en el dispositivo.
5. **Privacidad en pantallas pequenas:** se muestran iniciales o nombre corto en listas; el detalle requiere un toque.
6. **Orientacion libre** (WCAG 1.3.4) y `env(safe-area-inset-bottom)` para equipos con muesca.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-10/fuentes/*.puml -o ..
```
