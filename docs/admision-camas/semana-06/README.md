# Admisión hospitalaria y asignación de cama — Semana 6: primera evaluación parcial

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-07 · **Entrega:** defensa teórica y caso práctico arquitectónico.

## Tesis arquitectónica

En el flujo «admisión de paciente y asignación de una cama disponible», **ni dos pacientes en la misma cama ni dos camas por episodio, y eso lo garantiza la base, no solo la aplicación**. El dominio (`Admission`) rechaza lo que ya sabe que es inválido: sin motivo, paciente ya admitido, cama no disponible o de otro tipo. La carrera entre dos admisiones simultáneas la resuelven los índices únicos de estancias abiertas (en PostgreSQL, la exclusión GiST de la migración) y el `UPDATE … WHERE status = 'available'`, dentro de una sola transacción que abre el episodio, la estancia, ocupa la cama y deja la auditoría.

## Trazabilidad de punta a punta

```mermaid
flowchart LR
  AD([Admisión]) --> UC1[UC-ADM-01, 02, 04 y 05<br/>Admitir y asignar cama]
  AD --> UC3[UC-ADM-03<br/>Buscar cama disponible]
  UC1 --> RF1[RF: paciente existente · no admitido<br/>cama disponible y del tipo · motivo]
  UC3 --> RF3[RF: camas disponibles por tipo]
  RF1 --> DOM[Domain: Admission]
  DOM --> APP[AdmitPatient]
  RF3 --> APP2[FindAvailableBeds]
  APP & APP2 --> REP{{AdmissionRepository<br/>PDO · InMemory}}
  REP --> TX[(care_episodes · bed_stays · beds<br/>local_access_audits)]
  APP --> EP1[POST /api/v1/admissions]
  APP2 --> EP2[GET /api/v1/beds/available]
```

| Semana | Qué aportó | Evidencia |
|---|---|---|
| 1 | Actores, UC-ADM-01..09, actividad y secuencia sobre las migraciones 000200 y 000300 | `docs/admision-camas/semana-01/` (PR #41, integrado) |
| 2 | RF/RNF y OCP para las reglas de elegibilidad de cama | `docs/admision-camas/semana-02/` (PR #46, integrado) |
| 3 | Micro-HIS por capas con la transacción completa y pruebas | `micro-his/admision-camas/` (PR #201) |
| 4 | MVC (tablero de camas) + Repository PDO e InMemory | Reentrega de la Semana 4 (diseño del PR #57) |
| 5 | API v1, `AdmissionDeskClient` que no reintenta el alta, OpenAPI y frontera | Reentrega de la Semana 5 (diseño del PR #61) |
