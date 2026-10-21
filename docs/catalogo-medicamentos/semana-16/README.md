# ASII-14 - Catálogo de medicamentos - Semana 16

- **Tema:** integración, validación y primera entrega funcional
- **Issue:** [#165](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/165) · Código: PR #602

| Requisito | Estado | Evidencia |
|---|---|---|
| Integrar con los componentes reales del HIS | **Hecho** | API v1 en Laravel detrás de `tenant`, `jwt.auth` y `RequirePermission` reales, comprobados por HTTP con Tenants integrado ([integracion.md](./integracion.md)) |
| Límites reales de autorización y hospital | **Hecho y probado** | Matriz real de `config/rbac.php`; 401, 400, 403 por tenant y 403 por permiso |
| Alta, búsqueda, consulta y vigencia con rechazos y fallos | **Probado** | 6 pruebas Feature, OK sobre `develop`; escritura de `admin_hospital` con la matriz real desde #627; los demás roles, 403 auditado |
| Configuración reproducible sin secretos | **Hecho** | [integracion.md](./integracion.md#configuración-reproducible-sin-secretos) |
| Migraciones y semillas de prueba | **Bloqueado** | Tabla `medication` del slice H5 pendiente; no se inventa el esquema |
| Evidencia en staging u on-premise | **Bloqueado** | No hay acceso a ningún entorno; solo ejecución local |
| Errores corregidos | **Hecho** | `medication_id`, 500 → 503 por conexión, AV3 y el contrato de errores de acceso |

**La Semana 16 no se declara completa:** faltan el despliegue y la persistencia real, que dependen de terceros (ver [bloqueos](./integracion.md#bloqueos-y-limitaciones-no-se-declaran-completados)).

[Declaración de uso de IA](./DECLARACION_IA.md)
