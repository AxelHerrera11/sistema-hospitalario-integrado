# Centro de documentacion y manuales por rol - Semana 11

> Tema guia: **Mejores practicas para diseno movil y web**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
## Entregables

- Prototipo navegable responsive: [`prototipo/index.html`](./prototipo/index.html) (desktop y movil).
- Vista lado a lado 1024 px / 375 px: [`prototipo/comparar.html`](./prototipo/comparar.html).
- [Camino feliz y errores criticos](./01-camino-feliz-errores.svg) | [fuente](./fuentes/01-camino-feliz-errores.puml)

Pantallas: `#catalogo`, `#detalle`, `#publicar`, `#publicado`, `#sin-resultados`, `#err-roles`, `#err-permiso`, `#err-sesion`, `#errores`.

## Camino feliz

Catalogo -> Buscar -> Detalle -> Descargar; Publicar -> Revisar y publicar -> Documento publicado

## Escenarios de error critico

| Escenario | Disparador | Pantalla | Respuesta API | Recuperacion |
|---|---|---|---|---|
| Sin roles | se intenta publicar sin rol destinatario | `#err-roles` | 422 `documento_sin_roles` | error en linea antes de enviar; el boton explica que falta |
| Busqueda vacia | no hay coincidencias | `#sin-resultados` | 200 con lista vacia | sugerencias de terminos |
| Sin permiso | rol sin permiso de publicacion | `#err-permiso` | 403 `forbidden` | mensaje neutro (no revela permisos) y vuelta al catalogo |
| Sesion expirada | JWT vencido | `#err-sesion` | 401 `unauthenticated` | borrador local del formulario |

Los codigos coinciden con el contrato API (Semana 5) y el formato comun de errores de ASII-24.

## Mejores practicas aplicadas

| Practica | Como se aplica |
|---|---|
| Prevenir antes que corregir | se muestran solo opciones validas y se valida en linea |
| Errores accionables | que paso + que hacer, junto al campo, enlazado con `aria-describedby` |
| No perder trabajo | los datos se conservan en 409/422/401 y en fallas de red |
| Retroalimentacion inmediata | `role=status` en exitos, `role=alert` en errores, esqueleto al cargar |
| Mismo contenido en movil y web | un solo HTML responsive; en movil tablas -> tarjetas y botones a todo el ancho |
| Accesibilidad | `h1` en cada pantalla, foco al cambiar de pantalla, objetivos de 44 px, contraste AA |
| Seguridad en mensajes | 401/403 no revelan permisos ni existencia de recursos |

## Correcciones de la evaluacion de la Semana 9

- HE-01 (sev 3): roles validados en linea con mensaje visible.
- HE-02: estado "sin resultados" con sugerencias.
- HE-05: tamano del PDF visible.
- WCAG 3.3.1 y 1.3.1: errores en linea y `h1` en la confirmacion.

**Re-auditoria:** la auditoria automatica de la Semana 9 sobre este prototipo da **12 chequeos, 0 a revisar**:

```bash
php ../semana-09/evidencia/auditoria-accesibilidad.php prototipo/index.html
```

## Como revisarlo

Abrir `prototipo/index.html`, seguir el camino feliz desde el menu y luego la entrada **Errores**; en
movil, usar `comparar.html` o DevTools a 375 x 812.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-24-centro-documentacion/semana-11/fuentes/*.puml -o ..
```
