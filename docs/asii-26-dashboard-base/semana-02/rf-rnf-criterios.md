# RF/RNF y criterios de aceptación — Semana 2 (SRP)

**Módulo:** `ASII-26 - Dashboard base UI/UX e implementación transversal` | **Issue:** `#209`
**Proceso:** navegación del dashboard con estados de carga, vacío, error y resumen inicial. **Principio aplicado:** Single Responsibility (SRP).

## 1. Requisitos funcionales (RF)

| ID | Requisito |
|---|---|
| `RF-01` | El sistema debe validar la sesión y los permisos antes de mostrar el dashboard, resolviendo login (401), sin autorización (403) o acceso permitido. |
| `RF-02` | El sistema debe construir el menú de navegación según el rol y los permisos, sin depender de la consulta de datos. |
| `RF-03` | El sistema debe obtener el resumen inicial desde la API sin decidir el estado visual. |
| `RF-04` | El sistema debe traducir el resultado en un estado de UI: carga, vacío, error con reintento o datos disponibles. |
| `RF-05` | El sistema debe permitir agregar un ítem de menú o un origen de resumen nuevo **sin reescribir la lógica de acceso ni la de estado**. |
| `RF-06` | Ante falta de sesión, el sistema debe redirigir a login; ante falta de permiso, a la pantalla sin autorización. |

## 2. Requisitos no funcionales (RNF)

| ID | Requisito | Relación con SRP |
|---|---|---|
| `RNF-01` | Cambiar el menú, la carga, el estado o el acceso debe afectar **solo a la unidad con ese motivo de cambio**. | Cada clase agrupa una única razón para cambiar. |
| `RNF-02` | Cada responsabilidad (guard, menú, carga, estado) debe poder probarse de forma aislada. | Se depende de contratos, no de implementaciones. |
| `RNF-03` | El coordinador no debe contener reglas de permisos, catálogo de menú ni criterios de estado. | Evita reintroducir la clase "antes". |
| `RNF-04` | La separación no debe impedir reutilizar el menú o el resumen en otras vistas. | Unidad cohesiva y reutilizable. |

## 3. Criterios de aceptación

- **`AC-01` (acceso):** sin sesión la vista resuelve `login`; con sesión, `ok`.
- **`AC-02` (menú por rol):** un rol administrador obtiene más ítems que un rol no administrador.
- **`AC-03` (estado):** un resumen con datos resuelve `datos`; uno vacío resuelve `vacio`.
- **`AC-04` (extensión de menú sin modificar el coordinador):** al agregar `MenuConReportes`, el menú cambia **sin modificar `DashboardController`** (verificable en el diff del commit).
- **`AC-05` (extensión de carga sin modificar el coordinador):** al agregar `ResumenVacio`, el estado cambia **sin modificar `DashboardController`**.
- **`AC-06` (aislamiento):** cambiar el menú no altera la carga del resumen ni las reglas de acceso.

## 4. Trazabilidad con la evidencia

| Criterio | Evidencia |
|---|---|
| `AC-01`, `AC-02`, `AC-03` | `evidencia/demo-srp.php` → `DESPUÉS admin`, `DESPUÉS sin sesion`, salidas de estado |
| `AC-04` | `evidencia/demo-srp.php` → `DESPUÉS menu nuevo (controlador intacto)` |
| `AC-05` | `evidencia/demo-srp.php` → `DESPUÉS resumen vacio (loader nuevo)` |
| `AC-06` | `evidencia/demo-srp.php` → `DESPUÉS cambiar menu no altera la carga: OK` |
