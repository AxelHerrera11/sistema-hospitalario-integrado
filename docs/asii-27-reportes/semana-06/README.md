# Reportes exportables - Semana 6

> Tema guia: **Primera evaluacion parcial: defensa teorica y caso practico de arquitectura y diseno**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- [Presentacion (7 diapositivas)](./presentacion.md)
- [Diagrama trazable RF -> CU -> componente -> prueba](./01-trazabilidad.svg) | [fuente](./fuentes/01-trazabilidad.puml)
- Matriz decision -> evidencia y resolucion del cambio practico (este documento).

## Defensa teorica (guion)

1. **Problema:** Los reportes operativos se arman a mano copiando datos de varios modulos; no son reproducibles ni se sabe con que filtros se generaron.
2. **Actores:** Administracion (genera y exporta), Jefaturas (consultan), Auditoria (verifica reproducibilidad).
3. **Por que capas:** separar la regla de negocio de HTTP y SQL permite probarla sola y cambiar la
   infraestructura sin tocarla.
4. **Por que Open/Closed (OCP):** es el principio que mas protege la regla central del modulo (ver D-02).
5. **Como se integra:** por contrato API versionado; ningun modulo lee las tablas de otro.

## Matriz decision -> evidencia

| ID | Decision | Alternativa descartada | Justificacion | Evidencia |
|---|---|---|---|---|
| D-01 | Arquitectura en 4 capas | Script que consulta y exporta | el reporte se prueba sin BD ni HTTP | [Semana 3: capas](../semana-03/01-capas.svg) |
| D-02 | OCP: interfaz `Exportador` (CSV, JSON) | `if/else` por formato | agregar formato = agregar clase | [Semana 2: OCP](../semana-02/solid-ocp.md) |
| D-03 | Reporte como instantanea inmutable | Recalcular en cada descarga | reproducibilidad para auditoria | [Semana 3: Domain](../semana-03/micro-his/src/Domain/Reporte.php) |
| D-04 | Contrato de filtros y exportacion antes del codigo | Filtros por reporte sin acuerdo | capa de filtros comun con Dashboard | [Contrato filtros](../contrato-filtros-export.md) |
| D-05 | Tenant por cabecera, nunca como filtro | Parametro `tenant` en la URL | impide pedir datos de otro hospital | [Contrato filtros §3](../contrato-filtros-export.md) |
| D-06 | PDF sin dependencia hasta aprobacion (406) | Agregar libreria sin acuerdo | no se agregan dependencias sin el grupo | [Contrato filtros §5](../contrato-filtros-export.md) |

## Caso practico: resolucion de cambio

**Cambio solicitado:** Administracion pide exportar en **XLSX**.

**Analisis de impacto:** Se agrega `ExportadorXlsx implements Exportador` y se registra en el catalogo de formatos. No se modifica `ExportadorCsv`, `ExportadorJson`, el caso de uso ni el controlador (OCP). Mientras el grupo no apruebe la libreria, `format=xlsx` responde `406 report_format_unsupported`.

```php
final class ExportadorXlsx implements Exportador
{
    public function soporta(string $formato): bool { return $formato === 'xlsx'; }
    public function exportar(Reporte $reporte): string { /* libreria aprobada por el grupo */ }
}
```

**Prueba de aceptacion del cambio:** `format=csv` sigue igual (regresion); `format=xlsx` -> archivo valido o 406 si falta la dependencia.

| Capa | Cambia? |
|---|---|
| Presentation (controlador / API) | No |
| Application (caso de uso) | Solo si recibe la nueva politica por constructor |
| Domain | Si: la regla vive aqui |
| Infrastructure (Repository) | No |

## Criterios de evaluacion cubiertos

- Presentacion de 6-8 diapositivas: 7 en `presentacion.md`.
- Diagrama trazable: cada RF llega a un componente y a una prueba existente de la Semana 3.
- Matriz decision -> evidencia: 6 decisiones con enlace a artefactos ya fusionados.
- Cambio practico: impacto por capa, codigo propuesto y prueba de aceptacion.

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-06/fuentes/*.puml -o ..
```
