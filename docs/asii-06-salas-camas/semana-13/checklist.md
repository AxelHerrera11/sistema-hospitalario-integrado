# Checklist técnico — ASII-06

**Estados:** Conforme = evidencia suficiente dentro del alcance revisado;
Parcial = existe implementación pero falta una dimensión de verificación;
Riesgo = limitación relevante; Pendiente = no se ejecutó o implementó.

| ID | Control | Evidencia | Estado |
|---|---|---|---|
| RT-01 | Alcance semanal | Plantilla: “Semana 13: revisión técnica formal”; issue #11 sin criterios semanales adicionales. | Conforme |
| RT-02 | Listado de wards | `GET /api/v1/wards`; respuesta incluye `ward_id`, `code`, `name`, `location`, `status`; Feature valida colección vacía y error seguro. | Conforme |
| RT-03 | Camas por ward | `GET /api/v1/wards/{ward}/beds`; usa `ward_id`; Feature comprueba relación, exclusión de otra ward, vacío y ward inexistente. | Conforme |
| RT-04 | Datos expuestos | Presenter HTTP devuelve solo campos de catálogo previstos; no incluye información de pacientes. | Conforme |
| RT-05 | Manejo de errores | 401/403 por auth/RBAC, 404 `WARD_NOT_FOUND`, 503 de persistencia; respuesta sin detalles internos. | Conforme en pruebas API |
| RT-06 | Ruta y menú Vue | `/wards` protegido por guard; navegación visible según `user.permissions`. | Conforme en código |
| RT-07 | Permiso de cliente y servidor | Ruta usa `camas.ver`; middleware HTTP usa `rbac:camas.ver`; Feature y test Node comprueban autorización/gating. | Conforme en pruebas automatizadas |
| RT-08 | Uso del cliente HTTP | La vista consume Axios compartido, que envía Bearer token y `X-Tenant-ID` desde estado de sesión. | Conforme en código |
| RT-09 | Estados Vue | Carga, vacío, errores, reintento y respuesta obsoleta implementados. Build valida compilación; no prueba renderizado o interacción. | Parcial |
| RT-10 | Adaptación y accesibilidad | Layout responsive, controles nativos, foco visible y anuncios `status`/`alert`; no se realizó prueba manual o WCAG. | Parcial |
| RT-11 | Capas backend | Ruta/provider → controlador → servicio → repositorios → adaptadores PDO. Sin SQL duplicado en controlador. | Conforme |
| RT-12 | Persistencia | Integración PostgreSQL del catálogo verifica consultas/relación en transacción y rollback. | Conforme en prueba de integración |
| RT-13 | Aislamiento por tenant | Se confirmó cruce antes de #1154. Guard temporal `HOSPITAL_DB_TENANT_ID` deniega tenant distinto o asignación ausente; conexión dinámica por hospital sigue pendiente. | Contención temporal; no afirmar aislamiento completo |
| RT-14 | Login real y recorrido visual | No se facilitó cuenta de pruebas autorizada ni se inspeccionó navegador autenticado. Suites de tenant son aisladas. | Pendiente |

## Interpretación

“Conforme” se limita a la evidencia indicada y no equivale a aprobación formal
del docente/reviewer. “Parcial” y “Pendiente” no significan necesariamente
defecto de código. El cruce por conexión fija se confirmó y se bloqueó
temporalmente para estas rutas; la asignación de una base por hospital según
la arquitectura acordada sigue sin implementarse.
