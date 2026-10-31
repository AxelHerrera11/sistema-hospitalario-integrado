# Matriz de amenazas - Reportes operativos (Semana 17)

Alcance: `GET /api/v1/reportes`, `GET /api/v1/reportes/{reporte}`,
`GET /api/v1/reportes/{reporte}/export` y el exportador CSV. El servidor es la autoridad; ocultar
reportes o el boton de exportar en la interfaz no sustituye JWT, tenant ni RBAC.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| T01 | Consultar reportes sin sesion | `jwt.auth` antes de RBAC | 401 `AUTH_REQUIRED` | Mitigada |
| T02 | Leer reportes de otro hospital | `RequirePermission` compara el hospital del tenant con `X-Tenant-ID` | 403 `TENANT_MISMATCH` | Mitigada |
| T03 | Usuario sin membresia en el hospital | `RoleResolver` devuelve cero roles y RBAC deniega | 403 `RBAC_DENIED` | Mitigada |
| T04 | Rol sin `reportes.ver` consulta el catalogo | Permiso por ruta | Enfermeria, recepcion y jefatura reciben 403 | Mitigada |
| T05 | Rol sin `reportes.exportar` descarga el CSV | Permiso separado en la ruta de exportacion | Medico recibe 403 al exportar | Mitigada |
| T06 | Arrancar sin base hospitalaria asignada | `EnsureHospitalDatabaseTenant` valida la configuracion | 503 `HOSPITAL_DATABASE_TENANT_UNCONFIGURED` | Mitigada |
| T07 | Inyectar formulas en el CSV | El exportador antepone apostrofo a celdas que empiezan con `=`, `+`, `-` o `@` | `'=Medicina` en la prueba | Mitigada |
| T08 | Descubrir reportes inexistentes | Un id desconocido responde con el sobre comun | 404 `report_not_found` | Mitigada |
| T09 | Pedir un formato no soportado | Lista de exportadores; solo CSV y solo texto (`format[]` no llega a 500) | 406 `report_format_unsupported` | Mitigada |
| T10 | Consultas sin rango o demasiado largas | Validacion de filtros antes de tocar la fuente | 422 `report_filter_invalid` y `report_range_too_long` | Mitigada |
| T11 | Filtrar datos internos o errores de SQL | La respuesta usa filas del reporte; la falla se traduce | La prueba no encuentra `SQLSTATE`, `password`, `updated_at` | Mitigada |
| T12 | Inyectar una traza no confiable | `X-Request-Id` solo se acepta con patron y longitud; si no, se genera | `trace-[0-9a-f]{12}` en el error | Mitigada |
| T13 | Cachear reportes entre usuarios | `Cache-Control: no-store, private` en JSON, CSV y error | Cabeceras verificadas | Mitigada |
| T14 | Agotar las rutas con exportaciones repetidas | Rate limiting comun de `/api/v1` | No existe control especifico verificable | Pendiente: API Gateway |

## Riesgo residual y acciones

- **PostgreSQL real:** la fuente de pruebas es un doble; falta un recorrido con base HOSPITAL real para
  confirmar los filtros y el orden del reporte.
- **Nuevos reportes y formatos:** se agregan a los catalogos (OCP), pero cada uno debe repetir la matriz
  de permisos y la prueba de formulas.
- **API Gateway:** definir y probar el limite de tasa comun de `/api/v1`.
