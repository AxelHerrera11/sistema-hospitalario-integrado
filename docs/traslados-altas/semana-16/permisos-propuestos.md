> **Actualización:** este PR solicita la activación. Hasta su integración por RBAC (ASII-02), esta página sigue vigente.

# Permisos propuestos para traslados y altas (pendientes de RBAC)

| Permiso | Ruta prevista en la API v1 | Rol propuesto | Estado en `config/rbac.php` |
|---|---|---|---|
| `traslados.registrar` | `POST /api/v1/traslados` | `enfermeria` | `catalog` y `pending`; **no activo** |
| `altas.registrar` | `POST /api/v1/altas` | `medico` | `catalog` y `pending`; **no activo** |
| `traslados.ver` | `GET /api/v1/estancias/{id}/internacion` | `enfermeria` y `medico` | `catalog` y `pending`; **no activo** |

## Qué significa hoy

- Los nombres existen en el catálogo, así que las rutas pueden declararlos sin romper `PublishedContractsTest`.
- Como ningún rol los tiene en `rbac.roles`, `RequirePermission` responde **403** a cualquier usuario, incluido `superadmin`, como con cualquier permiso pendiente.
- Por eso, en la integración, las operaciones reales a través de Laravel quedan **denegadas** hasta la activación. El camino de éxito se prueba con la activación simulada **solo dentro de la prueba** (`config()->set` en PHPUnit), nunca en la configuración compartida.

## Acción pendiente

**Depende de:** RBAC. La activación equivalente de medicamentos está en `b73e3e8`.

Revisar la propuesta y, si se aprueba, mover cada permiso de `pending` a `roles` para el rol acordado, actualizando la fila de `docs/rbac/catalogo-modulos.md`. ASII-08 no activa los permisos por su cuenta.
