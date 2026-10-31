# Semana 16 — Integración, validación y primera entrega funcional

**Tarea:** integrar el módulo en la rama común, validarlo en un ambiente on-premise con datos ficticios, corregir
los errores encontrados y dejar una primera entrega funcional con evidencia.

## Qué quedó integrado en `develop`

Los 26 PR del módulo (#1156–#1181, semanas 1–15) están fusionados en `develop`. Con ellos, la rama común tiene:

| Pieza | Estado en `develop` | Cómo se comprobó |
|---|---|---|
| Micro-HIS `micro-his/expediente-medico` (consulta y apertura) | Integrado | Suite propia y ejecutor del CI ([03](evidencias/03-suites.txt)) |
| Adaptador Laravel `GET /api/v1/patients/{patientCode}/medical-record` | Integrado y registrado en `bootstrap/providers.php` | `route:list` y 5 pruebas Feature ([02](evidencias/02-integracion-laravel.txt)) |
| Prototipo de interfaz (`public/ui`) | Integrado | Servido por el front controller ([01](evidencias/01-smoke-on-premise.txt)) |
| Bitácora de Gobernanza (eventos `expediente_medico.*`) | Integrada | Filas reales escritas por el servidor ([01](evidencias/01-smoke-on-premise.txt)) |

## Ambiente on-premise usado

El grupo no tiene un servidor de staging y GitHub Actions no ejecuta jobs (la organización tiene la facturación
bloqueada desde el 24-oct-2026). Por eso la validación se hizo **on-premise, en el equipo de desarrollo**:

| Elemento | Valor |
|---|---|
| Sistema | Windows 11, PHP 8.2.12, Node 22.13.1 |
| Código | Copia limpia de `origin/develop` (`6e841849`) + los cambios de esta entrega |
| Servidor | `php -S 127.0.0.1:8096` con el front controller del módulo |
| Datos | SQLite local creada desde `database/schema.sql` y `database/demo.sql` (personas ficticias) |
| Bitácora | `PdoAuditLog` de Gobernanza sobre SQLite (`audit_log` append-only) |

## Validación ejecutada (2026-10-29)

| # | Qué se validó | Resultado | Evidencia |
|---|---|---|---|
| V-01 | Consulta de la médica tratante (P-0001) | 200, `Cache-Control: no-store`, sin `contact` ni `global_id` | [01](evidencias/01-smoke-on-premise.txt) |
| V-02 | Médico con asignación terminada y enfermería | 403 `SIN_VINCULO_ASISTENCIAL` | [01](evidencias/01-smoke-on-premise.txt) |
| V-03 | Sin identidad / hospital sin base | 401 `IDENTIDAD_REQUERIDA` / 503 `HOSPITAL_NO_DISPONIBLE` | [01](evidencias/01-smoke-on-premise.txt) |
| V-04 | Apertura de P-0002 y repetición | 201 y luego 200 con el mismo id (idempotente) | [01](evidencias/01-smoke-on-premise.txt) |
| V-05 | Apertura de paciente fallecido / método no permitido | 409 `PACIENTE_NO_ACTIVO` / 405 | [01](evidencias/01-smoke-on-premise.txt) |
| V-06 | Bitácora: 4 eventos con hash encadenado; `UPDATE` rechazado | Append-only confirmado | [01](evidencias/01-smoke-on-premise.txt) |
| V-07 | Ruta Laravel publicada y pruebas Feature | 1 ruta, 5 pruebas OK | [02](evidencias/02-integracion-laravel.txt) |
| V-08 | Suite del micro-HIS, interfaz y ejecutor del CI | 90 OK, 11/11, 25 módulos OK | [03](evidencias/03-suites.txt) |

La apertura (V-04) usa la concesión **PROPUESTA** que solo activa el prototipo: en `config/rbac.php` todavía no
existe el permiso de apertura, por eso Laravel solo publica la consulta (prueba Feature «Opening is not published
until rbac catalogues its permission»).

## Errores corregidos en esta entrega

| Id | Encontrado en | Problema | Causa | Corrección | Verificación |
|---|---|---|---|---|---|
| DEF-EXP-10 | Verificación manual (Semana 15, estaba abierto) | Tras cambiar `app.js` el navegador seguía usando la versión anterior | `php -S` sirve los estáticos sin `ETag` ni `Last-Modified` y **descarta** las cabeceras del router cuando este devuelve `false` (comprobado con `curl -I`) | Nueva clase `Presentation/StaticAsset`: el front controller sirve `public/ui` con `Cache-Control: no-cache`, solo archivos `.html`, `.js` y `.css` dentro de la carpeta | Prueba N-14 y `curl -I` en [01](evidencias/01-smoke-on-premise.txt) |

Al servir la interfaz desde PHP apareció un riesgo nuevo: leer rutas con `..`. `StaticAsset` resuelve la ruta real
y rechaza todo lo que quede fuera de `public/ui` (N-14 y `curl --path-as-is` → 404).

## Fallos vistos que no son del módulo

`PublishedContractsTest` falla en `develop` por un permiso de órdenes de laboratorio sin catalogar (acción
«actualizar», nombre exacto en [02](evidencias/02-integracion-laravel.txt)) citado en
`docs/ordenes-laboratorio/semana-01/README.md` (otro módulo). No se modificó; falla igual sin esta entrega.

## Archivos cambiados

`micro-his/expediente-medico/src/Presentation/StaticAsset.php` (nuevo), `micro-his/expediente-medico/public/index.php`,
`micro-his/expediente-medico/tests/caja-negra.php` (N-14) y esta carpeta.

## Pendiente

- Validar en PostgreSQL y en un servidor del grupo cuando exista (R-04).
- Permiso de apertura y criterio de enfermería (RBAC), ver [`../pendientes-integracion.md`](../pendientes-integracion.md).
