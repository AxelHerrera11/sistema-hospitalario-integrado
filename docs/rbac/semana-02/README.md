# Semana 02 — Módulo RBAC: diseño OCP (Abierto/Cerrado)

**Objetivo.** Preparar la vista de diseño del módulo RBAC en torno al **Principio Abierto/Cerrado (OCP)**: las reglas de autorización se pueden extender **sin modificar** el motor que las ejecuta. Incluye diagramas ANTES/DESPUÉS, requisitos con trazabilidad a las UCs de Semana 1 y un ejemplo ejecutable en PHP.

> Entrega de **Semanas 1-2** del backlog de RBAC. Todo lo que hoy no existe en `app/`, `database/` ni `routes/` está marcado como **Propuesto — se implementa desde Semana 3**. No se modificó el código del repositorio.

## 1. Estructura

```
docs/rbac/semana-02/
├── README.md              ← este archivo
├── requerimientos.md      ← RF/RNF + criterios de aceptación + trazabilidad
├── 01-antes.svg           ← ANTES: motor acoplado (hipotético de referencia)
├── 02-despues.svg         ← DESPUÉS: Authorizer dependiente de abstracciones
├── fuentes/               ← fuentes Mermaid (.mmd) de los diagramas
└── ejemplo/
    └── ocp-demo.php       ← ejemplo ejecutable del diseño DESPUÉS (80 líneas)
```

## 2. ANTES — diseño hipotético de referencia

Como el repositorio **no tiene verificación de roles/permisos** (no hay `can()`, `Gate` ni `permission:` en rutas; ver `requerimientos.md §1`), el diagrama ANTES es un **diseño hipotético de referencia**: un `AuthorizationService` acoplado que decide con `if/else` sobre `role_code`, con la denegación registrada dentro del mismo controlador.

- Rol nuevo o permiso nuevo → **editar** el mapa y los `if` (violación de OCP).
- Ver `01-antes.svg`.

## 3. DESPUÉS — diseño propuesto

`Authorizer` (cerrado a modificación) colabora con interfaces:

| Clase / interfaz | Responsabilidad | Dependencia |
|---|---|---|
| `Authorizer` | Autoriza solo si **todas** las reglas registradas permiten; sin reglas, niega (denegación por defecto); la primera regla que niega fija el motivo. **No cambia al agregar una regla.** | `AuthorizationRule`, `PermissionProvider`, `DenialRecorder` |
| `PermissionProvider` | Devuelve los permisos del rol. | interfaz |
| `AuthorizationRule` | Decisión unitaria: `decide(contexto)` devuelve `null` (permite) o el motivo de la negación. `RoleGrantsPermission` — permite si algún rol del usuario otorga el permiso; `HospitalScopeRule` — solo restringe (nunca concede): niega si el hospital de la operación ≠ el de la asignación del rol. | interfaz |
| `DenialRecorder` | Registra cada denegación con el `trace_id` que genera `Authorizer` y el motivo (`AuditLogger` la implementa — **externa**). Si el registro falla, `Authorizer` captura el error y la autorización sigue devolviendo `false` (falla segura). | Gobernanza |
| `InMemoryPermissions` | Implementación en memoria del catálogo rol → permisos (dato, no código). | `database/…role_code` (`…:30,41`) |

Regla nueva = **clase nueva** que implementa `AuthorizationRule` y se registra; rol/permiso nuevo = **dato** entregado por `PermissionProvider`. Ver `02-despues.svg`.

## 4. Supuestos de diseño (SD-RBAC-NN)

Todos documentados en `requerimientos.md §5` (SD-RBAC-01…08). Los relevantes para los diagramas:

- **SD-RBAC-03** — `spatie` con `teams=false` (`config/permission.php:31`) **no aísla por hospital**; el alcance por tenant se implementa como regla (`HospitalScopeRule`). *Pendiente de validación.*
- **SD-RBAC-05/06** — las reglas reciben un contexto (`roles`, `required`, hospital/membresía) y `Authorizer` genera el `trace_id` al denegar; `DenialRecorder` solo lo registra.
- **SD-RBAC-07** — la autorización siempre usa el hospital del `X-Tenant-ID`.
- **SD-RBAC-08** — las reglas se combinan con **AND** (todas deben permitir); sin reglas, denegación por defecto; la primera regla que niega define el motivo. No hay fuente en el repo que defina esta combinación. *Pendiente de validación.*

