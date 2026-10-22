# Guion de la defensa — Admisión y camas (ASII-07)

1. **Problema** (1 min): dos pacientes asignados a la misma cama, o un paciente en dos camas, rompen la ocupación y la seguridad del paciente.
2. **Tesis** (1 min): la base garantiza una cama por paciente y un paciente por cama; el dominio rechaza lo que ya sabe que es inválido.
3. **Del UML al código** (2 min): UC-ADM-01, 02, 04 y 05 → `Admission` → `AdmitPatient` → `AdmissionRepository` → `POST /api/v1/admissions`.
4. **SOLID** (1 min): OCP en la elegibilidad de cama (demo de la Semana 2).
5. **Transacción** (2 min): episodio, estancia, cama y auditoría juntos; la prueba de rollback.
6. **Cliente-servidor** (1 min): el GET se reintenta y el POST no. Frontera: gestión de camas con ASII-06 y ASII-08.
7. **Caso práctico** (2 min): aislamiento por tuberculosis y la lista de espera, con su tabla de impacto por capa.
8. **Riesgos abiertos** (30 s): un solo escritor de `beds.status` y la lista de espera.

**Preguntas que preparo:**
- ¿Por qué no basta con revisar `beds.status` antes de guardar? Porque entre la lectura y el guardado otra admisión puede tomar la cama; el `UPDATE` condicionado y los índices lo resuelven.
- ¿Qué pasa si el servidor no responde al admitir? El cliente no repite el alta y pide verificar la cama.
