# Centro de documentacion y manuales por rol - Semana 9

> Tema guia: **Evaluacion del diseno, usabilidad y accesibilidad**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
## Entregables

- [Informe de evaluacion de usabilidad](./informe-usabilidad.md): recorrido cognitivo y analisis heuristico.
- [Cumplimiento de accesibilidad WCAG 2.2 AA](./accesibilidad-wcag.md) con 16 criterios.
- Evidencia ejecutable: [`evidencia/contraste.php`](./evidencia/contraste.php) y [`evidencia/auditoria.php`](./evidencia/auditoria.php).

## Resumen de resultados

- **Heuristicas:** 5 hallazgos; 1 de severidad alta (>= 3) que se corrigen en la Semana 11.
- **WCAG 2.2 AA:** 2 criterio(s) parcial(es); el resto cumple en el prototipo.
- **Contraste:** 12 pares de la guia de estilos, todos sobre el minimo.

## Comandos

```bash
cd docs/asii-24-centro-documentacion/semana-09
php evidencia/contraste.php
php evidencia/auditoria.php      # audita ../semana-08/prototipo/index.html (PR de la Semana 8)
```

La salida completa de ambos comandos esta en [accesibilidad-wcag.md](./accesibilidad-wcag.md).
