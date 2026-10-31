# Semana 16 (parte 2) — Apertura del expediente en la API v1

## Objetivo

Hasta ahora la API v1 solo publicaba la consulta del expediente longitudinal. La apertura (RF-EXP-01) existía en el
micro-HIS, pero no tenía ruta en Laravel porque su permiso no estaba en el catálogo RBAC. Esta parte activa el
permiso y publica la operación, con lo que el flujo «apertura y consulta autorizada» queda completo en la aplicación.

## Decisión RBAC

`expediente.abrir` se agrega al catálogo y a la matriz activa de `config/rbac.php` para **medico** y **superadmin**
(decisión de la responsable del módulo, 30/10/2026). Enfermería conserva solo la consulta; recepción y
administración no reciben la concesión. La sección «Activación de la apertura del expediente» de
`docs/rbac/catalogo-modulos.md` documenta la decisión; `RoleMatrixTest` fija la matriz.

## Contrato

| Método | Ruta | Permiso | Respuesta |
|---|---|---|---|
| GET | `/api/v1/patients/{patientCode}/medical-record` | `expediente.ver` | 200 expediente longitudinal (sin cambios) |
| POST | `/api/v1/patients/{patientCode}/medical-record` | `expediente.abrir` | 201 `{created: true, record_number, …}` la primera vez; 200 `{created: false, …}` si ya existía |

Middlewares en el mismo orden que la consulta: `api`, `tenant`, `jwt.auth`, `EnsureHospitalDatabaseTenant` y
`rbac:expediente.abrir`. Errores: 403 `RBAC_DENIED`, 403 `ACTOR_SIN_REFERENCIA_LOCAL`, 404 `PACIENTE_NO_ENCONTRADO`,
422 `CODIGO_PACIENTE_INVALIDO`, 503 `AUDITORIA_NO_DISPONIBLE` / `HOSPITAL_NO_DISPONIBLE`. Cada apertura deja
`expediente_medico.abierto` o `expediente_medico.apertura_repetida` en la bitácora de Gobernanza.

## Cambios en pruebas

- Laravel: la prueba «la apertura no se publica» se reemplaza por dos: médico abre (201), repetir devuelve el mismo
  número (200) y queda auditado; enfermería, recepción y administración reciben 403 sin escribir en la bitácora.
- Micro-HIS: N-08 y S-01 usaban la matriz activa suponiendo que nadie podía abrir; ahora esperan 201/200 para
  medicina y 403 para el resto.

## Validación (2026-10-30, copia limpia de `origin/develop` + este cambio)

```bash
php micro-his/expediente-medico/tests/run.php
# Resumen: 98 OK, 0 FALLO
php vendor/bin/phpunit tests/Feature/ExpedienteMedico tests/Feature/Rbac/RoleMatrixTest.php
# OK (13 tests, 292 assertions)
php vendor/bin/phpunit --testsuite Unit,Feature
# 365 pruebas; los mismos 44 errores y 3 fallos que develop sin el cambio (Recepción, Salas y camas y Reportes
# requieren PostgreSQL; PublishedContractsTest falla por docs/notas-soap). Ningún fallo nuevo.
```
