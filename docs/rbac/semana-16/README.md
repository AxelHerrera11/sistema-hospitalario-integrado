# Semana 16 — Integración funcional de RBAC

**Entregas para respetar el límite de 400 líneas:** S16a `ce4fda1` (integración funcional), S16b `650780f` (matriz activa/pendiente y pruebas) y S16c `c804c89` (catálogo, contrato y esta documentación). S16c depende de S16b, que depende de S16a.

María / Mar-03. Worktree `s16-rbac`, rama `feature/asii-02-semana-16-rbac-mar-03`, creada desde `origin/develop` (`4af4d8f`) y actualizada por fast-forward a `757bcae` tras completar fetch.

## Alcance y diseño

RF: proteger rutas por permiso y hospital; consultar catálogo desde Vue. RNF: denegar por defecto y no confiar en identidades del cliente.
Flujo: auth/tenant → `rbac:modulo.accion` → `Authorize` existente → RoleResolver + PermissionProvider + DenialRecorder → controlador.
El provider registra el alias y las interfaces. Los tests sustituyen las dependencias por InMemory y auth/tenant por fixtures, exclusivamente en tests.

`CentralRoles` consulta el esquema existente CENTRAL: usuario, hospital y membresía activos, con vigencia. No hay migraciones nuevas ni escrituras sobre tablas de Javier.
`ConfiguredPermissions` lee `config/rbac.php`; la matriz rol × permiso propuesta ya está configurada con los cinco roles de Semana 5 y los 26 permisos del catálogo (22 conciliados y 4 administrativos para Cindy). No hay comodines, herencia ni concesión automática a `superadmin`: cada permiso se enumera. Las concesiones activas viven en `roles`; las propuestas no aprobadas viven en `pending` y el middleware las deniega. La matriz y sus pendientes están en [`../semana-05/permisos.md`](../semana-05/permisos.md).
`LogDenials` registra denegaciones estructuradas en el logger de Laravel; el adaptador propio `AuditDenials` consume `AuditLogRepositoryInterface::logRaw()` cuando se integre el provider de Cindy. No equivale a auditoría clínica definitiva.

## Contrato para el equipo

`GET /api/v1/permissions`, JWT y tenant validados, permiso `rbac.permisos.ver`; 200 `{permissions: string[]}`. La lista tiene 26 permisos: 13 conciliados de Semana 5, los 9 exactos de Axel y 4 administrativos para Cindy. El catálogo es informativo; consultarlo no concede permisos.
`rbac.asignar` (membresías, motor `AssignRole`) y `rbac.roles.asignar` (edición de permisos de un rol, endpoint propuesto) son operaciones distintas, no alias. El contrato HTTP usa el segundo; el motor conserva el primero.
Middleware: 401 `AUTH_REQUIRED`, 403 `RBAC_DENIED`, 503 `RBAC_CONTEXT_UNAVAILABLE` o `RBAC_UNAVAILABLE`; cuerpo `{code, trace_id}`. Los errores anteriores de auth/tenant conservan su contrato propio.
Uso por otros módulos: `->middleware(['tenant', 'jwt.auth', 'rbac:alergias.ver'])`. El servidor resuelve usuario mediante `getAuthIdentifier()` y hospital mediante el `getKey()` del atributo `tenant` validado. Confirmar estas dos correspondencias con Cindy antes del adaptador real.
UI: `/rbac`; carga, vacío, error, 401/403/503 y reintento manual. Usa el cliente Axios existente. No contiene pacientes ni datos clínicos.

## Criterios de aceptación y límites

- Un lector de hospital A recibe 200; el mismo usuario en B recibe 403.
- Cabeceras `X-User-ID` y `X-User-Ref` no cambian la identidad autenticada.
- Resolver caído devuelve 503 sin ejecutar el controlador ni exponer excepciones.
- La ruta mantiene auth, tenant y RBAC; no existe autenticación de prueba en producción.

