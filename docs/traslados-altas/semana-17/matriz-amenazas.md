# Matriz de amenazas - Traslados y altas (Semana 17)

Alcance: `POST /api/v1/traslados`, `POST /api/v1/altas`, la consulta de internación y la SPA. El servidor es la autoridad; ocultar acciones en Vue no sustituye JWT, tenant, RBAC ni las reglas transaccionales.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| T01 | Operar sin sesión o con token inválido | `jwt.auth` antes de RBAC y del controlador | `salida-recorrido-api.txt`: sin token 401 | Mitigada |
| T02 | Leer o escribir en otro hospital | `tenant` contrasta JWT y `X-Tenant-ID` | Recorrido real: token A/cabecera B 403 | Mitigada |
| T03 | Usar un permiso no aprobado | Denegación por defecto de `rbac.pending` | API y PostgreSQL: 403 y cero escrituras | Mitigada |
| T04 | Un rol autorizado para traslado intenta dar alta | Permisos separados por ruta | Recorrido real: enfermería recibe 403 en alta | Mitigada |
| T05 | Suplantar actor u hospital desde JSON | Actor desde JWT -> `local_user_refs`; tenant desde middleware; campos extra ignorados | `test_body_cannot_spoof_actor_or_tenant_and_wrong_role_cannot_write` | Mitigada |
| T06 | Reutilizar una estancia vista antes | `estancia_esperada_id` debe seguir vigente bajo bloqueo | Repetición del traslado 409 `ESTANCIA_NO_VIGENTE` | Mitigada |
| T07 | Ocupar dos veces una cama por carrera | Bloqueos y restricción de estancia abierta | Semana 15, dos procesos: 20/20 | Mitigada |
| T08 | Dejar traslado o alta parcialmente escritos | Una transacción; rollback ante fallo confirmado | `run.php`: fallo tras primera escritura revierte | Mitigada |
| T09 | Reintentar un COMMIT de resultado incierto | Respuesta incierta exige consultar antes de reintentar | `run.php` y mensajes de la SPA | Mitigada |
| T10 | Cambiar disponibilidad operativa al liberar ocupación | Las operaciones cierran `bed_stays`, no actualizan `beds.status` | `run.php`: RF-TA-10; recorrido conserva estados | Mitigada |
| T11 | Filtrar SQL, credenciales o traza de excepción | Excepción PDO -> código estable con `APP_DEBUG=false` | `test_database_error_hides_details_and_replaces_untrusted_trace` | Mitigada |
| T12 | Inyectar una traza no confiable | `X-Request-Id` acepta solo patrón y longitud limitados | La prueba T11 exige una traza nueva saneada | Mitigada |
| T13 | Cachear datos de internación entre usuarios | `Cache-Control: no-store, private` | Pruebas API en éxito y 503 | Mitigada |
| T14 | Exponer datos personales innecesarios | Respuesta operacional sin nombre, correo ni historia clínica | Contrato y respuestas del recorrido | Mitigada |
| T15 | Mezclar datos pese a un tenant válido | Seleccionar conexión HOSPITAL por tenant | Una sola conexión `hospital` integrada | Bloqueada: Plataforma/Tenants |
| T16 | Usuario válido sin referencia clínica local | Rechazo `ACTOR_SIN_REFERENCIA_LOCAL` | Pruebas Feature y PostgreSQL | Parcial: falta sincronización ASII-01 |
| T17 | Agotar las rutas con solicitudes repetidas | Rate limiting común de `/api/v1` | No existe control específico verificable | Pendiente: API Gateway |

## Riesgo residual y acciones

- **Plataforma/Tenants:** resolver `hospitals.clinical_endpoint` y escoger una conexión HOSPITAL aislada antes de producción.
- **ASII-01/identidad:** sincronizar membresías CENTRAL a `local_user_refs`; no sembrarlas manualmente.
- **ASII-02 (`Luis890D`):** aprobar o rechazar los tres permisos; mientras sigan en `pending`, el sistema integrado debe continuar en 403.
- **API Gateway:** definir y probar el límite de tasa común. ASII-08 no declara este control como implementado.
