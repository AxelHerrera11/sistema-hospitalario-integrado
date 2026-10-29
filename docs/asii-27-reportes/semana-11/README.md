# Reportes exportables - Semana 11

> Tema guia: **Mejores practicas para diseno movil y web**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- Prototipo navegable responsive: [`prototipo/index.html`](./prototipo/index.html) (desktop y movil).
- Vista lado a lado 1024 px / 375 px: [`prototipo/comparar.html`](./prototipo/comparar.html).
- [Camino feliz y errores criticos](./01-camino-feliz-errores.svg) | [fuente](./fuentes/01-camino-feliz-errores.puml)

Pantallas: `#filtros`, `#previa`, `#exportado`, `#err-rango`, `#err-formato`, `#sin-datos`, `#err-sesion`, `#errores`.

## Camino feliz

Reportes -> Filtros -> Vista previa (paginada) -> Exportar CSV -> Exportacion lista -> Vista previa con los mismos filtros

## Escenarios de error critico

| Escenario | Disparador | Pantalla | Respuesta API | Recuperacion |
|---|---|---|---|---|
| Rango excesivo | rango > 366 dias | `#err-rango` | 422 `report_range_too_long` | error al cambiar la fecha con el limite calculado |
| Formato no soportado | se pide PDF o XLSX | `#err-formato` | 406 `report_format_unsupported` | alternativa CSV en un clic |
| Sin filas | filtros sin resultados | `#sin-datos` | 200 con lista vacia | volver a filtros |
| Sesion expirada | JWT vencido al exportar | `#err-sesion` | 401 `unauthenticated` | filtros conservados |

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

- HE-01 (sev 3): el motivo del PDF deshabilitado es texto visible ligado con `aria-describedby`.
- HE-02: el limite de 366 dias se valida en linea.
- HE-03: tras exportar se vuelve con los mismos filtros.
- HE-04: total de filas y pagina visibles.

**Re-auditoria:** la auditoria automatica de la Semana 9 sobre este prototipo da **12 chequeos, 0 a revisar**:

```bash
php ../semana-09/evidencia/auditoria-accesibilidad.php prototipo/index.html
```

## Como revisarlo

Abrir `prototipo/index.html`, seguir el camino feliz desde el menu y luego la entrada **Errores**; en
movil, usar `comparar.html` o DevTools a 375 x 812.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-11/fuentes/*.puml -o ..
```
