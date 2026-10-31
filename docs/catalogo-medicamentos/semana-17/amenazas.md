# Matriz de amenazas (STRIDE) del catálogo

Objeto: `/api/v1/medications` (PR #602), el micro-HIS y el prototipo. Pruebas: `tests/Feature/MedicationCatalog/` (`MedicationCatalogApiTest` = A1–A6; `MedicationCatalogSecurityTest` = T1–T6).

| ID | STRIDE | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|---|
| M1 | Suplantación | Llamar al API sin identidad | `jwt.auth` y `RequirePermission` (401 `AUTH_REQUIRED`) | A1 | Probado (identidad sustituida en la prueba) |
| M2 | Suplantación | Elegir otro hospital con `X-Tenant-ID` o con el cuerpo | El hospital sale del contexto; la cabecera debe coincidir | A1, T3 | Probado |
| M2b | Elevación | Miembro válido del hospital B usa la base HOSPITAL del hospital A | `EnsureHospitalDatabaseTenant` tras `jwt.auth`: 403 `HOSPITAL_DATABASE_TENANT_MISMATCH` sin tocar filas. Requiere `HOSPITAL_DB_TENANT_ID` con el UUID del hospital dueño de la base. | `test_t3b_…`; recorrido con JWT y PostgreSQL reales | Probado; defecto hallado en auditoría el 30/10/2026. La selección de base por hospital sigue en Plataforma/Tenants |
| M3 | Manipulación | Inyección SQL en `q` o en el cuerpo | Sentencias preparadas, `%` literal | T1, `tests/pdo.php` | Probado (SQLite real) |
| M4 | Manipulación | Asignación masiva (`validity`, id u hospital en el alta) | El Router solo pasa campos conocidos y el dominio fija `active` | T2 | Probado |
| M5 | Manipulación | Cambiar la vigencia sin confirmar o en el medicamento equivocado | Confirmación con el registro completo y éxito solo tras `rowCount` | Prototipo D7–D9, `tests/casos.php` W2 | Probado (UI simulada) |
| M6 | Repudio | No poder rastrear quién cambió la vigencia | `trace_id` en cada respuesta; denegaciones registradas por `LogDenials`; `AuditedCatalogWrites` registra `catalogo.medicamento_registrado` y `catalogo.vigencia_cambiada` en `audit_log` de CENTRAL (actor, hospital, medicamento y `trace_id`) y revierte la escritura si la auditoría falla | A3, T5; `MedicationCatalogApiTest` (evento y 503 `AUDIT_UNAVAILABLE`); `MedicationCatalogPostgresTest` (reversión en PostgreSQL) | **Probado.** Riesgo residual: si falla el `COMMIT` de HOSPITAL después de auditar, queda un evento sin cambio (sobrerregistro, no repudio) |
| M7 | Divulgación | Errores con SQL, rutas o trazas | `CatalogResponse` con mensajes fijos | A6, T4 | Probado |
| M8 | Divulgación | Respuestas cacheadas por intermediarios | `Cache-Control: no-store, private` | A3, T4 | Probado |
| M9 | Divulgación | Exponer datos de pacientes | El catálogo no tiene datos de pacientes; la respuesta tiene 6 campos fijos | T6 | Probado |
| M10 | Divulgación | Enumerar medicamentos por ids secuenciales | `medication_id` es `uuid` asignado por PostgreSQL (H5, #766); un id con otro formato responde 404 (#774) | `MedicationCatalogPostgresTest` (uuid y 404 para `999`) | **Probado** en la base HOSPITAL. Los adaptadores InMemory y SQLite, solo de pruebas y prototipo, siguen con ids secuenciales |
| M11 | Divulgación | Inyección de cabeceras con `X-Request-Id` | Solo se acepta `[A-Za-z0-9._-]{8,64}` | T5 | Probado |
| M12 | Denegación de servicio | Búsquedas costosas o masivas | Término de 2–60 y máximo 50 resultados; `CatalogRateLimit`: 60 solicitudes por minuto por usuario y hospital, 429 `CATALOG_RATE_LIMITED` con `Retry-After` | `tests/casos.php` B2; `MedicationCatalogApiTest` (429 y cupo independiente) | **Probado.** El contador usa la caché configurada (`database` en CENTRAL); el valor 60 es una decisión de la autora, revisable |
| M13 | Denegación de servicio | Caída de la base HOSPITAL que deje la UI en estado incoherente | 503 explícito; el cliente no reintenta escrituras | A6, prototipo D12/M12 | Probado |
| M14 | Elevación | Escribir sin permiso | `rbac:medicamentos.registrar` y `rbac:medicamentos.cambiar_vigencia`, sin conceder | A4 (403 para medico, admin_hospital y superadmin) | Probado |
| M15 | Elevación | Rol que lee sin tener permiso | Matriz real de `config/rbac.php` | A2 (recepcion 403) | Probado |
| M16 | Elevación | Inyección de HTML en datos mostrados por la UI | `textContent` en el prototipo | Prototipo D11/M11 | Probado (prototipo) |

**Riesgos abiertos priorizados:** M6, M10 y M12 quedaron mitigados el 21/10/2026, con los riesgos residuales de cada fila (ver `evidencia-funcional.md`). Ninguno tiene revisión humana independiente.
