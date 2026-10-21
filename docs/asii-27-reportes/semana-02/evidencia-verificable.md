# Evidencia verificable — Semana 2 (OCP)

**Módulo:** `ASII-27` | **Issue:** `#210` | Entorno: PHP 8.2+, sin framework ni base de datos.

## 1. Demo ejecutable

```bash
php docs/asii-27-reportes/semana-02/evidencia/demo-ocp.php
```

Salida real:

```text
ANTES  csv: csv:1
ANTES  xlsx: Formato no soportado: xlsx (obliga a EDITAR la clase)
DESPUÉS csv:
folio,departamento,estado,fecha
A-3,laboratorio,abierta,2026-10-09
DESPUÉS xlsx (nuevo, orquestador intacto): xlsx:1
DESPUÉS reproducible: OK
DESPUÉS pdf: excepción controlada -> Formato no soportado: pdf
```

## 2. Lectura de la evidencia

| Observación | Criterio |
|---|---|
| `ANTES` solo reconoce `csv`/`pdf`; `xlsx` exige editar la clase | `AC-05` (motiva OCP) |
| `DESPUÉS` filtra por `estado` **y** `desde` a la vez (solo A-3) | `AC-01` |
| `DESPUÉS xlsx` funciona con clase nueva, orquestador intacto | `AC-02`, `AC-04` |
| Dos ejecuciones producen el mismo CSV | `AC-06` |
| Formato no registrado → excepción controlada | `AC-05` |

## 3. Extensión sin modificación y validaciones

Las clases nuevas (`FiltroPorEstado`, `FiltroPorRangoFecha`, `ExportadorExcel`) y `ReporteServiceDespues` se versionan en commits separados; el orquestador no menciona filtros ni formatos concretos (`RNF-03`).

```bash
git log --oneline origin/develop..HEAD
php -l docs/asii-27-reportes/semana-02/evidencia/demo-ocp.php   # No syntax errors
java -jar plantuml-1.2026.8.jar -tsvg docs/asii-27-reportes/semana-02/fuentes/*.puml -o ..
```

Los diagramas de clases se generan desde sus fuentes `.puml` (exit 0, 1 línea por SVG).
