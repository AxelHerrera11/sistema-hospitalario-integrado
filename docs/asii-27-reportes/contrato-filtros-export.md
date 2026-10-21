# Contrato de filtros y exportación de reportes — ASII-27

- **Versión:** 1.0 (propuesta, previa al código)
- **Fecha:** 18/10/2026 — publicación antes de la implementación, según el plan de integración 17–23 oct.
- **Base:** `/api/v1`, cabeceras estándar (`Authorization: Bearer`, `X-Tenant-ID`).
- **Principio del módulo:** OCP — los reportes se agregan sin modificar el núcleo de exportación.

## 1. Alcance y diferencias

- **ASII-27 (este módulo):** reportes **operativos exportables** (CSV, y PDF cuando el grupo apruebe una dependencia) con **filtros administrativos** reproducibles.
- **ASII-23 (Yamilet):** indicadores/analytics con fórmulas versionadas (`IND-*`, `TCI-*`); no se duplica aquí.
- **ASII-26 (dashboard):** ocupa la misma capa de filtros y estados visuales; la capa común es una propuesta que el grupo acuerda (reunión del mar 20).

## 2. Endpoints

| Método y ruta | Permiso | Descripción |
|---|---|---|
| `GET /reportes` | `reportes.ver` | Catálogo de reportes registrados: `id`, `nombre`, `filtros`, `formatos` |
| `GET /reportes/{id}` | `reportes.ver` | Vista previa en JSON (`data` + `meta`, misma paginación que el resto del sistema) |
| `GET /reportes/{id}/export` | `reportes.exportar` | Descarga; query `format=csv` (hoy) o `format=pdf` (pendiente de dependencia) |

Errores: `{message, code}` con `report_not_found` (404), `report_filter_invalid` (422), `report_format_unsupported` (406), `report_range_too_long` (422), `unauthenticated`/`forbidden` (401/403).

## 3. Filtros comunes (capa compartida)

| Parámetro | Regla |
|---|---|
| `date_from`, `date_to` | `Y-m-d`; al menos uno obligatorio; rango máximo **366 días** |
| `order` | ascendente por defecto, según `orden` declarado por cada reporte |
| `per_page` | solo vista previa; máx. 100 |

El tenant **no** es un filtro: lo impone la cabecera `X-Tenant-ID` (patrón de `TenantMiddleware`). Filtros adicionales se declaran por reporte (p. ej. `status` y `doctor_profile_id` para citas; `ward_id` para ocupación).

## 4. Formato CSV

- UTF-8 con BOM, separador `,`, encabezados en la primera fila, fechas ISO 8601.
- El archivo reproduce **exactamente** los filtros aplicados: la primera línea del cuerpo son los datos, no metadatos; los filtros se comunican en el header `X-Report-Filters` (JSON) para que la exportación sea reproducible.

## 5. PDF

`composer.json` no incluye ninguna librería de PDF (ni dompdf ni equivalente). Este contrato **no agrega dependencias**: `format=pdf` responde `report_format_unsupported` hasta que el grupo apruebe la librería (acuerdo a proponer el mar 20 junto con Yamilet y Javier).

## 6. Extensión sin modificar el núcleo (OCP)

```text
ReportDefinition (contrato: id, nombre, permiso, filtros[], columns[], rows(filtros))
        └─ CitasReportDefinition, OcupacionReportDefinition, ...

ReportExporter (contrato: supports(format), export(definition, rows, stream))
        ├─ CsvReportExporter      (núcleo existente, no se modifica)
        └─ PdfReportExporter      (futuro, sin tocar el CSV)
```

- Agregar un reporte = una clase `ReportDefinition` + registro en el catálogo; **no** se edita `CsvReportExporter` ni el controlador.
- Agregar un formato = un nuevo `ReportExporter`; el controlador solo resuelve `supports($format)`.

## 7. Datos fuente (sin tablas nuevas)

Reportes propuestos para la primera entrega:

| Reporte | Fuente (esquema `hospital`) |
|---|---|
| Citas por rango y estado | `appointments` (vía repositorio de ASII-05) |
| Ocupación por sala/cama | `wards`, `beds`, `bed_stays` |
| Estancias abiertas por paciente | `care_episodes`, `bed_stays`, `local_patients` |

## 8. Pendientes de acuerdos del grupo

1. Formato común de respuestas y errores `{message, code, trace_id}` (Yamilet/Javier, mar 20).
2. Librería de PDF y firma de la exportación.
3. Middleware `permission:reportes.*` con María (RBAC); hasta entonces, rutas bajo `tenant` + `jwt.auth`.
