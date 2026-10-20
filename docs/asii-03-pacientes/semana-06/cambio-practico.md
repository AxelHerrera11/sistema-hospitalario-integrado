# Cambio práctico defendible

## Situación inicial
Durante el diseño del contrato API de Pacientes se definieron permisos necesarios para registrar, consultar y editar pacientes.

En ese momento el módulo todavía no estaba conectado al RBAC definitivo del sistema.

## Cambio realizado en el diseño de integración
Después de integrarse el módulo RBAC del proyecto, se confirmó que el catálogo común contiene los permisos:

- pacientes.ver
- pacientes.registrar
- pacientes.editar

Por lo tanto Pacientes no necesita implementar su propio sistema de roles o permisos.

## Flujo integrado
La operación protegida debe atravesar:

1. validación del tenant;
2. autenticación JWT;
3. autorización RBAC;
4. ejecución del controlador de Pacientes.

Representación:

tenant -> jwt.auth -> rbac:permiso -> PatientController

## Ejemplo
Para registrar un paciente, la ruta POST /api/v1/patients requiere pacientes.registrar.

Si el usuario no está autenticado, la capa transversal rechaza la solicitud antes de ejecutar Pacientes.

Si está autenticado pero no posee el permiso, RBAC devuelve la denegación correspondiente.

PatientController solamente ejecuta el caso de uso cuando las validaciones transversales ya fueron superadas.

## Beneficio
La modificación elimina autorización duplicada dentro de Pacientes y permite que todos los módulos utilicen el mismo mecanismo de seguridad.

## Alcance
Este cambio corresponde a la integración arquitectónica. Semana 6 no agrega una implementación paralela de autenticación ni modifica el RBAC del equipo.
