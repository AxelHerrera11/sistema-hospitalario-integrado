# Activación de gobernanza para el cierre RBAC

Rama: `feature/asii-02-semana-17-rbac-integracion-gobernanza-mar-03`, desde develop `a3925260`.

Se registra el provider ya existente después de RBAC y Tenants. Queda disponible `GET /api/v1/audit-events`, con middleware tenant, JWT real y `rbac:auditoria.ver`. No se añaden permisos, escritores, migraciones ni valores a `audit_log.result`. La política vigente concede `auditoria.ver` a superadmin; no se amplía a admin_hospital.

`GovernanceHttpTest` comprueba con el kernel HTTP y CENTRAL SQLite aislada:

- Lectura de denegaciones por usuario autorizado, recurso y criticidad preservados, separación por hospital y auditoría de la consulta.
- Rechazo del médico antes de validar filtros, con una sola denegación persistida y su trace_id.
- Rechazo de hospital ajeno tanto en cabecera como en query.
- Revocación de rol y logout con JWT real.
- Validación de filtros y respuesta 503 sin detalles SQL cuando falla el almacenamiento.

Ejecución: `php vendor/bin/phpunit tests/Feature/Rbac/GovernanceHttpTest.php`: **5 pruebas, 33 aserciones, OK**.

Además se probó en la copia conjunta (develop a3925260 + #848 + #858 y sus cambios de revalidación): login lector 200, catálogo 403, auditoría 403, login superadmin 200, catálogo 200, consulta HTTP de la denegación real por resultado y correlación 200, hospital ajeno 403, logout 200 y token anterior 401. Se usaron usuarios ficticios en localhost y SQLite persistente, sin sustituir JWT ni RBAC por dobles.

## Integración y entrega

Integrar también #848 para que las denegaciones generadas por RBAC lleven `result=denied` y sean recuperables con ese filtro. La activación del endpoint no corrige por sí sola el escritor. Las pruebas de esta rama no presuponen que #848 ya esté fusionado.

CENTRAL debe tener aplicadas sus migraciones de `audit_log` y `audit_chain_state`, con estado inicial de cadena. El acuerdo de #679 ya acepta NULL/success/failure/denied/already_revoked/unknown; este PR no aplica su migración ni realiza merge. Verificar por separado la revisión y aplicación del esquema en el entorno de entrega.

La prueba local cierra el pendiente de cableado y de consulta HTTP. No acredita despliegue en staging ni autoriza a certificar las 17 semanas o módulos clínicos de terceros. Base del PR: develop; borrador; sin merge.
