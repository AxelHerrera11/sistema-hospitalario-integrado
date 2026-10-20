# Trazabilidad de cobertura de requisitos (Semana 15)

## 1. Trazabilidad con los requisitos de datos

| Requisito (semana 2) | Prueba que lo cubre |
|---|---|
| RF-D-02 No solapamiento temporal | `test_two_active_stays_on_the_same_bed_are_rejected` |
| RF-D-04 Obligatoriedad de la identidad clínica | `test_stay_must_lie_inside_the_care_episode_period` |
| RF-D-06 Coherencia estado-vigencia | `test_operational_status_does_not_change_the_interval_derived_occupancy` |
| RF-D-07 Dominio de valores | `test_occupancy_counters_ignore_beds_out_of_service` |
| RNF-D-04 Trazabilidad de esquema | Todas: la prueba hace `migrate:fresh` del slice en cada caso |
| RNF-D-06 Evidencia reproducible | Toda la suite `tests/Database` |
