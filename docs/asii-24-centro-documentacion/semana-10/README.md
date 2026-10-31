# Centro de documentacion y manuales por rol - Semana 10

> Tema guia: **Diseno para movilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
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
| Catalogo | buscador fijo + tarjetas en 1 columna | 2 columnas | 3 columnas + filtros por tipo |
| Manual | indice colapsable arriba; texto a 1 columna | indice colapsable | indice lateral fijo + contenido |
| Publicar | pasos (datos -> roles -> archivo) uno por pantalla | formulario unico | formulario en 2 columnas con vista previa |

## Escenarios de uso movil

| ID | Contexto | Situacion | Criterio de aceptacion |
|---|---|---|---|
| EM-01 | Enfermera en turno nocturno | Consulta el protocolo de alergias junto a la cama del paciente. | Busqueda con teclado del celular, tipografia >= 16 px y modo lectura sin columnas. |
| EM-02 | Personal en area sin cobertura | Necesita un manual ya consultado antes. | Ultimos 5 documentos disponibles sin conexion (cache del navegador) con su fecha de version. |
| EM-03 | Desarrollador revisando contrato API | Revisa un endpoint desde el celular durante una reunion. | OpenAPI en vista de lectura con bloques de codigo con scroll horizontal propio, no de la pagina. |

## Reglas de movilidad aplicadas

1. **Objetivos tactiles de 48 px** y separacion minima de 8 px (supera WCAG 2.5.8).
2. **Campos con `font-size: 16px`** para evitar el zoom automatico y teclado adecuado (`type=date`, `search`, `tel`).
3. **Zona del pulgar:** la accion principal queda abajo (`barra-accion` fija) y el menu en barra inferior.
4. **Red inestable:** estados de carga ligeros, reintento explicito y ningun dato clinico guardado sin cifrar en el dispositivo.
5. **Privacidad en pantallas pequenas:** se muestran iniciales o nombre corto en listas; el detalle requiere un toque.
6. **Orientacion libre** (WCAG 1.3.4) y `env(safe-area-inset-bottom)` para equipos con muesca.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-10/fuentes/*.puml -o ..
```
