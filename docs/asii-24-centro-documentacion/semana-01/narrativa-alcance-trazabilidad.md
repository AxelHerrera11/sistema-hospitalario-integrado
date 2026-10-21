# Narrativa de alcance y trazabilidad

**Módulo:** `ASII-24 - Centro de documentación y manuales por rol` | **Issue:** `#208` | **PR:** hacia `develop` con `Refs #208`.
**Proceso modelado:** publicación y consulta de documentación y manuales diferenciados por rol.

## Alcance y carácter propuesto

El proceso permite a un **responsable de documentación** publicar y clasificar documentos y manuales indicando qué roles pueden verlos, y a un **usuario por rol** (médico, enfermería, recepción, administración) consultar un catálogo filtrado, buscar por texto o categoría, ver la vigencia y descargar el documento que le corresponde. El acceso queda sujeto al rol y al tenant.

El modelo es coherente con la especificación base publicada en `docs/asii-24-centro-documentacion/openapi.yaml` (PR #248), que se mantiene abierta a nuevas rutas sin editar las ya publicadas (ISP). Los identificadores `CU-01`..`CU-06`, `RF-01`..`RF-08` y las excepciones `EX-01`..`EX-04` son una propuesta de esta entrega: la asignación pide modelar el proceso, no fija una numeración oficial. Solo se modela el diseño; no se implementa backend, endpoints ni interfaz en este entregable.

## Actores y participantes

| Elemento | Rol en el proceso |
|---|---|
| Responsable de documentación | Actor principal: publica, clasifica y versiona |
| Usuario por rol | Actor principal: consulta, busca y descarga según su rol |
| Servicio de autenticación y autorización (JWT) | Actor secundario: valida sesión, rol y tenant |
| API /documentacion | Participante: expone publicación y consulta |
| Servicio de documentación | Participante: valida, clasifica e indexa |
| Base de datos | Participante: persiste documentos, roles y vigencia |

## Casos de uso

| ID | Caso de uso | Descripción |
|---|---|---|
| `CU-01` | Publicar documento o manual | Registra el documento con sus roles destinatarios |
| `CU-02` | Clasificar por rol y catálogo | Asigna roles, categoría y metadatos |
| `CU-03` | Consultar documentación según rol | Lista el catálogo visible para el rol |
| `CU-04` | Buscar y filtrar documentos | Filtra por texto, categoría o vigencia |
| `CU-05` | Descargar documento | Entrega el archivo permitido al rol |
| `CU-06` | Gestionar versiones y vigencia | Mantiene versiones y su vigencia |

`CU-01` incluye `CU-02` y la validación de acceso. `CU-03` incluye la validación de acceso y extiende a buscar (`CU-04`), descargar (`CU-05`) y ver vigencia (`CU-06`). El catálogo visible se filtra siempre por el rol del usuario.

## Diagrama de actividad

Decisiones, excepciones y resultado:

- `¿Autorizado a publicar?` (No): responder 403 y terminar.
- `¿Datos y roles válidos?` (No): responder 422 con detalle y terminar.
- `¿Documento visible para el rol?` (No): ocultar o responder 403 y terminar; (Sí): ver detalle y descargar.

El flujo tiene una salida explícita por cada rama: sin permiso de publicación, datos inválidos, documento fuera del catálogo del rol y consulta/descarga exitosa.

## Diagrama de secuencia

Participantes: responsable de documentación, usuario por rol, API /documentacion, servicio de documentación, base de datos y Auth/roles. La secuencia cubre dos tramos: **publicación** (verificación de permiso, validación de datos, persistencia de documento y roles) y **consulta por rol** (listado filtrado por rol), con alternativas `alt` para 403, 422 y un bloque `opt` para un documento no visible para el rol.

## Trazabilidad

| ID | Requisito | Caso de uso | Actividad | Secuencia |
|---|---|---|---|---|
| `RF-01` | Publicar un documento o manual con roles destinatarios | `CU-01` | Publicar documento | R → API → SVC |
| `RF-02` | Asignar categoría y metadatos de catálogo | `CU-02` | Clasificar por rol y catálogo | SVC → DB |
| `RF-03` | Listar el catálogo filtrado por el rol del usuario | `CU-03` | Ver solo documentos permitidos | listarParaRol(rol) |
| `RF-04` | Buscar y filtrar por texto o categoría | `CU-04` | Buscar y filtrar | GET /documentacion (filtros) |
| `RF-05` | Descargar el documento permitido al rol | `CU-05` | Descargar documento | opt descarga |
| `RF-06` | Mantener versiones y vigencia | `CU-06` | Ver detalle y vigencia | SVC → DB |
| `RF-07` | Ocultar o denegar documentos fuera del rol | `CU-03` | ¿Documento visible para el rol? (No) | `opt Documento no visible` (403) |
| `RF-08` | Validar los datos y roles antes de publicar | `CU-01` | ¿Datos y roles válidos? | `alt Datos inválidos` (422) |
| `RNF-01` | Verificar autenticación, rol y tenant antes de operar | `CU-01`..`CU-06` | ¿Autorizado? | Auth/roles |
| `RNF-02` | Respetar el aislamiento por hospital/tenant | Condición de acceso | Condición de acceso | Cabecera X-Tenant-ID |

## Trazabilidad de excepciones

| ID | Excepción | Actividad | Secuencia | Resultado esperado |
|---|---|---|---|---|
| `EX-01` | Sin permiso de publicación | ¿Autorizado a publicar? (No) | `alt Sin permiso` | 403 |
| `EX-02` | Datos o roles inválidos | ¿Datos y roles válidos? (No) | `alt Datos inválidos` | 422 con detalle |
| `EX-03` | Documento fuera del catálogo del rol | ¿Documento visible para el rol? (No) | `opt Documento no visible` | 403 |
| `EX-04` | Falta de sesión o tenant inválido | ¿Autorizado? (No) | Auth/roles | 401/403 |

## Existente y propuesto

| Componente o regla | Estado |
|---|---|
| Módulo ASII-24, proceso y alcance | **Propuestos/descritos** en esta entrega (UML) |
| Especificación base OpenAPI (`docs/asii-24-centro-documentacion/openapi.yaml`) | **Publicada por separado** (PR #248); no forma parte de este PR |
| Publicación, catálogo por rol, versiones y descarga | **Fuera de alcance** de la Semana 1 |
| Sesión, rol y permisos (RBAC) | **Scaffold en curso** en el repositorio; pendiente de acuerdos del grupo |

## Exclusiones

No se definen RF/RNF formales de arquitectura, SOLID, backend, endpoints ni interfaz implementada. Quedan fuera del proceso los reportes, el dashboard, la gestión de citas y la carga de archivos binarios.
