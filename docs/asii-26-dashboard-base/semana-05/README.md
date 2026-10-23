# Tablero de indicadores (dashboard base) - Semana 5

> Tema guia: **Cliente-servidor, API REST, microservicios e integracion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-26` | **Issue:** [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209)
- **Proceso:** consulta del tablero de indicadores por periodo
- **Principio SOLID aplicado:** Single Responsibility (SRP)
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
| `GET` | `/tablero` | `periodo` (query, obligatorio) | 200 `data{periodo,total,cumplen_meta,indicadores[]}` | 422 `periodo_requerido` |
| `POST/PUT/DELETE` | `/tablero` | - | - | 405 `method_not_allowed` (solo lectura) |
| `*` | `otra ruta` | - | - | 404 `route_not_found` |

Reglas REST aplicadas: recurso en plural y sustantivo, verbo HTTP con semantica (GET seguro e
idempotente, POST crea), codigos de estado estandar y `code` estable para el cliente.

## Microservicios e integracion

El dashboard es un **agregador (API composition)**: no es dueno de los datos clinicos, los compone desde contratos de otros modulos en un read model. Por eso el recurso es de solo lectura (`405` en escritura) y no se conecta a tablas ajenas. Queda en el monolito modular; un BFF separado solo se justifica si la composicion se vuelve costosa.

| Rol | Modulo | Contrato | Uso |
|---|---|---|---|
| Proveedor | ASII-05 Citas | `GET /citas` | citas del dia |
| Proveedor | ASII-09 Ocupacion | `GET /ocupacion/resumen` | porcentaje de camas ocupadas |
| Proveedor | ASII-02 RBAC | permisos de la sesion | filtrar tarjetas y menu (falla cerrada) |
| Consumidor | SPA Vue (layout comun) | `GET /tablero?periodo=` | pantalla de inicio de cada rol |

Regla de integracion: ningun modulo lee las tablas de otro; toda dependencia pasa por un
contrato API versionado (`/api/v1`) a traves del gateway, con JWT y `X-Tenant-ID`.

## Requisitos y criterios de aceptacion

- **RF-01**: exponer `GET /tablero` sobre el Micro-HIS de la Semana 4.
- **RF-02**: responder `422 periodo_requerido` en el sobre comun cuando falta el periodo.
- **RF-03**: rechazar escrituras con `405` (el tablero es un read model).
- **RF-04**: documentar la composicion con Citas y Ocupacion por contrato.
- **RNF-01**: sobre comun de ASII-24 sin trazas ni SQL; PHP 8.2+ sin framework; el borde REST no tiene reglas.
- **AC-01**: cada ruta del contrato responde el status documentado (pruebas de contrato en verde).
- **AC-02**: rutas desconocidas responden `404 route_not_found` y metodos no soportados `405`.
- **AC-03**: `contrato-api.yaml` declara cada ruta y status que las pruebas verifican.

## Evidencia

```bash
cd docs/asii-26-dashboard-base/semana-05
php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php
```

```text
  [OK] ruta desconocida responde 404 con el sobre comun
  [OK] GET /tablero responde 200 con el read model del periodo
  [OK] GET /tablero sin periodo responde 422 periodo_requerido
  [OK] POST /tablero responde 405 (recurso de solo lectura)
  [OK] el contrato declara las rutas y status probados

Resumen: 5 pruebas, 0 fallidas.
```

Prueba manual con el servidor embebido (`MICRO_HIS_DSN=sqlite:storage.sqlite` para persistir entre peticiones):

```bash
php -S localhost:8081 -t api/public
curl -s 'localhost:8081/api/v1/tablero?periodo=2026-11'
curl -s -X POST localhost:8081/api/v1/tablero
```

## Plan de trabajo (issue, rama, worktree y PR)

| Paso | Detalle |
|---|---|
| Issue | [#209](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/209) (`ASII-26`), checklist de la Semana 5 |
| Rama | `feature/asii-26-semana-05-api-rest-lis671` |
| Base | apilada sobre `feature/asii-26-07-repository-mvc-lis671` (PR #1184), porque reutiliza el Micro-HIS de la Semana 4 |
| Worktree | `git worktree add ../shi-asii-26-dashboard-base -b feature/asii-26-semana-05-api-rest-lis671 feature/asii-26-07-repository-mvc-lis671` |
| PR | hacia `develop` con `Refs #209`, **se abre cuando #1184 este fusionado** y la rama se actualice con `origin/develop` |
