# Semana 17 — Seguridad y despliegue final del escenario on-premise

Issue #6. Rama feature/asii-18-semana-17-recepcion-muestras-mar-03 desde origin/develop; destino develop. Requiere integrar semanas 5–16 en orden; este PR añade seguridad, no duplica su implementación. No se hizo merge ni force push.

## Matriz de amenazas

| Amenaza | Control implementado | Verificación |
|---|---|---|
| Suplantación mediante headers | JWT y membresía real; X-User-ID/X-User-Ref no autentican | test_spoofed_user_headers_do_not_authenticate |
| Acceso a otro hospital | JWT ligado a tenant y hospital clínico configurado | test_token_for_one_hospital_cannot_read_another |
| Escalada de privilegios | Permisos independientes de recibir/ver/etiquetar; actor local activo | API/contrato previos y prueba HTTP de lector sin escritura |
| Cachear información clínica | Cache-Control no-store/private y Pragma no-cache, también en errores | test_phi_responses_and_denials_cannot_be_cached |
| Abuso de solicitudes | Límite por IP con almacenamiento persistente y Retry-After | test_request_limit_and_body_limit_are_enforced |
| Cuerpos excesivos | Límite de 64 KiB antes de la escritura clínica | test_body_limit_precedes_clinical_write |
| Filtración por errores | Respuesta 503 genérica aun con debug activado | test_internal_failures_are_sanitized_even_with_debug_enabled |
| Filtración por logs | Correlación, actor, hospital, ruta patrón y estado; sin cuerpo/query/token/código | test_observability_never_logs_request_data |
| Duplicidad o alteración de custodia | Replay, transacción muestra/outbox y trigger inmutable previos | ReceptionApiTest, ReceptionIntegrityTest y ReceptionFlowTest |
| Entrega insegura | Gate bloquea debug, HTTP público, entorno incorrecto, cache efímera y dependencias ausentes | ReceptionReleaseGateTest y comando de gate |

## Despliegue y bitácora — 23/10/2026, America/Guatemala

1. Validar la instalación dedicada y comandos de semana 16 con datos ficticios. Mantener secretos fuera de Git y backups antes de aplicar migraciones en un hospital real.
2. Registrar ReceptionSecurityServiceProvider en bootstrap/providers.php. Aplica el middleware únicamente al prefijo de muestras y conserva JWT/RBAC.
3. Para una instalación compartida, configurar APP_URL=https://..., certificado válido, document root en public/, APP_DEBUG=false, cookies de refresh seguras y CACHE_STORE persistente.
4. Ejecutar php artisan reception:release-gate. No habilitar tráfico si devuelve código distinto de cero. El flag --loopback-demo solo permite HTTP en 127.0.0.1/localhost y nunca habilita HTTP público.
5. Ejecutar php artisan test tests/Feature/Reception/Deployment y repetir la validación HTTP de semana 16.
6. Conservar evidencia sanitizada; nunca copiar .env, fixture de credenciales, Authorization, contraseñas o datos de pacientes reales a PR/bitácora.

Resultados observados: gate on-premise con --loopback-demo aprobado; diagnóstico ready=true; las 11 pruebas nuevas de integración/seguridad pasaron con 48 aserciones sobre CENTRAL/HOSPITAL PostgreSQL. El gate rechaza HTTP público incluso con el flag de demo. La compilación Vue y el flujo real del navegador se verificaron en la instalación on-premise.

Operación: límite por defecto de 60 solicitudes por minuto por IP; revisar capacidad si varios puestos comparten NAT. Los logs de proxy/servidor deben ocultar parámetros y códigos de muestra; el middleware solo registra la ruta patrón. El fixture queda en storage privado y el seeder rechaza entornos distintos de staging o bases que no terminen en _staging.

Rollback de aplicación: retirar este proveedor y restaurar el artefacto anterior del módulo; este PR no crea ni borra tablas clínicas. No deshacer migraciones de custodia ni eliminar muestras como rollback de una versión.

Alcance demostrado: entrega académica on-premise local con datos ficticios. Un despliegue público de producción necesita servidor, dominio/certificado y autorización del hospital; no se afirma haberlo realizado. GitHub Actions continúa sujeto al bloqueo de facturación de la cuenta, por lo que la evidencia aportada es de ejecuciones locales reales.
