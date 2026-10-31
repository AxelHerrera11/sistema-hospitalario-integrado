# Reportes exportables - Semana 7

> Tema guia: **Diseno de componentes y refactorizacion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- [Diagrama de componentes backend/frontend](./01-componentes.svg) | [fuente](./fuentes/01-componentes.puml)
- Contratos de entrada/salida por componente (tabla abajo).
- Refactor ejecutable: [`antes.php`](./refactor/antes.php), [`despues.php`](./refactor/despues.php) y
  [`verificar.php`](./refactor/verificar.php), que prueba que ambos producen la misma salida.

## Componentes

El frontend se organiza en vista -> componentes -> store (Pinia) -> cliente API; el backend en
controlador -> casos de uso -> entidad -> Repository -> adaptador. La unica dependencia entre ambos
es el contrato REST de la Semana 5, por lo que cada lado puede cambiar sin romper al otro.

## Contratos de entrada/salida

| Componente | Entrada | Salida | Errores |
|---|---|---|---|
| `FiltrosReporte.vue` | `date_from`, `date_to`, filtros del reporte | evento `aplicar(filtros)` | aviso si el rango supera 366 dias |
| `BotonExportar.vue` | `reporteId`, `filtros`, `formato` | descarga del archivo | mensaje si 406 `report_format_unsupported` |
| `reportesApi.js` | `GET /api/v1/reportes/{id}/export?format=` | archivo + `X-Report-Filters` | `{code, message}` |
| `ReporteController` | Request HTTP | 201/200 `{data}` | 422 `reporte_invalido`, 422 `periodo_requerido` |
| `Exportador` | `exportar(filas)` | `string` (archivo) | formato no soportado |
| `ReporteRepository` | `guardar(Reporte)` / `porPeriodo()` | `void` / `list<Reporte>` | `ErrorPersistenciaException` |

## Refactorizacion antes/despues

| Tecnica | Aplicacion |
|---|---|
| Replace Conditional with Polymorphism | cada rama del `if/elseif` se convierte en un `Exportador`. |
| Extract Superclass / Template Method | CSV y TSV comparten `ExportadorDelimitado`; solo cambia `separador()`. |
| Remove Duplicated Code | el bucle de filas existia dos veces; queda una. |
| Open/Closed | un formato nuevo se agrega en la lista de exportadores sin editar los existentes. |

**Justificacion:** el codigo *antes* mezclaba decisiones en condicionales anidados y duplicados, lo
que hace dificil agregar casos sin romper otros. El codigo *despues* deja cada regla en un lugar con
nombre propio y aplica Open/Closed (OCP). El refactor no cambia comportamiento: `verificar.php` ejecuta los
mismos escenarios contra ambas versiones y compara la salida.



## Evidencia

```bash
cd docs/asii-27-reportes/semana-07
php refactor/verificar.php
```

```text
  [IGUAL] csv -> "fecha,servicio,citas\n2026-11-02,Pediatria,14\n2026-11-03,Medicina,9\n"
  [IGUAL] tsv -> "fecha\tservicio\tcitas\n2026-11-02\tPediatria\t14\n2026-11-03\tMedicina\t9\n"
  [IGUAL] json -> "{\"data\":[{\"fecha\":\"2026-11-02\",\"servicio\":\"Pediatria\",\"citas\":14},{\"fecha\":\"2026-11-03\",\"servicio\":\"Medicina\",\"citas\":9}],\"total\":2}"
  [IGUAL] pdf (no soportado) -> "ERROR: report_format_unsupported"

Comportamiento: 4/4 escenarios iguales.
Antes:   22 lineas, anidamiento max. 3, 3 sentencias if.
Despues: 41 lineas, anidamiento max. 3, 1 sentencias if.
```

## Regeneracion de diagramas

```bash
java -jar <ruta-a-plantuml.jar> -tsvg docs/asii-27-reportes/semana-07/fuentes/*.puml -o ..
```
