# RF/RNF y criterios de aceptación — Semana 2 (OCP)

**Módulo:** `ASII-27 - Reportes operativos exportables y filtros administrativos` | **Issue:** `#210`
**Proceso:** filtrado y exportación reproducible de un reporte operativo. **Principio aplicado:** Open/Closed (OCP).

## 1. Requisitos funcionales (RF)

| ID | Requisito |
|---|---|
| `RF-01` | El sistema debe permitir aplicar uno o más filtros administrativos (rango de fechas, estado, sala) a un reporte operativo antes de exportarlo. |
| `RF-02` | El sistema debe permitir exportar el reporte filtrado en al menos un formato estable (CSV) y admitir formatos adicionales. |
| `RF-03` | El sistema debe permitir incorporar un **nuevo tipo de filtro** sin modificar la clase que orquesta la generación del reporte. |
| `RF-04` | El sistema debe permitir incorporar un **nuevo formato de exportación** sin modificar la clase que orquesta la generación. |
| `RF-05` | Los mismos criterios sobre los mismos datos deben producir el mismo contenido exportado (reproducibilidad). |
| `RF-06` | Ante un formato no registrado, el sistema debe informar el error con una excepción controlada, sin afectar el filtrado ya ejecutado. |

## 2. Requisitos no funcionales (RNF)

| ID | Requisito | Relación con OCP |
|---|---|---|
| `RNF-01` | Agregar un filtro o un formato nuevo debe implicar **crear una clase nueva y registrarla**, sin editar clases ya probadas. | Definición operativa de OCP: abierto a extensión, cerrado a modificación. |
| `RNF-02` | Cada filtro y cada exportador debe poder probarse de forma aislada. | Se depende de abstracciones (`ReportFilter`, `ReportExporter`), no de implementaciones. |
| `RNF-03` | El orquestador no debe contener nombres concretos de formato (`'csv'`, `'pdf'`) ni de filtro en su lógica de control. | Evita reintroducir los condicionales `if/elseif` del diseño "antes". |
| `RNF-04` | La capa de abstracción no debe degradar de forma perceptible la exportación de hasta 5 000 filas. | Verifica que OCP no añada sobrecosto relevante. |

## 3. Criterios de aceptación

- **`AC-01` (filtrado combinado):** con datos ficticios, al aplicar simultáneamente `estado` y `desde`, el resultado contiene solo los registros que cumplen ambas condiciones.
- **`AC-02` (exportación soportada):** para un formato registrado, se genera el contenido sin errores y con los mismos registros filtrados.
- **`AC-03` (extensión de filtro sin modificación):** al agregar `FiltroPorSala` y registrarlo, el filtro se aplica **sin modificar `ReportesService`** (verificable en el diff del commit).
- **`AC-04` (extensión de formato sin modificación):** al agregar `ExportadorExcel` y registrarlo, el formato funciona **sin modificar `ReportesService`**.
- **`AC-05` (formato no soportado):** un formato no registrado produce una excepción controlada que identifica el formato, sin afectar el filtrado previo.
- **`AC-06` (reproducibilidad):** los mismos criterios aplicados dos veces sobre los mismos datos producen contenido idéntico.

## 4. Trazabilidad con la evidencia

| Criterio | Evidencia |
|---|---|
| `AC-01`, `AC-06` | `evidencia/demo-ocp.php` → salida `DESPUÉS csv` y `DESPUÉS reproducible: OK` |
| `AC-02`, `AC-04` | `evidencia/demo-ocp.php` → `DESPUÉS xlsx (nuevo, sin tocar el orquestador)` |
| `AC-03`, `AC-04` | `solid-ocp.md` §5 (extensión) + separación de commits (clases nuevas frente a `ReportesService`) |
| `AC-05` | `evidencia/demo-ocp.php` → `DESPUÉS pdf: excepción controlada` |
