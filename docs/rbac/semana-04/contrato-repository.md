# Contrato del Repository y traducción a HTTP

## Interfaz `MembershipRepository` (Application)

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

## Traducción a HTTP en el controlador

| Resultado de `AssignRole` | Estado | Cuerpo |
|---|---|---|
| `Membership` creada | `201` | `user_id`, `hospital_id`, `role_code` |
| `RbacViolation` `payload_invalido` | `422` | `code` |
| `RbacViolation` `RBAC_DENIED` | `403` | `code`, `message`, `trace_id` (sin revelar el permiso que faltó) |
| `RbacViolation` `rol_ya_asignado` | `409` | `code` |
| Error del repositorio | `500` | `code: RBAC_PERSISTENCE_ERROR` |

Cada fila termina la petición: ningún error continúa al caso de uso ni a la base.