Pendiente: auth real de Cindy y su contrato tenant/usuario, confirmación de que `role_code` usa exactamente `superadmin`/`admin_hospital`/`medico`/`enfermeria`/`recepcion`, aprobación de los pares en `pending` (incluida `prescripciones.excepcion_alergia`, que hoy se deniega a todos), acuerdo de los permisos publicados que aún no se otorgan, validación PostgreSQL con Javier, auditoría definitiva y staging. Los códigos PascalCase del micro-HIS son fixtures heredados, no el contrato de integración. El adaptador SQL está implementado; esta entrega no afirma conexión real ni despliegue. La asignación administrativa sigue en el micro-HIS previo; no se expone escritura CENTRAL sin acuerdo del esquema.
Corrección compartida indispensable: `JwtAuth.php` importaba `JWTAuth` con el mismo nombre de clase (PHP ignora mayúsculas); se cambió solo el alias de la fachada para evitar el fatal de clase duplicada. No se implementó auth de Cindy.
Las guías `docs/weekly-plan.md` y `docs/worktree-guide.md` enlazadas por README no existen en las referencias consultadas. Se siguen las reglas explícitas del README y del plan Word: feature desde develop, un módulo, sin force push, revisión por Yamilet y sin auto-merge.

Validación y salida real: ver `evidencia.txt`. Uso de IA: Codex para implementación, pruebas y documentación; requiere revisión y defensa humana.

Actualización 2026-10-20: las ramas S16a/S16b/S16c incorporan adaptador, permisos y contrato actualizado sin código de Cindy. Ver [cierre de integración](../cierre-integracion-2026-10-20.md); los SHA de arriba son referencias históricas anteriores a esta actualización.

## Corrección de integración (2026-10-21): contrato de auditoría de las denegaciones

Defecto reproducible: `App\Rbac\AuditDenials` escribía el evento `rbac.access_denied` sin resultado, criticidad ni recurso. La fila quedaba con `audit_log.result` en NULL, de modo que el lector de gobernanza la reconstruye como `AuditOutcome::Unknown` y el filtro por resultado `denied` (`result = :outcome`) no la devuelve; tampoco existían `criticality` ni `resource`, que el contrato exige (`docs/gobernanza-auditoria/semana-05/contrato-api.md`: `outcome: denied`, `criticality: security`, `resource: {type: permission, id: <permiso>}`).

Corrección: el mismo escritor (`AuditDenials` sobre `AuditLogRepositoryInterface::logRaw()`, cableado en `bootstrap/providers.php`) ahora envía `result: denied`, `criticality: security` y `resource: {type: permission, id: <permiso>}`. No se crea un segundo escritor ni se duplica la cadena de auditoría; `permission`, `reason` y `trace_id` se conservan igual. `LogDenials` sigue siendo solo el respaldo al log de Laravel y no escribe en `audit_log`.

Pruebas: `tests/Feature/Rbac/AuditDenialsContractTest.php` fija el contrato del escritor y `tests/Database/Central/RbacDenialAuditTest.php` valida en PostgreSQL que la fila escrita se lee con resultado `denied`, criticidad `security` y recurso `permission`. Sin la corrección las tres aserciones principales fallan (resultado NULL); la reproducción queda en `evidencia.txt`.

Límites: no se modifica el módulo de gobernanza ni su consulta, que sigue sin registrar en `bootstrap/providers.php`; no se amplían los resultados admitidos en `audit_log` (la restricción vigente es la del PR #679, abierto y ajeno a este módulo). `auth.logout` escribe `already_revoked`, valor que no pertenece a `AuditOutcome` y que queda anotado para el módulo de gobernanza. Pendiente: evidencia en staging/on-premise y confirmación del contrato por su responsable.

Actualización 2026-10-21: rama `feature/asii-02-semana-16-rbac-cobertura-modulos-mar-03` desde `origin/develop` (`3afc782`). Corrige el contrato de auditoría de denegaciones y añade sus pruebas; no cambia rutas, permisos ni la matriz de roles.
