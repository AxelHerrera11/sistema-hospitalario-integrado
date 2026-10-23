# Traslados, altas y liberación de camas — Semana 7

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** componentes backend y frontend, contratos de entrada y salida y un refactor justificado antes/después; separar UI, aplicación, dominio y persistencia sin ampliar el módulo.

## Contenido y cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Componentes backend con contratos | [componentes.md](./componentes.md) | Cumplido (implementados) |
| Componentes frontend con contratos | [componentes.md](./componentes.md) | **Diseño**, sin código de interfaz |
| Refactor justificado antes/después | [refactor.md](./refactor.md) y el código de este PR | Cumplido; corrige un defecto real |
| Separación por capas sin ampliar el módulo | `tests/arquitectura.php` y el diagrama | Cumplido |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

## Evidencia

- `php micro-his/traslados-altas/tests/run.php` → `OK: 95 verificaciones aprobadas`.
- `motivo: ["x"]` por HTTP: 201 con el código anterior (copia de `develop` extraída con `git archive`) y 422 con el nuevo.
