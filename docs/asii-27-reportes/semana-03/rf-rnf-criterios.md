# Requisitos y criterios de aceptación - ASII-27 Semana 3

## Requisitos funcionales

| ID | Requisito |
|---|---|
| RF-01 | Filtrar las filas del reporte por rango de fechas. |
| RF-02 | Rechazar un rango de fechas invertido. |
| RF-03 | Exportar el reporte a CSV y JSON. |
| RF-04 | Reportar un formato no soportado sin afectar el resto del flujo. |
| RF-05 | Permitir agregar un formato nuevo sin modificar el caso de uso (OCP). |

## Requisitos no funcionales

| ID | Requisito |
|---|---|
| RNF-01 | PHP 8.2+ sin framework ni Composer. |
| RNF-02 | El Dominio no depende de la infraestructura ni de la entrada CLI. |
| RNF-03 | El caso de uso depende de contratos, no de formatos concretos (OCP). |
| RNF-04 | Errores de dominio con mensajes estables (422/406). |
| RNF-05 | Sin credenciales ni datos reales en el repositorio. |

## Criterios de aceptación

- **AC-01**: un rango invertido lanza `FiltroInvalidoException` (`422 rango_fechas_invalido`).
- **AC-02**: solo se exportan las filas dentro del rango solicitado.
- **AC-03**: un formato desconocido lanza `FormatoNoSoportadoException` (`406 report_format_unsupported`).
- **AC-04**: un exportador nuevo funciona sin cambiar `GenerarReporte`.
- **AC-05**: el exportador JSON incluye el total de filas.
- **AC-06**: `src/Domain/` no contiene `PDO`, `$_GET`, `$_POST` ni `header(`.

## Trazabilidad

| Criterio | Artefacto | Prueba |
|---|---|---|
| AC-01 | `src/Domain/Reporte.php` | dominio: rechaza rango de fechas invertido |
| AC-02 | `src/Application/GenerarReporte.php` | aplicacion: camino feliz filtra y exporta csv |
| AC-03 | `src/Application/GenerarReporte.php` | aplicacion: formato no soportado |
| AC-04 | `src/Domain/Exportador.php` | ocp: un exportador nuevo funciona sin tocar el caso de uso |
| AC-05 | `src/Persistence/ExportadorJson.php` | aplicacion: json agrega el total |
| AC-06 | `src/Domain/` | revisión manual |
