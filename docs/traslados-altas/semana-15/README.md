# Traslados, altas y liberación de camas — Semana 15

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** matriz RF/RNF → prueba; al menos 3 pruebas unitarias, 2 de caja blanca y 4 de caja negra (ramas, condiciones, equivalencias, límites, permisos y errores); ejecutarlas y registrar los resultados y los defectos reales.

## Contenido y cumplimiento

| Pedido | Evidencia | Estado |
|---|---|---|
| Matriz RF/RNF → prueba | [matriz-pruebas.md](./matriz-pruebas.md) | Cumplido |
| ≥ 3 unitarias, ≥ 2 de caja blanca y ≥ 4 de caja negra | 4, 3 y 6 casos seleccionados | Cumplido |
| Ramas, condiciones, equivalencias, límites, permisos y errores | Columnas «Técnica» de la matriz | Cumplido |
| Ejecución y resultados | [defectos.md](./defectos.md) y [evidencia/](./evidencia/) | Cumplido |
| Defectos reales | D1–D7; D5 hallado esta semana con concurrencia real | Cumplido |
| Concurrencia con conexiones independientes (M7) | `tests/concurrencia.php` (#895): 20/20 | Cumplido |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

## Reproducir

```bash
php micro-his/traslados-altas/tests/run.php
node --test micro-his/traslados-altas/tests/ui-mensajes.test.mjs
# Con una base HOSPITAL desechable migrada (nombre terminado en _test):
TRASLADOS_PG_DSN="pgsql:host=127.0.0.1;port=55433;dbname=<base>_test" TRASLADOS_PG_PASSWORD=<clave local> php micro-his/traslados-altas/tests/postgres.php
TRASLADOS_PG_DSN=… TRASLADOS_PG_PASSWORD=… php micro-his/traslados-altas/tests/concurrencia.php 10
```
