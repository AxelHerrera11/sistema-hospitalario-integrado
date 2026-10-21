# Integración de Pacientes con RBAC

Los permisos se declaran en `config/rbac.php` y se conceden explícitamente:

| Rol | pacientes.ver | pacientes.registrar | pacientes.editar |
| --- | --- | --- | --- |
| recepcion | Sí | Sí | Sí |
| medico | Sí | No | No |
| enfermeria | Sí | No | No |
| superadmin | Sí | Sí | Sí |
| admin_hospital | No | No | No |

Se conserva el alcance administrativo de `admin_hospital`. No hay herencia por
nombre de rol ni comodines. Las concesiones requieren membresía en el hospital activo.

El módulo Pacientes debe aplicar `tenant`, `jwt.auth` y después el middleware:

| Operación | Middleware |
| --- | --- |
| GET /api/v1/patients/{code} | rbac:pacientes.ver |
| POST /api/v1/patients | rbac:pacientes.registrar |
| PATCH /api/v1/patients/{code} | rbac:pacientes.editar |

La identidad y el hospital deben proceder de la autenticación y el contexto tenant
del servidor. El encabezado `X-Tenant-ID` debe coincidir con ese contexto.
Pacientes debe mantener sus consultas y modificaciones aisladas por hospital;
RBAC autoriza la operación, no selecciona el expediente.

Esta entrega modifica el catálogo, la matriz y sus pruebas. El propietario de
Pacientes conecta estos permisos a sus rutas; aquí no se añaden controladores
ni rutas de producción de ese módulo.
