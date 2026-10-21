# Inventario de evidencias — Semana 11

Capturas regeneradas desde el prototipo revisado, el 16-10-2026. Chrome headless 155.0.8059.80. Son estados demostrativos, no tráfico ni resultados del backend.

| Archivo | Escenario y pantalla | Viewport de contenido |
|---|---|---:|
| `escritorio-camino-feliz.png` | Asignación 201, `#roles-exito` | 1440 × 1400 |
| `escritorio-error.png` | 403 genérico; actor `recepcionista` sin detalle técnico ni enlace de bitácora, `#denegado` | 1440 × 1400 |
| `movil-390-camino-feliz.png` | Asignación 201, `#roles-exito` | 390 × 836 |
| `movil-390-error.png` | 403 genérico; detalle y enlace ocultos al actor sin permiso de auditoría, `#denegado` | 390 × 911 |
| `escritorio-asignar-rol.png` | Formulario y permisos del rol, `#roles` | 1440 × 1400 |
| `escritorio-bitacora.png` | Dos eventos ficticios etiquetados como demo, `#bitacora` | 1440 × 1400 |

Los cuatro archivos principales son las dos primeras situaciones en ambos tamaños. Las capturas móviles se capturaron dentro de un iframe verificado con `innerWidth=390`, `matchMedia(max-width:430px)=true` y `scrollWidth=390`; luego se recortó la franja externa del navegador headless.

## Inspección visual

Se abrieron las seis imágenes y se confirmó que muestran contenido, caracteres legibles, estado correcto, mensaje de denegación sin permiso visible y trazabilidad; no hay pantallas vacías ni texto cortado. La denegación de `recepcionista` oculta detalle técnico/enlace de bitácora; la bitácora desktop presenta el registro de muestra y el evento de denegación con el mismo `trace_id`.
