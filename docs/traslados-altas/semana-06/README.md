# Traslados, altas y liberación de camas — Semana 6 (primer parcial)

## Ficha

- **Estudiante:** María Yamilet Lindo Pablo · **GitHub:** [`Yamilet1235`](https://github.com/Yamilet1235)
- **Módulo:** `ASII-08` · **Issue:** `Refs #9` · **Destino:** `develop`
- **Asignación:** 6–8 diapositivas, un diagrama trazable, una matriz decisión → evidencia y un cambio práctico documentado que conecte UML, RF/RNF, LSP, capas, Repository y API.

## Contenido

| Entregable | Archivo | Estado |
|---|---|---|
| 7 diapositivas | [presentacion.pdf](./presentacion.pdf) (portada y 7). Fuente Marp: [presentacion.md](./presentacion.md) | Cumplido |
| Diagrama trazable UML → RF/RNF → código | [01-trazabilidad.png](./01-trazabilidad.png), diapositiva 2. Fuente: [fuentes/01-trazabilidad.dot](./fuentes/01-trazabilidad.dot) | Cumplido |
| Matriz decisión → evidencia | [matriz-decisiones.md](./matriz-decisiones.md): 15 decisiones | Cumplido |
| Cambio práctico documentado | [cambio-practico.md](./cambio-practico.md): `Retry-After` en el 503 | Cumplido; **ejercicio simulado** |
| [Declaración de uso de IA](./DECLARACION_IA.md) | — | — |

**No se declara:** defensa oral, preguntas del docente ni evaluación. El cambio práctico lo propuso la autora como práctica.

## Evidencia

- `php micro-his/traslados-altas/tests/run.php` → `OK: 88 verificaciones aprobadas`.
- `php -S` con HOSPITAL inaccesible → `503` y `Retry-After: 5`.
