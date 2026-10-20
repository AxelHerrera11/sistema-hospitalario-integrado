# Matriz decisión — evidencia

| Decisión | Requisito relacionado | Evidencia |
|---|---|---|
| Separar Domain de Persistence | Mantener lógica independiente de infraestructura | Patient y PdoPatientRepository están en capas distintas |
| Utilizar PatientRepository | Permitir sustituir persistencia | Implementaciones PDO e InMemory |
| Validar fecha de nacimiento | No aceptar fechas futuras | Regla de dominio y prueba automatizada |
| Evitar código duplicado | Identificación local única | Validación de aplicación y restricción de BD |
| Mantener código inmutable | Evitar cambios de identidad accidental | Update no permite modificar code |
| Mantener global_id inmutable | Conservar vínculo de identidad global | Update no permite modificar global_id |
| Exponer patient_id | Facilitar integración con otros módulos | Contrato OpenAPI de Semana 5 |
| Usar API REST versionada | Establecer contrato cliente-servidor | Rutas /api/v1/patients |
| Reutilizar RBAC común | Evitar autorización duplicada | pacientes.ver, pacientes.registrar y pacientes.editar |
| No separar microservicio físico | Reducir complejidad de consistencia | Plan de integración de Semana 5 |
