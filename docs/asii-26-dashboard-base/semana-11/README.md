# Tablero de indicadores (dashboard base) - Semana 11

> Tema guia: **Mejores practicas para diseno movil y web**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
## Entregables

- Prototipo navegable responsive: [`prototipo/index.html`](./prototipo/index.html) (desktop y movil).
- Vista lado a lado 1024 px / 375 px: [`prototipo/comparar.html`](./prototipo/comparar.html).
- [Camino feliz y errores criticos](./01-camino-feliz-errores.svg) | [fuente](./fuentes/01-camino-feliz-errores.puml)

Pantallas: `#inicio`, `#detalle`, `#cargando`, `#vacio`, `#err-servicio`, `#err-periodo`, `#err-sesion`, `#errores`.

## Camino feliz

Iniciar sesion -> Inicio (cargando -> tarjetas) -> Como se calcula -> Inicio

## Escenarios de error critico

| Escenario | Disparador | Pantalla | Respuesta API | Recuperacion |
|---|---|---|---|---|
| Servicio caido | Citas u Ocupacion no responden | `#err-servicio` | 503 / error de red | ultimos datos con hora y boton Reintentar |
| Periodo invalido | mes fuera de rango o vacio | `#err-periodo` | 422 `periodo_requerido` | error junto al campo con el formato esperado |
| Sin datos | periodo sin indicadores | `#vacio` | 200 con `total: 0` | enlace al periodo actual |
| Sesion expirada | JWT vencido | `#err-sesion` | 401 `unauthenticated` | volver al inicio tras iniciar sesion |

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

- HE-01 (sev 3): cada tarjeta indica "menor/mayor es mejor".
- HE-03: enlace "Como se calcula".
- HE-02: el cambio de periodo pasa por el estado cargando con el periodo en el titulo.

**Re-auditoria:** la auditoria automatica de la Semana 9 sobre este prototipo da **12 chequeos, 0 a revisar**:

```bash
php ../semana-09/evidencia/auditoria.php prototipo/index.html
```

## Como revisarlo

Abrir `prototipo/index.html`, seguir el camino feliz desde el menu y luego la entrada **Errores**; en
movil, usar `comparar.html` o DevTools a 375 x 812.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-26-dashboard-base/semana-11/fuentes/*.puml -o ..
```
