# Matriz de amenazas - Dashboard base (Semana 17)

Alcance: `GET /api/v1/dashboard/resumen`, el middleware `EnsureHospitalDatabaseTenant` y la composicion
de tarjetas de `micro-his/dashboard-base`. El dashboard falla cerrado: sin permiso no consulta la fuente.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| T01 | Ver el resumen sin sesion | `jwt.auth` y comprobacion de usuario en el controlador | 401 `unauthenticated` | Mitigada |
| T02 | Pedir el resumen de otro hospital | `EnsureHospitalDatabaseTenant` compara el hospital del tenant con `HOSPITAL_DB_TENANT_ID` | 403 `HOSPITAL_DATABASE_TENANT_MISMATCH` | Mitigada |
| T03 | Arrancar sin base hospitalaria asignada | El middleware responde si falta la configuracion | 503 `HOSPITAL_DATABASE_TENANT_UNCONFIGURED` | Mitigada |
| T04 | Elegir el rol o los permisos desde el cliente | No se acepta rol en la peticion; los permisos salen de `RoleResolver` y `config/rbac.php` | Prueba de S16 `sin permiso de citas no hay tarjeta` | Mitigada |
| T05 | Ver datos que el rol no puede consultar | Falla cerrada: sin `citas.ver` no se consulta la fuente | `data.tarjetas` vacio para laboratorio | Mitigada |
| T06 | Inyectar una fecha invalida | Texto con formato `AAAA-MM-DD` y `checkdate` (`fecha[]` no llega a 500) | 422 `validation_failed` | Mitigada |
| T07 | Filtrar el error de la fuente | Se traduce a un codigo propio sin detalle interno | 503 `dashboard_source_unavailable` | Mitigada |
| T08 | Filtrar datos internos o secretos | La respuesta compone solo campos del read model | La prueba no encuentra `password`, `token`, `SQLSTATE`, `updated_at` | Mitigada |
| T09 | Cachear el resumen entre usuarios | `Cache-Control: no-store, private` en exito y error | Cabeceras verificadas | Mitigada |
| T10 | Suplantar la sesion con un rol de otro hospital | Los roles se resuelven para el hospital del tenant; sin membresia no hay roles | Prueba de aislamiento y T02 | Mitigada |
| T11 | Forzar consultas costosas con fechas repetidas | Rate limiting comun de `/api/v1` | No existe control especifico verificable | Pendiente: API Gateway |
| T12 | Depender de una fuente caida | El controlador responde 503 y no deja caer la peticion | Prueba de S16 y T07 | Mitigada |

## Riesgo residual y acciones

- **PostgreSQL real:** las pruebas usan `CitasDelDia` en memoria; falta un recorrido con base HOSPITAL
  real para confirmar la consulta por fecha.
- **Zona horaria:** la fecha por defecto usa `now()`; fijar `timezone` de la conexion `hospital`.
- **API Gateway:** definir y probar el limite de tasa comun de `/api/v1`.
