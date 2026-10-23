# Centro de documentacion y manuales por rol - Semana 5

> Tema guia: **Cliente-servidor, API REST, microservicios e integracion**

## Ficha

- **Estudiante:** Lis Ivette Rosales Colindrez
- **GitHub:** [`Lis671`](https://github.com/Lis671)
- **Identificador:** `ASII-24` | **Issue:** [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208)
- **Proceso:** publicacion y consulta de documentacion y manuales diferenciados por rol
- **Principio SOLID aplicado:** Interface Segregation (ISP)
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
| `POST` | `/documentos` | titulo, roles[] | 201 `data{id}` | 422 `documento_sin_roles`, 422 `validation_failed` |
| `GET` | `/documentos` | `rol` (query, obligatorio) | 200 `data{documentos[]}` | 422 `validation_failed` |
| `*` | `otra ruta / metodo` | - | - | 404 `route_not_found`, 405 `method_not_allowed` |

Reglas REST aplicadas: recurso en plural y sustantivo, verbo HTTP con semantica (GET seguro e
idempotente, POST crea), codigos de estado estandar y `code` estable para el cliente.

## Microservicios e integracion

El centro de documentacion es un **servicio de soporte** de baja escritura y alta lectura. Se integra por contrato y su catalogo puede servirse como contenido estatico (OpenAPI, manuales) cacheado por el gateway. No necesita base propia compleja: se mantiene en el monolito modular con la frontera de API definida, lo que permite publicarlo aparte si crece.

| Rol | Modulo | Contrato | Uso |
|---|---|---|---|
| Proveedor | ASII-01 Usuarios/tenants | `GET /auth/me` | obtener el rol de la sesion (no se acepta el rol del cliente en produccion) |
| Proveedor | ASII-02 RBAC | permiso `documentacion.publicar` | autorizar la publicacion |
| Consumidor | Todos los modulos | `openapi.yaml` + coleccion Postman | contrato unico del API v1 |
| Consumidor | ASII-26 Dashboard base | `GET /documentos?rol=` | enlace "Ayuda" contextual del menu |

Regla de integracion: ningun modulo lee las tablas de otro; toda dependencia pasa por un
contrato API versionado (`/api/v1`) a traves del gateway, con JWT y `X-Tenant-ID`.

## Requisitos y criterios de aceptacion

- **RF-01**: exponer `POST /documentos` y `GET /documentos` sobre el Micro-HIS de la Semana 4.
- **RF-02**: exigir `titulo` al publicar y `rol` al consultar (`422 validation_failed`).
- **RF-03**: traducir `documento_sin_roles` al sobre comun `{code, message}`.
- **RF-04**: devolver solo el catalogo visible para el rol consultado.
- **RNF-01**: sobre comun de ASII-24 sin trazas ni SQL; PHP 8.2+ sin framework; el borde REST no tiene reglas.
- **AC-01**: cada ruta del contrato responde el status documentado (pruebas de contrato en verde).
- **AC-02**: rutas desconocidas responden `404 route_not_found` y metodos no soportados `405`.
- **AC-03**: `contrato-api.yaml` declara cada ruta y status que las pruebas verifican.

## Evidencia

```bash
cd docs/asii-24-centro-documentacion/semana-05
php -d zend.assertions=1 -d assert.exception=1 api/tests/contrato_api.php
```

```text
  [OK] ruta desconocida responde 404 con el sobre comun
  [OK] POST /documentos responde 201 con data.id
  [OK] POST /documentos sin roles responde 422 documento_sin_roles
  [OK] GET /documentos devuelve solo lo visible para el rol
  [OK] GET /documentos sin rol responde 422 validation_failed
  [OK] el contrato declara las rutas y status probados

Resumen: 6 pruebas, 0 fallidas.
```

Prueba manual con el servidor embebido (`MICRO_HIS_DSN=sqlite:storage.sqlite` para persistir entre peticiones):

```bash
php -S localhost:8081 -t api/public
curl -s -X POST localhost:8081/api/v1/documentos -H 'Content-Type: application/json' -d '{"titulo":"Manual de enfermeria","roles":["enfermeria"]}'
curl -s 'localhost:8081/api/v1/documentos?rol=enfermeria'
```

## Plan de trabajo (issue, rama, worktree y PR)

| Paso | Detalle |
|---|---|
| Issue | [#208](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/208) (`ASII-24`), checklist de la Semana 5 |
| Rama | `feature/asii-24-semana-05-api-rest-lis671` |
| Base | apilada sobre `feature/asii-24-04-repository-mvc-lis671` (PR #1183), porque reutiliza el Micro-HIS de la Semana 4 |
| Worktree | `git worktree add ../shi-asii-24-centro-documentacion -b feature/asii-24-semana-05-api-rest-lis671 feature/asii-24-04-repository-mvc-lis671` |
| PR | hacia `develop` con `Refs #208`, **se abre cuando #1183 este fusionado** y la rama se actualice con `origin/develop` |
