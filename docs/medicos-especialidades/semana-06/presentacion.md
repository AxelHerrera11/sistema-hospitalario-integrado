# Guion de la defensa — Médicos y especialidades (ASII-04)

1. **Problema** (1 min): si se ofrece una franja de un médico vencido, sin la especialidad o con una cita ya tomada, Citas agenda algo que no se puede atender.
2. **Tesis** (1 min): la disponibilidad que se ofrece es verdadera; el dominio rechaza lo inválido y la base resuelve la concurrencia.
3. **Del UML al código** (2 min): UC-MED-01, 02 y 05 → `Period` y `SpecialtyAssignmentPolicy` → casos de uso → `DoctorRepository` → `/api/v1`.
4. **SOLID** (1 min): ISP, seis puertos; la consulta no ve métodos de escritura.
5. **Transacción** (1 min): perfil, especialidad y auditoría juntos; la prueba de rollback.
6. **Cliente-servidor** (1 min): permisos `medicos.*`, `trace_id`, el GET se reintenta y el POST no. Frontera: se queda en el HIS por las FK y el trigger de `appointments`.
7. **Caso práctico** (2 min): ausencias con `DoctorAbsenceReader`, `DOCTOR_ON_LEAVE` y `409` si hay citas.
8. **Riesgos abiertos** (30 s): `doctor_schedules`, auditoría H8, permisos en RBAC y el rango de 14 días.

**Preguntas que preparo:**
- ¿Por qué no basta con validar en la aplicación? Porque dos asociaciones simultáneas pasan ambas la validación; la exclusión GiST rechaza la segunda.
- ¿Por qué el médico sin horario aparece? Porque ocultarlo hace creer que no existe; aparece con `SCHEDULE_NOT_CONFIGURED`.
- ¿Por qué no un microservicio? `appointments` tiene FK a `doctor_profiles` y un trigger que lee `doctor_specialties`; separar rompe esa garantía sin una carga que lo justifique.
