# Narrativa de alcance y trazabilidad

**Módulo:** `ASII-26 - Dashboard base UI/UX e implementación transversal` | **Issue:** `#209` | **PR:** hacia `develop` con `Refs #209`.
**Proceso modelado:** navegación del dashboard con estados de carga, vacío, error y resumen inicial.

## Alcance y carácter propuesto

El proceso permite a un **usuario autenticado** ingresar al dashboard con un shell base UI/UX, navegar un menú construido según su rol y sus permisos, y observar el **resumen inicial** con sus estados de interfaz: carga, vacío, error con reintento y datos disponibles. Cuando la sesión o el permiso no permiten el acceso, el sistema redirige a login (401) o a la pantalla sin autorización (403).

El entregable define la base transversal reutilizable por el resto de módulos (layout, navegación, guards de router y estados de UI). Los identificadores `CU-D1`..`CU-D7`, `RF-01`..`RF-08` y las excepciones `EX-01`..`EX-03` son una propuesta de esta entrega: la asignación pide modelar el proceso, no fija una numeración oficial. Solo se modela el diseño; no se implementa backend, endpoints ni pantallas finales para cada módulo.

## Actores y participantes

| Elemento | Rol en el proceso |
|---|---|
| Usuario autenticado | Actor principal: ingresa, navega y consulta el resumen |
| Servicio de autenticación y autorización (JWT) | Actor secundario: provee sesión, rol y permisos |
| AppLayout / Navigation | Participante: shell de la interfaz y menú por rol |
| Router Guard | Participante: valida sesión y permisos antes de mostrar la vista |
| DashboardController (API) | Participante: entrega el resumen inicial |
| Base de datos | Participante: provee los datos del resumen |

## Casos de uso

| ID | Caso de uso | Descripción |
|---|---|---|
| `CU-D1` | Entrar al dashboard | Valida la sesión y habilita el shell base |
| `CU-D2` | Navegar menú por rol | Construye y muestra el menú según rol y permisos |
| `CU-D3` | Ver estado de carga | Presenta el indicador mientras se obtiene el resumen |
| `CU-D4` | Ver estado vacío | Informa cuando no hay datos para mostrar |
| `CU-D5` | Ver estado de error con reintento | Informa el error y ofrece reintentar |
| `CU-D6` | Consultar resumen inicial | Obtiene y presenta el resumen del dashboard |
| `CU-D7` | Manejar 401/403 y redirección | Redirige a login o a la pantalla de sin autorización |

`CU-D1` y `CU-D2` incluyen la validación de sesión y permisos (Router guard). `CU-D6` incluye el estado de carga (`CU-D3`) y extiende a estado vacío (`CU-D4`) y a estado de error con reintento (`CU-D5`). `CU-D7` extiende la validación cuando no hay sesión o no hay autorización.

## Diagrama de actividad

Decisiones, excepciones y resultado:

- `¿Sesión válida y token presente?` (No): redirigir a login y finalizar.
- `¿Autorizado para esta vista?` (No): redirigir a 403 y finalizar.
- `¿Respuesta exitosa?` (No): mostrar estado de error con reintento y finalizar.
- `¿Hay datos para mostrar?` (No): mostrar estado vacío; (Sí): mostrar resumen inicial.

El flujo tiene una salida explícita por cada rama: sin sesión, sin autorización, error de consulta, estado vacío y resumen visible.

## Diagrama de secuencia

Participantes: usuario autenticado, AppLayout/Navigation, Router Guard, Session/Auth, DashboardController (API) y base de datos. La secuencia muestra: verificación de token y tenant, control de permiso del menú, alternativas `alt` para sesión inválida (401), sin permiso (403), error de consulta (500/timeout → reintento), estado vacío y estado con datos, anotando el estado de UI correspondiente en cada caso.

## Trazabilidad

| ID | Requisito | Caso de uso | Actividad | Secuencia |
|---|---|---|---|---|
| `RF-01` | Validar la sesión antes de mostrar el dashboard | `CU-D1` | ¿Sesión válida y token presente? | Guard → Auth: Verificar token |
| `RF-02` | Redirigir a login cuando no hay sesión | `CU-D7` | Redirigir a login | `alt Sin sesión` (401) |
| `RF-03` | Construir el menú según el rol | `CU-D2` | Verificar permisos del ítem de menú | Guard → Guard: permiso del menú |
| `RF-04` | Redirigir a 403 cuando falta autorización | `CU-D7` | Redirigir a 403 | `alt Sin permiso` (403) |
| `RF-05` | Mostrar el estado de carga al consultar | `CU-D3` | Mostrar estado de carga | `GET /resumen` (cargando) |
| `RF-06` | Mostrar el resumen inicial | `CU-D6` | Mostrar resumen inicial | `alt Datos disponibles` |
| `RF-07` | Gestionar el estado vacío | `CU-D4` | ¿Hay datos para mostrar? (No) | `alt Datos vacíos` |
| `RF-08` | Gestionar el estado de error con reintento | `CU-D5` | ¿Respuesta exitosa? (No) | `alt Error de consulta` |
| `RNF-01` | Reutilizar un shell UI/UX común a los módulos | `CU-D1`,`CU-D2` | Shell base | AppLayout/Navigation |
| `RNF-02` | Respetar el aislamiento por hospital/tenant | Condición de acceso | Condición de acceso | Guard → Auth: token y tenant |

## Trazabilidad de excepciones

| ID | Excepción | Actividad | Secuencia | Resultado esperado |
|---|---|---|---|---|
| `EX-01` | Sesión ausente o token inválido | Redirigir a login | `alt Sin sesión` (401) | El usuario vuelve a autenticarse |
| `EX-02` | Falta de autorización para la vista | Redirigir a 403 | `alt Sin permiso` (403) | Se muestra pantalla de sin autorización |
| `EX-03` | Error al consultar el resumen | Estado de error con reintento | `alt Error de consulta` | El usuario puede reintentar la consulta |

## Existente y propuesto

| Componente o regla | Estado |
|---|---|
| Módulo ASII-26, proceso y alcance | **Propuestos/descritos** en esta entrega (UML) |
| Contrato UI base (`docs/asii-26-dashboard-base/contrato-ui-base.md`) | **Publicado por separado** en la tarea de contrato; no forma parte de este PR |
| Shell de layout, navegación y guards (`resources/js/shared/`, `resources/js/router/`) | **Referenciados** como base existente para el diseño |
| Sesión, rol y permisos (RBAC) | **Scaffold en curso** en el repositorio; pendiente de acuerdos del grupo |
| Implementación de cada resumen por módulo | **Fuera de alcance** de la Semana 1 |

## Exclusiones

No se definen RF/RNF formales de arquitectura, SOLID, backend, endpoints ni pantallas finales por módulo. Quedan fuera del proceso los analytics e indicadores, los reportes exportables, la gestión de citas y la documentación por rol.
