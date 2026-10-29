# Gestion de citas medicas y agenda - Semana 5

> Tema guia: **Cliente-servidor, API REST, microservicios e integracion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-05` | **Issue:** [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207)
- **Proceso:** registro y consulta de citas medicas validando disponibilidad
- **Principio SOLID aplicado:** Dependency Inversion (DIP)
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
| `POST` | `/citas` | paciente, doctor, desde, hasta | 201 `data{id, estado}` | 409 `appointments_overlap`, 409 `cita_rango_invalido`, 422 `validation_failed` |
| `GET` | `/citas` | `paciente` (query, opcional) | 200 `data[]` | - |
| `*` | `otra ruta / metodo` | - | - | 404 `route_not_found`, 405 `method_not_allowed` |

Reglas REST aplicadas: recurso en plural y sustantivo, verbo HTTP con semantica (GET seguro e
idempotente, POST crea), codigos de estado estandar y `code` estable para el cliente.

## Microservicios e integracion

Citas es un **contexto acotado** con datos propios (`appointments`) y una regla fuerte (anti-solape). Se mantiene dentro del monolito modular Laravel, pero con frontera de microservicio: solo se integra por contrato HTTP y nunca leyendo tablas de Pacientes o Medicos. Si la carga de agenda crece, el modulo puede extraerse sin cambiar a sus consumidores.

| Rol | Modulo | Contrato | Uso |
|---|---|---|---|
| Proveedor | ASII-03 Pacientes | `GET /pacientes/{id}` | validar que el paciente exista y pertenezca al tenant |
| Proveedor | ASII-04 Medicos | `GET /medicos/{id}/disponibilidad` | consultar horario antes de agendar |
| Consumidor | ASII-26 Dashboard base | `GET /citas` | tarjeta de citas del dia |
| Consumidor | ASII-27 Reportes | `GET /citas` | reporte operativo de citas por periodo |

Regla de integracion: ningun modulo lee las tablas de otro; toda dependencia pasa por un
contrato API versionado (`/api/v1`) a traves del gateway, con JWT y `X-Tenant-ID`.

## Requisitos y criterios de aceptacion

- **RF-01**: exponer `POST /citas` y `GET /citas` sobre el Micro-HIS de la Semana 4.
- **RF-02**: validar campos obligatorios en el borde HTTP y responder `422 validation_failed`.
- **RF-03**: traducir errores de dominio (solape, rango) al sobre comun `{code, message}`.
- **RF-04**: filtrar el listado por paciente.
- **RNF-01**: sobre comun de ASII-24 sin trazas ni SQL; PHP 8.2+ sin framework; el borde REST no tiene reglas.
- **AC-01**: cada ruta del contrato responde el status documentado (pruebas de contrato en verde).
- **AC-02**: rutas desconocidas responden `404 route_not_found` y metodos no soportados `405`.
- **AC-03**: `contrato-api.yaml` declara cada ruta y status que las pruebas verifican.

## Evidencia

```bash
cd docs/asii-05-citas/semana-05
php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php
```

```text
  [OK] ruta desconocida responde 404 con el sobre comun
  [OK] POST /citas camino feliz responde 201 con data
  [OK] POST /citas solapada responde 409 appointments_overlap
  [OK] POST /citas sin campos responde 422 validation_failed
  [OK] GET /citas filtra por paciente y responde 200
  [OK] el contrato declara las rutas y status probados

Resumen: 6 pruebas, 0 fallidas.
```

Prueba manual con el servidor embebido (`MICRO_HIS_DSN=sqlite:storage.sqlite` para persistir entre peticiones):

```bash
php -S localhost:8081 -t api/public
curl -s -X POST localhost:8081/api/v1/citas -H 'Content-Type: application/json' -d '{"paciente":"P-1","doctor":"D-1","desde":"2026-11-02T09:00:00-06:00","hasta":"2026-11-02T09:30:00-06:00"}'
curl -s 'localhost:8081/api/v1/citas?paciente=P-1'
```

## Plan de trabajo (issue, rama, worktree y PR)

| Paso | Detalle |
|---|---|
| Issue | [#207](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/207) (`ASII-05`), checklist de la Semana 5 |
| Rama | `feature/asii-05-semana-05-api-rest-lis671` |
| Base | apilada sobre `feature/asii-05-05-repository-mvc-lis671` (PR #1182), porque reutiliza el Micro-HIS de la Semana 4 |
| Worktree | `git worktree add ../shi-asii-05-citas -b feature/asii-05-semana-05-api-rest-lis671 feature/asii-05-05-repository-mvc-lis671` |
| PR | hacia `develop` con `Refs #207`, **se abre cuando #1182 este fusionado** y la rama se actualice con `origin/develop` |
