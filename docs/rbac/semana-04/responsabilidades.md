# Responsabilidades por capa y objetos reutilizables

## Capas

| Capa | Clase | Responsabilidad | No debe hacer | Estado |
|---|---|---|---|---|
| Presentation (Controller) | `RoleAssignmentController` | Tomar `X-User-ID`, `X-Tenant-ID`, el `{id}` de la ruta y `role_code`; llamar a `AssignRole`; traducir el resultado o `RbacViolation` a un estado HTTP | Ejecutar SQL, decidir permisos o validar roles | Propuesto (reemplaza a `RbacHttpHandler`) |
| Presentation (View) | `JsonView` | Escribir el estado HTTP y el cuerpo JSON | Decidir el estado o leer la petición | Propuesto |
| Application | `AssignRole` | Validar el payload, autorizar `rbac.asignar`, rechazar el rol repetido y pedir guardar | Conocer PDO, tablas o HTTP | Existe; cambia su dependencia a `MembershipRepository` |
| Application (puerto) | `MembershipRepository` | Contrato de lectura y guardado de membresías | Contener SQL | Propuesto |
| Domain | `Authorizer`, `RoleGrantsPermission`, `AssignerPrivilegeRule`, `Membership`, `RbacViolation` | Decidir con reglas en AND, generar el `trace_id` y mantener la falla segura | Leer la petición o la base | Existe, sin cambios |
| Persistence | `PdoMembershipRepository` | Implementar el puerto con PDO y sentencias preparadas; membresía y auditoría en una transacción | Decidir si una asignación es válida | Propuesto (sale de `PdoRbacStore`) |
| Persistence | `InMemoryMembershipRepository` | Implementar el puerto con arreglos, para pruebas sin base de datos | Simular reglas de negocio | Propuesto |
| Persistence | `PdoRbacStore` | Queda solo como `PermissionProvider` y `DenialRecorder` | Guardar membresías | Existe; se reduce |

## Objetos reutilizables

| Objeto | Quién puede reutilizarlo |
|---|---|
| `Authorizer` + `AuthorizationRule` | Cualquier ruta protegida del HIS (prescripciones, muestras, resultados): se agrega una regla con `addRule()` sin modificar el motor. |
| `MembershipRepository` | Cualquier módulo que necesite los roles de un usuario en su hospital. |
| `DenialRecorder` | Gobernanza y auditoría puede aportar la implementación real. |
| `RbacViolation` + tabla de traducción | Otros controladores, para responder errores con el mismo formato. |
| `JsonView` | Todos los controladores del micro-HIS. |
