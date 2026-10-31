# Matriz de amenazas - Centro de documentacion (Semana 17)

Alcance: `GET /api/v1/documentacion`, `GET /api/v1/documentacion/{id}` y el catalogo de
`config/centro_documentacion.php`. El servidor es la autoridad; ocultar documentos en la interfaz no
sustituye JWT, tenant ni RBAC.

| ID | Amenaza | Control | Evidencia | Estado |
|---|---|---|---|---|
| T01 | Consultar sin sesion | `jwt.auth` antes de RBAC | 401 `AUTH_REQUIRED` | Mitigada |
| T02 | Leer el catalogo de otro hospital | `RequirePermission` compara el hospital del tenant con `X-Tenant-ID` | 403 `TENANT_MISMATCH` | Mitigada |
| T03 | Usuario sin membresia en el hospital | `RoleResolver` devuelve cero roles y RBAC deniega | 403 `RBAC_DENIED` | Mitigada |
| T04 | Elegir el rol desde el cliente (`?rol=`) | El rol se resuelve del JWT y el tenant; el parametro se ignora | Prueba de S16 `el rol enviado por el cliente se ignora` | Mitigada |
| T05 | Ver un documento de otro rol | El catalogo filtra por roles de sesion | Enfermeria no ve `openapi-v1` | Mitigada |
| T06 | Descubrir la existencia de un documento ajeno | Un documento no visible responde igual que uno inexistente | 404 `document_not_found` con el mismo mensaje | Mitigada |
| T07 | Salir de `docs/` con una ruta manipulada | `realpath` debe quedar dentro de `docs/`; el catalogo valida el patron de ruta | Verificacion `rechaza ruta fuera de docs/` | Mitigada |
| T08 | Filtrar rutas internas o roles en el catalogo | La respuesta usa solo `id`, `titulo`, `tipo` y `version` | La prueba no encuentra `ruta` ni `docs/` | Mitigada |
| T09 | Filtrar el contenido de un archivo por error | El contenido solo se sirve si el documento es visible y el archivo es real | 404 y tipo `application/yaml`/`text/markdown` | Mitigada |
| T10 | Cachear documentos entre usuarios | `Cache-Control: no-store, private` en catalogo, contenido y 404 | Cabeceras verificadas | Mitigada |
| T11 | Publicar desde la interfaz sin permiso aprobado | No existe publicacion; el catalogo se administra en configuracion | Fuera de alcance de S17 | Pendiente: permiso de RBAC |
| T12 | Agotar las rutas con solicitudes repetidas | Rate limiting comun de `/api/v1` | No existe control especifico verificable | Pendiente: API Gateway |

## Riesgo residual y acciones

- **Publicacion de documentos:** requiere un permiso nuevo que debe proponerse a RBAC (ASII-02); por
  ahora el catalogo se administra en `config/centro_documentacion.php`.
- **PostgreSQL real:** no aplica al catalogo (no toca base HOSPITAL), pero el flujo integrado sigue
  pendiente de un recorrido con base real.
- **API Gateway:** definir y probar el limite de tasa comun de `/api/v1`.
