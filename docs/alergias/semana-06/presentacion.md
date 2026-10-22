# Guion de la defensa — Alergias (ASII-12)

1. **Problema** (1 min): una prescripción firmada sin ver una alergia grave es un evento adverso evitable.
2. **Tesis** (1 min): nunca «sin alergias» cuando no se sabe; cuatro niveles y falla segura en servidor y cliente.
3. **Del UML al código** (2 min): UC-ALG-01 y 05 → `Allergy` y `AllergyAlert` → casos de uso → `AllergyRepository` → `/api/v1`.
4. **SOLID** (1 min): OCP en las reglas del banner (demo de la Semana 2).
5. **Datos reales** (1 min): columnas, `CHECK` e índice único parcial de la migración 000401; `local_patients` solo de lectura.
6. **Cliente-servidor** (1 min): `AllergyCheckClient`, un reintento seguro y `UNAVAILABLE`. Decisión: no separarlo como microservicio, porque está en el camino clínico síncrono de Prescripciones.
7. **Caso práctico** (2 min): reactividad cruzada penicilina–cefalosporinas, con su tabla de impacto por capa.
8. **Riesgos abiertos** (30 s): códigos de alérgeno con ASII-14 y el uso de `unavailable` en ASII-15.

**Preguntas que preparo:**
- ¿Por qué el duplicado no se valida solo en PHP? Porque dos altas simultáneas pasarían la validación; el índice único decide.
- ¿Qué ve el médico si la base cae? «Alergias no disponibles: verificar con el paciente antes de prescribir».
