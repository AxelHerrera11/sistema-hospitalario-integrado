# Evidencia verificable — Admisión hospitalaria y asignación de cama

## Checklist de revisión

- [x] Los RF describen acciones observables y cada uno se traza a un UC de la Semana 1.
- [x] Los RNF son medibles: seguridad, concurrencia, consistencia, rendimiento, usabilidad, disponibilidad del dato, privacidad y mantenibilidad.
- [x] Cada criterio de aceptación tiene un escenario *Dado / Cuando / Entonces* que se puede probar.
- [x] OCP se aplica al flujo de búsqueda y asignación de cama con un diseño antes y después.
- [x] Las responsabilidades y dependencias están justificadas, incluida la relación con la exclusión GiST.
- [x] Una demo ejecutable comprueba que una regla nueva no modifica las clases existentes.

## Matriz RF/RNF hacia el diseño

| Requerimiento | Componente o mecanismo que lo cubre |
|---|---|
| RF-ADM-01 | `AdmissionController::store` → `AdmissionService::admitir` → `AdmissionRepository` |
| RF-ADM-02 | `PatientDirectory` + `AdmissionRepository::hospitalizacionAbierta` |
| RF-ADM-03 | Trigger `care_assignments_specialty_trigger` → `422 DOCTOR_SPECIALTY_INVALID` |
| RF-ADM-04 | `BedEligibilityChecker::eligibleCodes` sobre `BedRepository::listarDisponibles` |
| RF-ADM-05 | `BedAssignmentService::asignarCama` (bloqueo, revalidación, `bed_stays`, `beds`, auditoría) |
| RF-ADM-06 | `BedEligibilityChecker::rejection` tras el bloqueo + exclusión `bed_stays_bed_active_period_exclusion` |
| RF-ADM-07 | Lista vacía de `eligibleCodes`; episodio abierto sin estancia |
| RF-ADM-08 | Consulta de episodio + estancia abierta |
| RF-ADM-09 | `AuditLogger` en la misma transacción `hospital` |
| RNF-ADM-01 | Middlewares JWT, tenant y RBAC |
| RNF-ADM-02 | `FOR UPDATE` + exclusión GiST + índice `bed_stays_open_care_episode_unique` |
| RNF-ADM-03 | Transacción única en `BedAssignmentService` |
| RNF-ADM-04 | Reglas evaluadas en memoria sobre las camas ya filtradas por índice (`beds_ward_id_index`) |
| RNF-ADM-05 | Respuesta `409` con código de motivo + refresco de `BedPicker` |
| RNF-ADM-06 | Deducción «episodio `inpatient` abierto sin estancia» |
| RNF-ADM-07 | `AuditLogger` con metadatos mínimos |
| RNF-ADM-08 | Interfaz `BedEligibilityRule` y registro ordenado por *tag* |

## Validaciones ejecutadas

```bash
# comando:
php -l docs/admision-camas/semana-02/evidencia/demo-ocp.php
# resultado:
No syntax errors detected in docs/admision-camas/semana-02/evidencia/demo-ocp.php

# comando (PHP 8.4.21, CLI):
php docs/admision-camas/semana-02/evidencia/demo-ocp.php
# resultado (código de salida 0):
== Diseño después reproduce el comportamiento del diseño antes ==
PASS  cama libre en sala activa → elegible
PASS  cama ocupada → BED_NOT_AVAILABLE
PASS  sala inactiva → BED_NOT_AVAILABLE
PASS  tipo distinto al solicitado → BED_TYPE_MISMATCH
PASS  episodio con cama abierta → EPISODE_ALREADY_HAS_BED
PASS  episodio cerrado → EPISODE_NOT_OPEN
PASS  la búsqueda (UC-ADM-03) usa las mismas reglas

== Extensión OCP: PediatricWardAgeRule ==
PASS  sin la regla nueva, un adulto podría ocupar una cama pediátrica
PASS  con la regla registrada, adulto en sala pediátrica → PEDIATRIC_WARD_AGE
PASS  un niño de 8 años sí puede ocupar la cama pediátrica
PASS  la búsqueda del adulto ya no ofrece la cama pediátrica
PASS  las reglas anteriores siguen igual (cama ocupada → BED_NOT_AVAILABLE)
PASS  BedEligibilityChecker no menciona ninguna regla concreta

OK: todas las aserciones pasaron
```

Los diagramas `fuentes/01-diseno-antes.mmd` y `fuentes/02-diseno-despues.mmd` se renderizaron sin errores con `@mermaid-js/mermaid-cli`.

## Evidencia para la defensa

**¿Qué cambia al aplicar OCP?** Antes, las condiciones para asignar una cama eran una cadena de `if` dentro del servicio, repetida en la búsqueda. Después, cada condición es una regla que implementa `BedEligibilityRule` y un solo verificador las recorre, tanto al buscar como al asignar. Una regla nueva es una clase nueva más su registro.

**¿Cómo lo demuestro?** Con la demo: sin `PediatricWardAgeRule`, un adulto puede ocupar la cama pediátrica; al registrarla, ya no la ve en la búsqueda ni puede recibirla, un niño sí, y `BedEligibilityChecker` no cambió (su código no nombra ninguna regla).

**Si las reglas ya validan la cama, ¿para qué la exclusión GiST?** Porque entre la búsqueda y la confirmación otro usuario puede tomar la cama. Las reglas se vuelven a evaluar después del `FOR UPDATE`, y la exclusión de `bed_stays` garantiza en la base que nunca haya dos estancias solapadas aunque falle la aplicación.

**¿Por qué las reglas tienen orden?** Para que el mensaje sea el más útil: si el episodio está cerrado, no tiene sentido decir que la cama es de otro tipo.
