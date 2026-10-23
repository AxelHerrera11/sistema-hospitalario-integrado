# Semana 13 — Calidad del software y revisión técnica formal

**Entregable:** plan de revisión, checklist de 12–15 ítems, roles de autor/moderador/revisores, criterios de entrada/salida y formato de hallazgos.

## Plan de revisión

| Campo | Valor |
|---|---|
| Objeto | `micro-his/qa-calidad-versiones/**`, `tests/E2E/**`, `.github/workflows/qa-ci-e2e.yml`, `docs/CI-E2E/**` |
| Foco | Reglas de dominio del gate, contrato API, permisos/tenant, integridad de ejecuciones y evidencia, dependencias estrictamente necesarias |
| Duración | 1 sesión de 60 min de preparación individual + 45 min de reunión |
| Material previo | Este plan, [contrato-api.md](../semana-05/contrato-api.md), [rf-rnf-criterios.md](../semana-02/rf-rnf-criterios.md), salida de `tests/run.php` y de `pipeline-local.sh` |
| Fecha propuesta | Jueves 22-oct-2026, tarde (ventana de QA del plan de integración) |

## Roles

| Rol | Persona propuesta | Responsabilidad |
|---|---|---|
| Autora | Cindy Ruano | Presenta el material, responde, corrige |
| Moderadora | Yamilet (integración) | Conduce, controla tiempo, decide criterios de salida |
| Revisora RBAC | María | Permisos `qa.*` propuestos, contrato de rutas, formato 403 |
| Revisor BD | Javier | Propuesta de tablas (S4), migraciones del job e2e |
| Revisora QA manual | Madelin | Reparto manual/automatizado, uso de la evidencia en regresión |
| Registrador/a | Una de las revisoras | Llena la tabla de hallazgos |

> Personas **propuestas**: la asignación la confirma el grupo.

## Criterios de entrada

1. `php micro-his/qa-calidad-versiones/tests/run.php` → «TODAS LAS SUITES APROBADAS».
2. `node --test micro-his/qa-calidad-versiones/tests/view-model.test.mjs` y `tests/E2E/qa-calidad.e2e.mjs` en verde.
3. `php vendor/bin/pint --test micro-his/qa-calidad-versiones tests/E2E` → `passed`.
4. Ningún archivo del módulo > 400 líneas (AR08).
5. Documentos marcados [Implementado] / [Propuesto] sin ambigüedad.

## Checklist (14 ítems)

| # | Ítem | Cómo se verifica |
|---|---|---|
| 1 | Un reporte ausente, vacío o con todo omitido nunca aprueba | W04–W06, CI18 |
| 2 | Las omitidas en suites obligatorias tienen regla propia y no se esconden en la tasa | W12, W13 |
| 3 | El veredicto es reproducible: la decisión guarda la versión de la política | A03, `policy_version` en la respuesta |
| 4 | Resultados y decisiones son de solo inserción | U19, P04, P05 |
| 5 | Cada evidencia lleva SHA-256 y tamaño reales | U08–U12, CI07, H-03 |
| 6 | Toda operación exige permiso y se falla cerrado sin RBAC | BN04, BN22, A05 |
| 7 | El tenant lo fija el servidor; otro tenant → 404 sin fuga | BN03, BN11, P03 |
| 8 | Errores con `{code, trace_id}` y sin detalle técnico al cliente | BN23, ErrorMapper |
| 9 | Ningún permiso inventado se usa en rutas reales; los `qa.*` están marcados como propuesta | `PublishedContractsTest`, contrato-api.md |
| 10 | E2E solo en ambientes aislados; el sembrado rechaza bases no `*_e2e` | U06, guardas de `seed-e2e.php` |
| 11 | El workflow no expone secretos: JWT y contraseña generados y enmascarados por ejecución; `permissions: contents: read` | revisión del YAML |
| 12 | Dependencias estrictamente necesarias: sin paquetes nuevos en `composer.json`/`package.json` | `git diff` de ambos archivos (vacío) |
| 13 | Capas: dominio sin dependencias, controlador sin SQL ni reglas | AR01–AR06 |
| 14 | Docs, diagramas y código dicen lo mismo (nombres de clases, códigos de error, rutas) | lectura cruzada; SVG regenerados de sus `.mmd` |

## Criterios de salida

- 0 hallazgos **mayores** abiertos; los menores con dueño y fecha.
- Los ítems 1–7 y 9–11 marcados «cumple».
- La moderadora firma el acta (tabla de abajo) con fecha.

## Formato de hallazgos

| ID | Ítem | Severidad (mayor/menor/observación) | Ubicación (archivo:línea) | Descripción | Evidencia | Responsable | Acción | Estado |
|---|---|---|---|---|---|---|---|---|

## Hallazgos de la auto-revisión previa (21-oct-2026)

| ID | Ítem | Severidad | Ubicación | Descripción | Responsable | Estado |
|---|---|---|---|---|---|---|
| RT-01 | 12 | observación | `composer.json` | No hay PHPStan/Larastan: la etapa de análisis estático queda como aviso | Grupo | Propuesto (S14) |
| RT-02 | 9 | menor | `config/rbac.php` | No existen permisos `qa.*`; el montaje en Laravel espera a María | María | Abierto |
| RT-03 | 8 | menor | `app/Http/Middleware/TenantMiddleware.php`, `JwtAuth.php` | Formato `{error, message}` distinto al `{code, trace_id}` de RBAC | Lis (acuerdo) / Cindy (Tenants) | Abierto, no se cambió código |
| RT-04 | 1 | mayor para la política | `micro-his/signos-vitales`, `ingreso-resultados-lab`, `validacion-resultados-bioquimico`, `nativephp-extension-prototype` | Sin `tests/run.php`: con la política propuesta el gate rechaza todo PR | Josué, Gerson | Abierto ([D-01](../pendientes-y-bloqueos.md#d-01)) |
| RT-05 | 11 | menor | `qa-ci-e2e.yml`, job e2e | El E2E de pila completa no se ha ejecutado nunca en GitHub Actions | Cindy | Abierto: verificar en el primer PR |
