# Reportes exportables - Semana 5

> Tema guia: **Cliente-servidor, API REST, microservicios e integracion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-27` | **Issue:** [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210)
- **Proceso:** generacion y consulta de reportes operativos por periodo
- **Principio SOLID aplicado:** Open/Closed (OCP)
## Entregables

- [Contrato API (OpenAPI 3.0)](./contrato-api.yaml) del Micro-HIS.
- [Cliente-servidor e integracion](./01-cliente-servidor.svg) | [fuente](./fuentes/01-cliente-servidor.puml)
- [Secuencia de la solicitud REST](./02-secuencia-api.svg) | [fuente](./fuentes/02-secuencia-api.puml)
- Borde REST ejecutable en [`api/`](./api/) y pruebas de contrato en [`api/tests/`](./api/tests/).
- Plan de trabajo (issue, rama, worktree y PR) al final. Regenerar SVG: `java -jar plantuml.jar -tsvg fuentes/*.puml -o ..`

## Estilo cliente-servidor

El cliente (SPA Vue o Postman) solo conoce el contrato HTTP; el servidor (Micro-HIS) concentra
reglas y datos. El borde REST de esta semana **reutiliza sin cambios** el controlador, los casos
de uso y el Repository de la Semana 4: el `Router` solo mapea `METODO + ruta`, valida campos
obligatorios y aplica el sobre comun de ASII-24 (`{data}` en exito, `{code, message}` en error).

## API REST del modulo

| Metodo | Ruta | Entrada | Exito | Errores |
|---|---|---|---|---|
| `POST` | `/reportes` | periodo, datos{} | 201 `data{id}` | 422 `reporte_invalido` |
| `GET` | `/reportes` | `periodo` (query, obligatorio) | 200 `data{reportes[]}` | 422 `periodo_requerido` |
| `*` | `otra ruta / metodo` | - | - | 404 `route_not_found`, 405 `method_not_allowed` |

Reglas REST aplicadas: recurso en plural y sustantivo, verbo HTTP con semantica (GET seguro e
idempotente, POST crea), codigos de estado estandar y `code` estable para el cliente.

## Microservicios e integracion

Reportes es un **consumidor de lectura** de varios contextos. Se integra por contrato y genera instantaneas inmutables, por lo que puede ejecutarse como proceso asincrono (cola) sin bloquear a los modulos fuente. Se mantiene en el monolito modular; la frontera de API y el patron Exportador (OCP) permiten separarlo como worker si el volumen crece.

| Rol | Modulo | Contrato | Uso |
|---|---|---|---|
| Proveedor | ASII-05 Citas | `GET /citas` | fuente del reporte operativo de citas |
| Proveedor | ASII-09 Ocupacion | `GET /ocupacion/resumen` | fuente del reporte de ocupacion |
| Proveedor | ASII-02 RBAC | permisos `reportes.ver` / `reportes.exportar` | autorizar consulta y descarga |
| Consumidor | Administracion (SPA) | `GET /reportes`, `/export` | descarga CSV reproducible |

Regla de integracion: ningun modulo lee las tablas de otro; toda dependencia pasa por un
contrato API versionado (`/api/v1`) a traves del gateway, con JWT y `X-Tenant-ID`.

## Resiliencia, observabilidad, consistencia y migracion (propuesta)

> **Propuesta de diseno** redactada durante la integracion del PR a partir del contrato y la arquitectura de esta semana. No esta implementada ni probada y la autora no la ha confirmado como decision del modulo.

| Aspecto | Propuesta para reportes operativos |
|---|---|
| Resiliencia | Un reporte es una instantanea inmutable: si un proveedor (Citas, Ocupacion) falla durante la generacion, no se guarda un reporte parcial como valido; se responde un error reintentable y el usuario lo genera de nuevo. `POST /reportes` con `Idempotency-Key` evita duplicar la instantanea en reintentos. Para reportes grandes, generacion asincrona con estado (`pendiente`, `listo`, `fallido`). |
| Observabilidad | `X-Correlation-ID`; registro de quien genera o exporta, con los filtros aplicados del contrato de filtros, sin el contenido del reporte; metricas de duracion de generacion, tamano y fallos por proveedor. |
| Consistencia | Consistencia en el punto de corte: el reporte refleja los datos de los proveedores al momento de generarse y debe declararlo (periodo y hora de corte). Los cambios posteriores en los modulos fuente no lo alteran, lo que garantiza la reproducibilidad. |
| Migracion razonada | Se mantiene en el monolito modular. Disparador medible para un worker separado: tiempo de generacion por encima del objetivo o cola de solicitudes creciente. Pasos: (1) mover `GenerarReporte` a un worker que consume una cola; (2) `POST /reportes` pasaria a `202` con el id (cambio de contrato a versionar); (3) `GET /reportes` no cambia; (4) para revertir, volver a la generacion sincrona. |

## Requisitos y criterios de aceptacion

- **RF-01**: exponer `POST /reportes` y `GET /reportes` sobre el Micro-HIS de la Semana 4.
- **RF-02**: traducir `reporte_invalido` y `periodo_requerido` al sobre comun `{code, message}`.
- **RF-03**: listar solo los reportes del periodo solicitado.
- **RF-04**: documentar las fuentes (Citas, Ocupacion) como contratos, no como tablas.
- **RNF-01**: sobre comun de ASII-24 sin trazas ni SQL; PHP 8.2+ sin framework; el borde REST no tiene reglas.
- **AC-01**: cada ruta del contrato responde el status documentado (pruebas de contrato en verde).
- **AC-02**: rutas desconocidas responden `404 route_not_found` y metodos no soportados `405`.
- **AC-03**: `contrato-api.yaml` declara cada ruta y status que las pruebas verifican.

## Evidencia

```bash
cd docs/asii-27-reportes/semana-05
php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php
```

```text
  [OK] ruta desconocida responde 404 con el sobre comun
  [OK] POST /reportes responde 201 con data.id
  [OK] POST /reportes sin periodo responde 422 reporte_invalido
  [OK] GET /reportes lista solo el periodo pedido
  [OK] GET /reportes sin periodo responde 422 periodo_requerido
  [OK] el contrato declara las rutas y status probados

Resumen: 6 pruebas, 0 fallidas.
```

Prueba manual con el servidor embebido (`MICRO_HIS_DSN=sqlite:storage.sqlite` para persistir entre peticiones):

```bash
php -S localhost:8081 -t api/public
curl -s -X POST localhost:8081/api/v1/reportes -H 'Content-Type: application/json' -d '{"periodo":"2026-11","datos":{"atendidos":12}}'
curl -s 'localhost:8081/api/v1/reportes?periodo=2026-11'
```

## Plan de trabajo (issue, rama, worktree y PR)

| Paso | Detalle |
|---|---|
| Issue | [#210](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/210) (`ASII-27`), checklist de la Semana 5 |
| Rama | `feature/asii-27-semana-05-api-rest-lis671` |
| Base | apilada sobre `feature/asii-27-06-repository-mvc-lis671` (PR #1185), porque reutiliza el Micro-HIS de la Semana 4 |
| Worktree | `git worktree add ../shi-asii-27-reportes-exportables -b feature/asii-27-semana-05-api-rest-lis671 feature/asii-27-06-repository-mvc-lis671` |
| PR | hacia `develop` con `Refs #210`, **se abre cuando #1185 este fusionado** y la rama se actualice con `origin/develop` |
