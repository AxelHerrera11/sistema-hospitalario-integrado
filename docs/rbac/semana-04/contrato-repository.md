# Contrato del Repository y traducción a HTTP

## Interfaz `MembershipRepository` (Application, propuesta)

```php
interface MembershipRepository
{
    /** @return string[] roles del usuario en el hospital de la solicitud */
    public function rolesIn(string $userId, string $hospitalId): array;

    /** Guarda la membresía y su evento de auditoría de forma atómica; si falla, no guarda ninguno. */
    public function saveWithAudit(Membership $membership, string $actorId): void;
}
```

Los dos adaptadores deben pasar las mismas pruebas de la Semana 3 (camino feliz, reglas de dominio y error de persistencia). Así se comprueba que son intercambiables.

## Implementado frente a propuesto

`RoleResolver` y `MembershipRepository` **no son lo mismo**, y conviene no confundirlos:

- **`RoleResolver` (implementado):** puerto de *lectura* con una sola responsabilidad: dado un usuario y un hospital, devolver sus roles (`rolesIn`). Lo implementan los dos adaptadores de esta entrega (`PdoRbacStore` e `InMemoryRbacStore`). Es la interfaz que permite separar la resolución de roles de la persistencia concreta.
- **`MembershipRepository` (propuesta):** repositorio del *agregado* `Membership` que unifica lectura y escritura (`rolesIn` + `saveWithAudit`) con auditoría atómica. Saca de `AssignRole` la dependencia de la clase concreta `PdoRbacStore`.

| Elemento | Capa | Estado | Evidencia |
|---|---|---|---|
| `PermissionProvider` (`permissionsFor`) | Domain | Implementado | `micro-his/rbac/src/Domain/PermissionProvider.php` |
| `DenialRecorder` (`record`) | Domain | Implementado | `micro-his/rbac/src/Domain/DenialRecorder.php` |
| `RoleResolver` (`rolesIn`) | Domain | Implementado | `micro-his/rbac/src/Domain/RoleResolver.php` |
| `PdoRbacStore` | Persistence | Implementado | `micro-his/rbac/src/Persistence/PdoRbacStore.php` |
| `InMemoryRbacStore` | Persistence | Implementado | `micro-his/rbac/src/Persistence/InMemoryRbacStore.php` |
| `MembershipRepository` | Application | Propuesta | este documento |
| `RoleAssignmentController` y `JsonView` | Controller / View | Propuesta | `README.md` |

Precisión sobre el estado actual: `RoleResolver` ya está definido e implementado por ambos adaptadores, pero **todavía no lo consume el motor**; `AssignRole` sigue llamando a `PdoRbacStore::rolesIn()` y a `PdoRbacStore::assignWithAudit()` sobre la clase concreta. Sustituir esa dependencia por `MembershipRepository` (o por un `RoleResolver` + un puerto de escritura) es la parte **propuesta** de la Semana 4, porque cambia la firma del caso de uso y excede el alcance de esta entrega. En resumen: la lectura desacoplada (`RoleResolver`) está implementada; el repositorio completo de lectura y escritura (`MembershipRepository`) sigue siendo una propuesta.

## Traducción a HTTP en el controlador

| Resultado de `AssignRole` | Estado | Cuerpo |
|---|---|---|
| `Membership` creada | `201` | `user_id`, `hospital_id`, `role_code` |
| `RbacViolation` `payload_invalido` | `422` | `code` |
| `RbacViolation` `RBAC_DENIED` | `403` | `code`, `message`, `trace_id` (sin revelar el permiso que faltó) |
| `RbacViolation` `rol_ya_asignado` | `409` | `code` |
| Error del repositorio | `500` | `code: RBAC_PERSISTENCE_ERROR` |

Cada fila termina la petición: ningún error continúa al caso de uso ni a la base.
