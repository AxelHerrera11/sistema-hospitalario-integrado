# Narrativa de alcance y trazabilidad

**Módulo:** `ASII-05 - Gestión de citas` | **Issue:** `#207` | **PR:** hacia `develop` con `Refs #207`.
**Proceso modelado:** solicitud, validación de disponibilidad, confirmación o cancelación de cita.

## Alcance y carácter propuesto

El proceso permite a un **usuario de admisión** solicitar una cita (paciente, doctor, especialidad, horario y motivo), validar su disponibilidad y las reglas del horario, registrarla en estado `scheduled` y luego confirmarla o cancelarla. La validación de disponibilidad cubre el solape de paciente o doctor (exclusión GiST) y la especialidad activa del doctor en la fecha de inicio.

El modelo se deriva de la migración real `database/migrations/hospital/2026_08_11_000300_create_scheduling_and_bed_tables.php` y del contrato `docs/asii-05-citas/contrato-api.md` (publicado en otra tarea). Los identificadores `CU-01`..`CU-06`, `RF-01`..`RF-08` y las excepciones `EX-01`..`EX-04` son una propuesta de esta entrega: la asignación pide modelar el proceso, no fija una numeración oficial. Solo se modela el diseño; no se implementa backend, endpoints ni interfaz en este entregable.

## Actores y participantes

| Elemento | Rol en el proceso |
|---|---|
| Usuario de admisión | Actor principal: solicita, confirma, cancela y consulta |
| Servicio de autenticación y autorización (JWT) | Actor secundario: valida acceso, permisos y tenant |
| API /citas | Participante: expone las operaciones del contrato |
| Servicio de citas | Participante: valida reglas y coordina el registro |
| Repositorio (appointments) | Participante: consulta solapes y persiste la cita |
| Base de datos | Participante: aplica exclusiones GiST y restricciones |

## Casos de uso

| ID | Caso de uso | Descripción |
|---|---|---|
| `CU-01` | Solicitar cita | Inicia el proceso con los datos de la cita |
| `CU-02` | Validar disponibilidad y reglas del horario | Comprueba solape, especialidad activa y rango |
| `CU-03` | Registrar la cita | Crea la cita en estado `scheduled` |
| `CU-04` | Confirmar cita | Transiciona `scheduled` → `confirmed` |
| `CU-05` | Cancelar cita | Transiciona `scheduled`/`confirmed` → `cancelled` |
| `CU-06` | Consultar citas y detalle | Lista paginada y detalle por identificador |

`CU-01` incluye `CU-02`, que a su vez incluye `CU-03` y la validación de acceso. `CU-04`, `CU-05` y `CU-06` incluyen la validación de acceso y tenant. Las extensiones cubren confirmar al crear y cancelar tras registrar.

## Diagrama de actividad

Decisiones, excepciones y resultado:

- `¿Autorizado?` (No): responder 403 y terminar.
- `¿Datos y horario válidos?` (No): responder 422 con detalle y terminar.
- `¿Doctor tiene especialidad activa?` (No): responder 422 (specialty trigger) y terminar.
- `¿Horario libre?` (No): responder 409 (appointments_overlap) y terminar; (Sí): registrar `scheduled`.

El flujo tiene una salida explícita por cada rama: sin autorización, datos inválidos, especialidad inactiva, solape y registro exitoso.

## Diagrama de secuencia

Participantes: usuario de admisión, API /citas, servicio de citas, repositorio (appointments), base de datos y Auth/tenant. La secuencia muestra la verificación de token y tenant, la validación de campos y rango, la comprobación de especialidad activa, la consulta de solape con alternativas `alt` para acceso denegado, datos inválidos, solape (409) y registro exitoso (201), y un bloque `opt` para confirmar o cancelar la cita.

## Trazabilidad

| ID | Requisito | Caso de uso | Actividad | Secuencia |
|---|---|---|---|---|
| `RF-01` | Solicitar una cita con paciente, doctor, especialidad, horario y motivo | `CU-01` | Solicitar cita | U → API → SVC |
| `RF-02` | Validar campos y rango `ends_at > starts_at` | `CU-02` | ¿Datos y horario válidos? | Validar campos y rango |
| `RF-03` | Validar la especialidad activa del doctor en `starts_at` | `CU-02` | ¿Doctor tiene especialidad activa? | Comprobar especialidad activa |
| `RF-04` | Detectar solape de paciente o doctor | `CU-02` | ¿Horario libre? | buscarSolape / GiST |
| `RF-05` | Registrar la cita en estado `scheduled` | `CU-03` | Registrar cita scheduled | crear / INSERT |
| `RF-06` | Confirmar la cita (`scheduled` → `confirmed`) | `CU-04` | Transición de estado | opt Confirmar |
| `RF-07` | Cancelar la cita (`scheduled`/`confirmed` → `cancelled`) | `CU-05` | Transición de estado | opt Cancelar |
| `RF-08` | Consultar el listado y el detalle de citas | `CU-06` | Consulta | U → API → SVC |
| `RNF-01` | Verificar autenticación y permisos antes de operar | `CU-01`..`CU-06` | ¿Autorizado? | Auth/tenant |
| `RNF-02` | Respetar el aislamiento por hospital/tenant | Condición de acceso | Condición de acceso | Cabecera X-Tenant-ID |

## Trazabilidad de excepciones

| ID | Excepción | Actividad | Secuencia | Resultado esperado |
|---|---|---|---|---|
| `EX-01` | Datos o rango inválidos | ¿Datos y horario válidos? (No) | `alt Datos inválidos` | 422 con detalle |
| `EX-02` | Doctor sin especialidad activa | ¿Especialidad activa? (No) | 422 specialty trigger | 422 con código |
| `EX-03` | Solape de paciente o doctor | ¿Horario libre? (No) | `alt Solape activo` | 409 `appointments_overlap` |
| `EX-04` | Falta de sesión o permiso | ¿Autorizado? (No) | `alt Sin sesión o sin permiso` | 401/403 |

## Existente y propuesto

| Componente o regla | Estado |
|---|---|
| Módulo ASII-05, proceso y alcance | **Propuestos/descritos** en esta entrega (UML) |
| Contrato API de citas (`docs/asii-05-citas/contrato-api.md`) | **Publicado por separado** (PR #245); no forma parte de este PR |
| Tabla `appointments`, exclusiones GiST y triggers | **Existentes** en la migración real del repositorio |
| Dominio y repositorio (interfaz + adaptador) | **Desarrollados** en la tarea de dominio; referidos por su contrato |
| Middleware `permission:citas.*` | **Pendiente** de María; la autorización efectiva se difiere |

## Exclusiones

No se definen RF/RNF formales de arquitectura, SOLID, backend, endpoints ni interfaz implementada. Quedan fuera del proceso los reportes, el dashboard, la documentación por rol y la creación de tablas nuevas (el módulo no crea tablas).