## 5. Cómo verificar el ejemplo

```powershell
php docs/rbac/semana-02/ejemplo/ocp-demo.php
```

Salida exacta (`trace_id` generado por `Authorizer`, secuencial por denegación):

```text
1) Sin reglas -> denegación por defecto:
    [denegación registrada] usuario=med permiso=prescripciones.crear operacion=prescribir hospital=H2 motivo=sin_reglas trace_id=trace-0001
    Medico -> prescripciones.crear               => DENEGADO
    [denegación registrada] usuario=tec permiso=muestras.recibir operacion=recibir_muestra hospital=H2 motivo=sin_reglas trace_id=trace-0002
    TecnicoLab H2 -> muestras.recibir            => DENEGADO
    [denegación registrada] usuario=tec permiso=muestras.recibir operacion=recibir_muestra hospital=H1 motivo=sin_reglas trace_id=trace-0003
    TecnicoLab H1 -> muestras.recibir            => DENEGADO
2) Regla RoleGrantsPermission (permite si algún rol del usuario otorga el permiso):
    Medico -> prescripciones.crear               => PERMITIDO
    TecnicoLab H2 -> muestras.recibir            => PERMITIDO
    TecnicoLab H1 -> muestras.recibir [hueco: falta restringir] => PERMITIDO
3) Regla NUEVA HospitalScopeRule (solo restringe; Authorizer sin cambios):
    [denegación registrada] usuario=tec permiso=muestras.recibir operacion=recibir_muestra hospital=H1 motivo=fuera_de_hospital trace_id=trace-0004
    TecnicoLab H1 -> muestras.recibir            => DENEGADO
    TecnicoLab H2 -> muestras.recibir            => PERMITIDO
    Medico -> prescripciones.crear (sin regresión) => PERMITIDO
4) DenialRecorder que falla (falla segura; la autorización sigue devolviendo false):
    [bitácora no disponible] trace_id=trace-0005 error=bitacora_caida
    TecnicoLab H1 -> muestras.recibir            => DENEGADO
```

**Lectura:** el `Authorizer` combina las reglas con **AND** (todas deben permitir) y sin reglas niega todo (denegación por defecto). En el escenario 2, `RoleGrantsPermission` sola deja pasar a `TecnicoLab` en `H1` (marcado `[hueco: falta restringir]`); en el escenario 3, agregar la clase `HospitalScopeRule` (sin tocar `Authorizer`) restringe ese acceso (`DENEGADO`, motivo `fuera_de_hospital`), mantiene `PERMITIDO` en `H2` y no provoca regresiones. Esa es la extensión OCP en ejecución. En el escenario 4, `FailingDenialRecorder` lanza una excepción al registrar: `Authorizer` la captura y la autorización sigue devolviendo `false` (`DENEGADO`), como exigen RF-RBAC-05 y RNF-RBAC-02.

## 6. Alcance de próximas semanas

- **Semana 3:** implementación real en `app/` (middleware `permission:`, endpoints RBAC, seeder de roles/permisos, `GET /auth/me/permissions`).
- **Semana 4:** auditoría central (Gobernanza) como `DenialRecorder` real; formato definitivo de `trace_id` (SD-RBAC-06).

## 7. Consistencia de nombres

`Authorizer`, `AuthorizationRule`, `RoleGrantsPermission`, `HospitalScopeRule`, `PermissionProvider`, `InMemoryPermissions` y `DenialRecorder` se usan con el mismo nombre y la misma firma en `02-despues.svg`, `ejemplo/ocp-demo.php` y `requerimientos.md` (R6 de la tarea). En la fuente y en el ejemplo, `Authorizer` recibe un `PermissionProvider` por constructor y las reglas toman el permiso requerido desde el contexto; `HospitalScopeRule` agrega el alcance por hospital.