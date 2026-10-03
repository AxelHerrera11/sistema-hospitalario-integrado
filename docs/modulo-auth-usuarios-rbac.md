# Módulo de autenticación, usuarios y RBAC

## Alcance de este incremento

Este primer incremento del Área 1 agrega el backend para listar los usuarios
del hospital actual con sus roles. No incluye interfaz Vue, edición,
desactivación ni administración de roles y permisos.

La autenticación existente mantiene login, usuario actual, registro por Admin,
refresh y logout. `User` usa `BelongsToTenant`, por lo que sus consultas quedan
limitadas automáticamente al hospital resuelto desde `X-Tenant-ID`.
Login y registro normalizan el correo a minúsculas para conservar su unicidad
por hospital independientemente de cómo lo escriba el cliente.

## Listado de usuarios

```http
GET /api/v1/users?q=maria&per_page=15&page=1
Authorization: Bearer <jwt>
X-Tenant-ID: <uuid-del-hospital>
```

La ruta requiere los middlewares `tenant` y `auth.jwt`, además del permiso
`usuarios.ver`. En la matriz actual de `RoleSeeder`, solo el rol Admin recibe
ese permiso.

### Parámetros

| Parámetro | Requerido | Validación | Predeterminado |
|---|---|---|---|
| `q` | No | Texto de hasta 100 caracteres | Sin búsqueda |
| `per_page` | No | Entero entre 1 y 50 | 15 |
| `page` | No | Entero mayor o igual a 1 | 1 |

`q` busca por coincidencia parcial en `name` y `email` mediante `whereSearch`,
compatible con las reglas de búsqueda de PostgreSQL. Los resultados se ordenan
por `name` y, como desempate estable, por `id`.

### Respuesta 200

El endpoint usa el formato de paginación nativo de Laravel. Cada elemento de
`data` se transforma explícitamente para no serializar atributos internos.

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 15,
      "name": "María López",
      "email": "maria@hospital.local",
      "roles": [
        {
          "id": 3,
          "name": "Enfermera"
        }
      ]
    }
  ],
  "per_page": 15,
  "last_page": 1,
  "total": 1
}
```

Nunca se devuelven `password`, `remember_token`, tokens JWT, datos del pivote de
roles, `guard_name` ni `tenant_id`.

### Errores

| Estado | Motivo |
|---:|---|
| 400 | Falta `X-Tenant-ID` o no es un UUID válido. |
| 401 | JWT ausente, inválido, expirado o asociado a un usuario inexistente. |
| 403 | El JWT pertenece a otro hospital o falta `usuarios.ver`. |
| 404 | El hospital indicado no existe. |
| 422 | `q`, `per_page` o `page` no cumplen las validaciones. |

## Aislamiento y JWT

`TenantMiddleware` valida la cabecera y registra el hospital actual antes de la
autenticación. `JwtAuth` valida la firma y vigencia del JWT, compara su claim
`tenant_id` con el hospital de la petición y solo después carga al usuario. De
esta forma, el global scope de `User` no convierte silenciosamente un intento
entre hospitales en una consulta sin aislamiento.

El refresh sigue el mismo orden: valida el token en flujo de renovación,
compara el hospital, comprueba que el usuario exista dentro del scope y emite
un único token nuevo que conserva el claim `tenant_id`.

Fuera de una petición HTTP no hay un hospital enlazado en el contenedor. Por
eso los factories y seeders continúan indicando `tenant_id` explícitamente,
como requiere `BelongsToTenant` para procesos de consola.

## Pendiente

- Pantalla Vue y navegación para gestión de usuarios.
- Edición y desactivación de usuarios.
- Administración de asignaciones de roles y permisos.
- Consulta de auditoría.

Estos puntos requieren incrementos y criterios de aceptación separados.
