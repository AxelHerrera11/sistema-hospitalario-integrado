# Plan SQA

## Objetivo

Prevenir y detectar regresiones del endpoint de consulta mientras se completa
su integración segura y persistente.

## Controles

| Tipo | Control | Responsable | Evidencia |
|---|---|---|---|
| Preventivo | Mantener tenant → JWT → permiso por ruta; mínimo privilegio. | API/RBAC | Ruta y matriz revisadas |
| Preventivo | Reutilizar servicio/repositorio; no exponer excepciones PDO. | Pacientes | Revisión y prueba HTTP |
| Detectivo | Ejecutar casos autorizado, 404, auth, permiso, tenant y persistencia. | QA/Pacientes | PHPUnit |
| Detectivo | Verificar contrato, mapping `local_patient_id` → `patient_id` y diff. | API/Pacientes | Aserciones y revisión |
| Detectivo | Ejecutar prueba PostgreSQL con aislamiento tenant antes de ampliar endpoints. | QA/DB | Pendiente |

## Métricas y seguimiento

Los valores no medidos se indican como **No medido**; no se estiman.

| Métrica | Valor actual | Meta |
|---|---|---|
| Pruebas HTTP aprobadas | 4/4; 21 aserciones; warnings `.env` | 100%, sin warnings |
| Endpoints Pacientes con middleware requerido | 1/1 ruta existente | 100% de rutas |
| Casos con PostgreSQL real | 0 | ≥1 caso de integración |
| Defectos abiertos por severidad | No medido | 0 altos antes de ampliar |
| Requisitos del slice cubiertos | 6 escenarios en prueba | 100% de escenarios acordados |

Seguimiento por cada cambio y antes de habilitar despliegue; revisión de riesgos
en cada incremento. Responsables propuestos: Pacientes, API/RBAC, QA y DB.
