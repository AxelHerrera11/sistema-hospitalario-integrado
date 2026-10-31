# Matriz decisión → evidencia

| # | Decisión | Requisito | Alternativa descartada | Evidencia verificable |
|---|---|---|---|---|
| D-01 | Consultar exige permiso RBAC **y** vínculo asistencial | RF-EXP-04, RNF-EXP-01 | Solo permiso por rol | C3, C4, N-02 (enfermería con permiso pero sin vínculo → 403) |
| D-02 | Reutilizar `Authorize` de RBAC por un puerto (`AccessAuthorizer`) | RF-EXP-04 | Leer roles desde el expediente | `Integration/RbacAccessAuthorizer.php`; N-03/N-04 con la matriz real de `config/rbac.php` |
| D-03 | Auditoría Security: sin evento no hay datos | RF-EXP-05, RNF-EXP-01 | Auditoría «best effort» | C6, N-12, P-07 (filas reales en `audit_log` de Gobernanza) |
| D-04 | Apertura idempotente con UNIQUE + relectura del ganador | RF-EXP-01, RNF-EXP-02, RNF-EXP-05 | 409 si ya existe | A3, A6, P-03, N-09 |
| D-05 | INSERT y auditoría en la misma transacción local | RNF-EXP-02 | Auditar después del COMMIT | A5, P-04 |
| D-06 | Repository en Application, PDO e InMemory | RNF-EXP-06 | DAO dentro del controlador | R-02, R-03, R-05; caja negra `[pdo]` = `[memoria]` |
| D-07 | Una base por hospital vía `HospitalRegistry` | RNF-EXP-03 | Filtrar por columna `hospital_id` | P-08, P-09 (dos bases, mismo código de paciente) |
| D-08 | Paciente inexistente = «sin vínculo» en la consulta | RNF-EXP-01 | 404 «no existe» | C3, N-07 |
| D-09 | No dividir en microservicio | RNF-EXP-05 | Microservicio «Expediente» | `semana-05/evidencias/medicion-en-proceso.txt` |
| D-10 | Separar identidad CENTRAL y referencia local (cambio práctico) | RF-EXP-04, RNF-EXP-03 | Un único id para todo | C8, `tests/Feature/ExpedienteMedico` (ver [`cambio-practico.md`](cambio-practico.md)) |
| D-11 | No publicar la apertura en Laravel hasta que su permiso exista | RF-EXP-04 | Ruta con permiso inventado | `PublishedContractsTest` en verde; prueba Feature `…not_published…` (405) |
