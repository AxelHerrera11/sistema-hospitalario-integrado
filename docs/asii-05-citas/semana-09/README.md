# Gestion de citas medicas y agenda - Semana 9

> Tema guia: **Evaluacion del diseno, usabilidad y accesibilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
## Entregables

- [Informe de evaluacion de usabilidad](./informe-usabilidad.md): recorrido cognitivo y analisis heuristico.
- [Cumplimiento de accesibilidad WCAG 2.2 AA](./accesibilidad-wcag.md) con 16 criterios.
- Evidencia ejecutable: [`evidencia/contraste.php`](./evidencia/contraste.php) y [`evidencia/auditoria-accesibilidad.php`](./evidencia/auditoria-accesibilidad.php).

## Resumen de resultados

- **Heuristicas:** 5 hallazgos; 1 de severidad alta (>= 3) que se corrigen en la Semana 11.
- **WCAG 2.2 AA:** 2 criterio(s) parcial(es); el resto cumple en el prototipo.
- **Contraste:** 12 pares de la guia de estilos, todos sobre el minimo.

## Comandos

```bash
cd docs/asii-05-citas/semana-09
php evidencia/contraste.php
php evidencia/auditoria-accesibilidad.php      # audita ../semana-08/prototipo/index.html (PR de la Semana 8)
```

La salida completa de ambos comandos esta en [accesibilidad-wcag.md](./accesibilidad-wcag.md).
