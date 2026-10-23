# Traslados, altas y liberación de camas — Semana 11

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** recorrido navegable en escritorio y móvil con camino feliz y error crítico; mapa de navegación y capturas reales de ambos formatos; identificar los datos y servicios simulados.

## Contenido y cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Recorrido navegable en escritorio y móvil | Prototipo `micro-his/traslados-altas/public/ui/` | Cumplido: 9/9 a 1024 y 375 px |
| Camino feliz | [prototipo.md](./prototipo.md): 4 pasos × 2 formatos | Cumplido |
| Error crítico | [prototipo.md](./prototipo.md#error-crítico-hospital-inaccesible): 503 en ambos formatos | Cumplido: 2/2 |
| Mapa de navegación | [prototipo.md](./prototipo.md#mapa-de-navegación) | Cumplido |
| Capturas reales | 10 PNG en `capturas/` | Cumplido |
| Datos y servicios simulados | [simulaciones.md](./simulaciones.md) | Cumplido |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

## Reproducir

Levante el prototipo como en la [Semana 8](../semana-08/README.md#cómo-reproducir) y ejecute `node ../semana-10/evidencia/recorrido-ui.mjs <base> <carpeta> 1024` (y con `375`). Para el error crítico, levante un segundo `php -S` con `TRASLADOS_PG_DSN` apuntando a un puerto sin base y ejecute `node evidencia/error-critico.mjs <base> <carpeta>`.
