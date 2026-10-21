# Diseño SOLID — Open/Closed Principle (OCP)

**Módulo:** `ASII-27` | **Issue:** `#210` | **Proceso:** filtrado y exportación reproducible de un reporte operativo.

## 1. Problema

El flujo debe **abrirse a extensión** (nuevos reportes, filtros administrativos y formatos de exportación) y **cerrarse a modificación**: agregar un reporte, un filtro o un formato no debería obligar a editar y volver a probar la clase que genera el reporte.

## 2. Diseño "antes" (viola OCP)

Fuente: [`fuentes/01-diseno-antes.puml`](./fuentes/01-diseno-antes.puml) · SVG: [`01-diseno-antes.svg`](./01-diseno-antes.svg).

`ReportesService::exportar(id, filtros, formato)` concentra en un único método:

- la selección del reporte (`if id == 'citas'` / `'ocupacion'` …),
- cada filtro administrativo (un `if` por `status`, `ward_id`, rango de fechas…),
- cada formato (un `if/elseif` por `'csv'`, `'pdf'` …).

Cada reporte, filtro o formato nuevo obliga a **abrir y modificar** esa clase, con riesgo de romper lo que ya funcionaba. Es una violación directa de OCP.

## 3. Diseño "después" (aplica OCP)

Fuente: [`fuentes/02-diseno-despues.puml`](./fuentes/02-diseno-despues.puml) · SVG: [`02-diseno-despues.svg`](./02-diseno-despues.svg).

Se invierte la dependencia hacia abstracciones, en línea con el contrato publicado (`ReportDefinition`/`ReportExporter`):

- `ReportDefinition`: **qué** es el reporte (id, permiso, filtros y columnas declarados, `filas()`).
- `ReportFilter`: **cómo** se traduce una clave de filtro a la consulta.
- `ReportExporter`: **cómo** se serializa el resultado en cada formato.
- `ReportesService`: orquestador **cerrado a modificación**; sólo resuelve definición, aplica filtros y delega en el exportador.
- `ReportesServiceProvider`: registra las implementaciones (punto único de extensión).

## 4. Responsabilidades

| Componente | Responsabilidad única |
|---|---|
| `ReportDefinition` | Declarar metadatos y obtener las filas del reporte |
| `ReportFilter` | Aplicar una clave de filtro sobre la consulta |
| `ReportExporter` | Serializar las filas en un formato |
| `ReportesService` | Orquestar (resolver, filtrar, delegar); sin nombres de filtro/formato |
| `ReportesServiceProvider` | Registrar implementaciones |
| `ReportesController` | Traducir la petición HTTP y devolver la respuesta |

## 5. Cómo extender (sin modificar el núcleo)

| Extensión | Acción | No se modifica |
|---|---|---|
| Reporte nuevo | Nueva clase `ReportDefinition` + registro | `ReportesService`, exportadores |
| Filtro nuevo | Nueva clase `ReportFilter` + registro | `ReportesService`, otros filtros |
| Formato nuevo | Nueva clase `ReportExporter` + registro | `ReportesService`, `CsvReportExporter` |

## 6. Por qué cumple OCP

El orquestador depende sólo de las interfaces; el `foreach` sobre filtros y exportadores no menciona ningún tipo concreto. Agregar comportamiento = **añadir** clases y registrarlas, no **editar** el núcleo. La evidencia de extensión está en `evidencia/demo-ocp.php`, donde `ExportadorExcel` se incorpora sin tocar `ReportesService`.

## 7. Trazabilidad

Cubre `RF-03`, `RF-04`, `RNF-01`..`RNF-03` y los criterios `AC-03`/`AC-04` de [`rf-rnf-criterios.md`](./rf-rnf-criterios.md).

## 8. Alcance y exclusiones

Sólo se modela y evidencia el diseño (diagramas, RF/RNF y demo ejecutable). No se implementa Laravel completo, middleware de permisos, PDF real ni endpoints: `format=pdf` permanece como extensión futura y, según el contrato, responde formato no soportado hasta que el grupo apruebe una dependencia.
