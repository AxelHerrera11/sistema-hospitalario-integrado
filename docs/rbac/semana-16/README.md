# Semana 16 — Integración funcional de RBAC

María / Mar-03. Worktree `s16-rbac`, rama `feature/asii-02-semana-16-rbac-mar-03`, creada desde `origin/develop` (`4af4d8f`) y actualizada por fast-forward a `757bcae` tras completar fetch.

## Alcance y diseño

RF: proteger rutas por permiso y hospital; consultar catálogo desde Vue. RNF: denegar por defecto y no confiar en identidades del cliente.
Flujo: auth/tenant → `rbac:modulo.accion` → `Authorize` existente → RoleResolver + PermissionProvider + DenialRecorder → controlador.
El provider registra el alias y las interfaces. Los tests sustituyen las dependencias por InMemory y auth/tenant por fixtures, exclusivamente en tests.

`CentralRoles` consulta el esquema existente CENTRAL: usuario, hospital y membresía activos, con vigencia. No hay migraciones nuevas ni escrituras sobre tablas de Javier.
`ConfiguredPermissions` lee `config/rbac.php`; `roles` vacío deniega todo hasta acordar asignaciones. Ejemplo de configuración **propuesta**, no política aprobada: `'Reader' => ['rbac.permisos.ver']`.
`LogDenials` registra denegaciones estructuradas en el logger de Laravel; reemplazar el binding de `DenialRecorder` por AuditLogger cuando Cindy publique su interfaz. No equivale a auditoría clínica definitiva.

## Contrato para el equipo

`GET /api/v1/permissions`, JWT y tenant validados, permiso `rbac.permisos.ver`; 200 `{permissions: string[]}`. La lista incluye los nueve permisos exactos de Axel y el catálogo previo de RBAC, prescripciones y muestras. Catálogo de integración provisional, no concede permisos automáticamente.
Middleware: 401 `AUTH_REQUIRED`, 403 `RBAC_DENIED`, 503 `RBAC_CONTEXT_UNAVAILABLE` o `RBAC_UNAVAILABLE`; cuerpo `{code, trace_id}`. Los errores anteriores de auth/tenant conservan su contrato propio.
Uso por otros módulos: `->middleware(['tenant', 'jwt.auth', 'rbac:alergias.ver'])`. El servidor resuelve usuario mediante `getAuthIdentifier()` y hospital mediante el `getKey()` del atributo `tenant` validado. Confirmar estas dos correspondencias con Cindy antes del adaptador real.
UI: `/rbac`; carga, vacío, error, 401/403/503 y reintento manual. Usa el cliente Axios existente. No contiene pacientes ni datos clínicos.

## Criterios de aceptación y límites

- Un lector de hospital A recibe 200; el mismo usuario en B recibe 403.
- Cabeceras `X-User-ID` y `X-User-Ref` no cambian la identidad autenticada.
- Resolver caído devuelve 503 sin ejecutar el controlador ni exponer excepciones.
- La ruta mantiene auth, tenant y RBAC; no existe autenticación de prueba en producción.

Pendiente: auth real de Cindy y su contrato tenant/usuario, permisos por rol aprobados, validación PostgreSQL con Javier, auditoría definitiva y staging. El adaptador SQL está implementado; esta entrega no afirma conexión real ni despliegue. La asignación administrativa sigue en el micro-HIS previo; no se expone escritura CENTRAL sin acuerdo del esquema.
Corrección compartida indispensable: `JwtAuth.php` importaba `JWTAuth` con el mismo nombre de clase (PHP ignora mayúsculas); se cambió solo el alias de la fachada para evitar el fatal de clase duplicada. No se implementó auth de Cindy.
Las guías `docs/weekly-plan.md` y `docs/worktree-guide.md` enlazadas por README no existen en las referencias consultadas. Se siguen las reglas explícitas del README y del plan Word: feature desde develop, un módulo, sin force push, revisión por Yamilet y sin auto-merge.

Validación y salida real: ver `evidencia.txt`. Uso de IA: Codex para implementación, pruebas y documentación; requiere revisión y defensa humana.
