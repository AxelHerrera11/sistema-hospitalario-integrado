# Matriz de amenazas - Citas medicas y agenda (Semana 17)

Alcance: `GET/POST /api/v1/citas`, `GET /api/v1/citas/{id}`, las transiciones de estado y `DELETE`.
El servidor es la autoridad; ocultar acciones en la interfaz no sustituye JWT, tenant ni RBAC.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| T01 | Operar sin sesion o con token invalido | `jwt.auth` antes de RBAC y del controlador | `CitasSeguridadTest`: sin sesion 401 `AUTH_REQUIRED` | Mitigada |
| T02 | Leer o escribir en otro hospital por cabecera | `RequirePermission` compara el hospital del tenant con `X-Tenant-ID` | Cabecera distinta 403 `TENANT_MISMATCH` | Mitigada |
| T03 | Usuario sin membresia en el hospital | `RoleResolver` devuelve cero roles y RBAC deniega | 403 `RBAC_DENIED` | Mitigada |
| T04 | Rol sin permiso intenta crear o confirmar | Permisos separados por ruta | Enfermeria y medico reciben 403 al crear | Mitigada |
| T05 | Suplantar actor desde el cuerpo | El actor sale del JWT; el cuerpo solo aporta ids de paciente, doctor y especialidad | Pruebas de contrato de S16 y allowlist de respuesta | Mitigada |
| T06 | Agendar dos citas solapadas | Exclusion GiST en HOSPITAL; el doble de pruebas la reproduce | 409 `appointments_overlap` | Mitigada |
| T07 | Modificar una cita finalizada | Trigger de estado e inmutabilidad | 409 `appointments_finalized_immutable` | Mitigada |
| T08 | Enviar datos invalidos que lleguen a la base | Validacion en el borde antes del repositorio | 422 `validation_failed` con `errors` | Mitigada |
| T09 | Borrado fisico de citas | Ruta responde 405 sin tocar la base | 405 `appointments_delete_protection` | Mitigada |
| T10 | Filtrar datos internos del modelo | Respuesta por allowlist (`appointment_id`, paciente, doctor, especialidad, estado, motivo, fechas) | La prueba no encuentra `updated_at`, `deleted_at` ni secretos | Mitigada |
| T11 | Inyectar una traza no confiable | `X-Request-Id` solo se acepta con patron y longitud; si no, se genera | `trace-[0-9a-f]{12}` en el error | Mitigada |
| T12 | Cachear datos clinicos entre usuarios | `Cache-Control: no-store, private` en exito y error | Cabeceras verificadas en la prueba | Mitigada |
| T13 | Agotar las rutas con solicitudes repetidas | Rate limiting comun de `/api/v1` | No existe control especifico verificable | Pendiente: API Gateway |

## Riesgo residual y acciones

- **PostgreSQL real:** las pruebas usan un doble del repositorio; falta un recorrido con base HOSPITAL
  real para confirmar la exclusion GiST y los triggers de estado.
- **Zona horaria:** la conexion `hospital` no fija `timezone`; se asume UTC. Coordinar con plataforma.
- **API Gateway:** definir y probar el limite de tasa comun de `/api/v1`.
- **RBAC:** la matriz de permisos usada ya esta activa; cualquier cambio debe pasar por ASII-02.
