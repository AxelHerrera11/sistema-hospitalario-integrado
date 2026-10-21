# RF/RNF y criterios de aceptación — Semana 2 (DIP)

**Módulo:** `ASII-05 - Gestión de citas` | **Issue:** `#207`
**Proceso:** solicitud, validación de disponibilidad, confirmación o cancelación de cita. **Principio aplicado:** Dependency Inversion (DIP).

## 1. Requisitos funcionales (RF)

| ID | Requisito |
|---|---|
| `RF-01` | El sistema debe registrar una cita en estado `scheduled` tras validar los datos. |
| `RF-02` | El sistema debe detectar el solape de paciente o doctor antes de registrar y responder 409 `appointments_overlap`. |
| `RF-03` | El sistema debe confirmar una cita (`scheduled` → `confirmed`) aplicando la política de estado. |
| `RF-04` | El sistema debe cancelar una cita (`scheduled`/`confirmed` → `cancelled`) aplicando la política de estado. |
| `RF-05` | El sistema debe rechazar una transición inválida con un error controlado. |
| `RF-06` | El sistema debe operar con la misma lógica de dominio **independientemente del mecanismo de persistencia**. |

## 2. Requisitos no funcionales (RNF)

| ID | Requisito | Relación con DIP |
|---|---|---|
| `RNF-01` | El dominio no debe depender de Eloquent ni de la base de datos concreta. | Depende de `AppointmentRepositoryContract`. |
| `RNF-02` | El caso de uso debe poder probarse sin base de datos real. | Se inyecta un adaptador en memoria. |
| `RNF-03` | Cambiar la persistencia no debe obligar a modificar el controlador ni la política de estado. | El adaptador es sustituible tras el contrato. |
| `RNF-04` | Las garantías de la base (solape, inmutabilidad) deben permanecer en el adaptador real. | El adaptador conoce las exclusiones GiST. |

## 3. Criterios de aceptación

- **`AC-01` (registro):** una cita válida se registra en estado `scheduled`.
- **`AC-02` (solape):** una cita que se solapa con otra activa se rechaza con 409 `appointments_overlap`.
- **`AC-03` (confirmar/cancelar):** `scheduled` → `confirmed` y luego → `cancelled` según la política.
- **`AC-04` (transición inválida):** una transición no permitida produce un error controlado.
- **`AC-05` (inversión):** el **mismo controlador** opera con otro adaptador (`InMemory`) sin modificar su código (verificable en el diff del commit).
- **`AC-06` (sin ORM):** en el diseño "antes", el servicio no puede operar sin base de datos; el diseño "después" sí.

## 4. Trazabilidad con la evidencia

| Criterio | Evidencia |
|---|---|
| `AC-01` | `evidencia/demo-dip.php` → `DESPUÉS registrar cita: A-1 scheduled` |
| `AC-02` | `evidencia/demo-dip.php` → `DESPUÉS solape detectado: 409 appointments_overlap` |
| `AC-03` | `evidencia/demo-dip.php` → `DESPUÉS confirmar: confirmed` / `DESPUÉS cancelar: cancelled` |
| `AC-04` | `solid-dip.md` §"Ejemplo concreto" + política de estado |
| `AC-05` | `evidencia/demo-dip.php` → `DESPUÉS misma logica con otro adaptador: OK` |
| `AC-06` | `evidencia/demo-dip.php` → `ANTES acoplado a Eloquent: ... (no inyectable)` |
