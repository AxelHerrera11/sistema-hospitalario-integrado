# Semana 17 — Seguridad y preparación de despliegue RBAC

María / Mar-03. Rama `feature/asii-02-semana-17-rbac-mar-03`, worktree `s17-rbac`, recreada sobre la entrega documental de semana 16 (`f41be9b`). **Depende de S16a, S16b y S16c**: revisar esos diffs primero; antes de un PR a develop actualizar su base tras el merge de S16. Sin force push ni auto-merge.

S16 se divide en tres entregas para respetar el límite de 400 líneas, porque la matriz corregida superaba 478 en un solo diff: **S16a** `ce4fda1` (integración funcional, 352 líneas), **S16b** `650780f` (matriz activa/pendiente y pruebas, 267 líneas sobre S16a) y **S16c** `f41be9b` (catálogo y contrato, 213 líneas sobre S16b). Cada una conserva su propia evidencia.

## Código funcional

`RequirePermission` rechaza permisos vacíos/malformados y comodines, ausencia o discrepancia de tenant y contexto incompleto. Conserva 401/403/503 y añade 400 `TENANT_REQUIRED`, 403 `TENANT_MISMATCH`, 503 `RBAC_PERMISSION_NOT_CONFIGURED`. Éxitos y errores propios llevan `Cache-Control: no-store, private`. No modifica errores de middlewares anteriores.
`ConfiguredPermissions` hace búsqueda literal de roles; no interpreta puntos como rutas de configuración. `superadmin` recibe 21 de los 22 permisos publicados **enumerados**, no por su nombre ni con `*`; el restante (`prescripciones.excepcion_alergia`) está en `pending` y se deniega a todos. `rbac.asignar` (asignar membresía) y `rbac.roles.asignar` (editar permisos de un rol) son operaciones distintas, no alias. La matriz propuesta configurada está en [`../semana-05/permisos.md`](../semana-05/permisos.md). Los permisos se resuelven por petición; cambiar la política no deja una decisión cacheada por RBAC (un cambio a config cacheada requiere reconstruir config y reiniciar workers).

## Matriz de amenazas y verificación

| Amenaza | Control | Evidencia |
|---|---|---|
| Suplantar usuario por cabecera | Principal autenticado del servidor | IntegrationTest |
| Leer permisos de otro hospital | Membresía por hospital y comparación del header validado | IntegrationTest + SecurityTest |
| Escalar con rol desconocido o `*` | Coincidencia exacta, sin permisos implícitos | SecurityTest |
| Matriz desviada o concesión pendiente activada | La prueba compara config con la matriz publicada, separa `pending` y verifica su denegación | RoleMatrixTest + IntegrationTest |
| Confundir membresía con edición de permisos de rol | Dos permisos distintos (`rbac.asignar` / `rbac.roles.asignar`), sin alias | RoleMatrixTest |
| Usar códigos PascalCase del micro-HIS en producción | Coincidencia literal; `Medico`/`AdminHospital` no reciben permisos | RoleMatrixTest |
| Otorgar permisos publicados sin acuerdo | Pendientes fuera de `roles` y prueba HTTP de no concesión | RoleMatrixTest + IntegrationTest |
| Omitir permiso del middleware | Denegación 503 antes del controlador | SecurityTest |
| Caída de resolución o auditoría | 503, nunca ejecutar operación | IntegrationTest + SecurityTest |
| Filtrar DSN/token por error | Solo code y trace_id | IntegrationTest + SecurityTest |
| Cachear una respuesta autorizada | no-store en respuestas propias | SecurityTest |
| Usar membresía revocada/expirada | Consulta CENTRAL activa y vigente | Código implementado; prueba PostgreSQL pendiente con Javier |

## Despliegue propuesto, todavía no ejecutado

1. Yamilet revisa y mergea S16 antes de S17. Confirmar con Cindy: usuario autenticado con ID CENTRAL, `tenant->getKey()` corresponde al hospital y middleware valida JWT; sustituir `DenialRecorder` por AuditLogger acordado. La fachada JWT tiene un alias corregido en S16; los modelos faltantes de auth no los resuelve RBAC.
2. Javier valida la lectura de `hospital_memberships`, `central_users`, `hospitals` y sus vigencias en PostgreSQL. No ejecutar migraciones adicionales de RBAC. La matriz activa está en `config/rbac.php`; `pending` documenta los pares aún no aprobados y el motor los deniega. Falta que el equipo apruebe esos pares (`permisos.md`) y que Cindy/Javier confirmen que `hospital_memberships.role_code` usa exactamente `superadmin`, `admin_hospital`, `medico`, `enfermeria` y `recepcion` (la búsqueda distingue mayúsculas). El endpoint de edición de un rol usará `rbac.roles.asignar`; no reutilizar `rbac.asignar`.
3. En staging, instalar los locks: `composer install --no-interaction --prefer-dist` y `npm ci`. Ejecutar `php artisan test tests/Feature/Rbac` y los cuatro scripts `micro-his/rbac/tests/{run,inmemory,authorization,authorization_errors}.php`; después `npm run build` y `php artisan route:list --path=api/v1/permissions -v`.
4. Configurar secretos por entorno, `APP_DEBUG=false`, HTTPS, logging con acceso restringido y BD CENTRAL correcta. Ejecutar `php artisan config:cache` y `php artisan route:cache`; reiniciar workers persistentes si existen.
5. Smoke real: sin JWT no obtiene catálogo; lector del hospital correcto 200; sin permiso 403; hospital distinto denegado; resolver indisponible no ejecuta acción. Comprobar traza en auditoría y `/rbac` en desktop/móvil. No usar fixtures de tests como login real.
6. Si falla el smoke, restaurar el release anterior y su configuración de permisos, reconstruir config/rutas y reiniciar workers. No revertir migraciones ajenas. Registrar commit, hora, responsable, comandos y resultados antes de declarar desplegado.

## Bitácora y estado real

2026-10-19: implementación local y validación aislada; resultados exactos en `evidencia.txt`. La matriz de 22 permisos × 5 roles está publicada; 21 están activos para `superadmin` y los pares `pending` se deniegan. Auth y tenant simulados **solo en tests**, sin E2E JWT ni conexión PostgreSQL. Las suites vanilla ya usaban SQLite de prueba; no se incorporó SQLite a Laravel ni otra migración. S16 se dividió en S16a `ce4fda1`, S16b `650780f` y S16c `f41be9b` para respetar el límite de 400 líneas por entrega.
No se afirma staging, despliegue final, QA del sistema completo, aprobación de permisos ni revisión de Yamilet. PR/issue y evidencia visual pendientes; no se publicaron cambios ni se mergearon PR de terceros. Las dos guías enlazadas desde README no estaban disponibles; se consultaron README, plantillas, plan Word y aportes de Axel.
IA: Codex generó código, pruebas y documentación; quedan revisión humana, confirmación de contratos y defensa individual.
