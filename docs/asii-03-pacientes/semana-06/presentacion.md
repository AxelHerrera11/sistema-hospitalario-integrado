# Presentación — ASII-03 Pacientes

## Diapositiva 1 — Problema y objetivo
- Registrar pacientes del hospital.
- Consultar pacientes por código.
- Actualizar datos permitidos de forma segura.
- Evitar duplicados y modificaciones de identidad.

## Diapositiva 2 — Requisitos principales
- Registro con datos válidos.
- Código único.
- Fecha de nacimiento no futura.
- Identificadores estables.
- Persistencia segura.
- Control de acceso por permisos.

## Diapositiva 3 — Arquitectura
Presentation -> Application -> Domain
                     |
                     v
               PatientRepository
                     |
             PDO / InMemory

La lógica de negocio no depende directamente de SQL ni del framework HTTP.

## Diapositiva 4 — Repository y pruebas
- PatientRepository define el contrato de persistencia.
- PdoPatientRepository implementa persistencia real.
- InMemoryPatientRepository permite pruebas sin BD.
- Se cubren camino feliz, duplicados, fecha futura y fallo de persistencia.

## Diapositiva 5 — API REST
- POST /api/v1/patients
- GET /api/v1/patients/{code}
- PATCH /api/v1/patients/{code}

El API expone patient_id como identificador estable del paciente.

## Diapositiva 6 — Seguridad e integración
Cadena esperada:
tenant -> jwt.auth -> rbac -> Pacientes

Permisos:
- pacientes.ver
- pacientes.registrar
- pacientes.editar

Pacientes no implementa su propio sistema de usuarios ni roles.

## Diapositiva 7 — Decisiones defendidas
- Separar dominio de persistencia.
- Usar Repository para desacoplar almacenamiento.
- Mantener código y global_id inmutables.
- No convertir Pacientes en microservicio físico todavía.
- Reutilizar autenticación, tenant y RBAC comunes del HIS.

## Diapositiva 8 — Cambio práctico
El diseño inicial trataba los permisos únicamente como parte del contrato.

Después de la integración del RBAC del equipo, el módulo se alineó con los permisos reales publicados en el sistema.

Resultado: el contrato del módulo puede integrarse sin implementar autorización duplicada.
