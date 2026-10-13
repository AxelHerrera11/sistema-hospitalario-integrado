# RF/RNF y criterios de aceptación — Admisión hospitalaria y asignación de cama

Los requerimientos se derivan de los casos de uso y flujos de la [Semana 1](../semana-01/README.md). Cada RF indica el caso de uso que lo origina para mantener la trazabilidad.

## Requerimientos funcionales

| Código | UC | Requerimiento funcional | Criterio de aceptación |
|---|---|---|---|
| RF-ADM-01 | UC-ADM-01 | El sistema debe registrar la admisión como un episodio `inpatient` abierto, con motivo, servicio, fecha y hora de ingreso y médico responsable. | **Dado** personal de admisión con permiso `admisiones.registrar`, **cuando** envía datos válidos de un paciente activo, **entonces** se crean `care_episodes` (`type = inpatient`, `status = open`) y `care_assignments` (`source = episode`) y se responde `201` con `bed: null`. |
| RF-ADM-02 | UC-ADM-02 | El sistema debe verificar que el paciente exista, esté activo y no tenga otra hospitalización abierta. | **Dado** un paciente con un episodio `inpatient` abierto, **cuando** se intenta admitirlo otra vez, **entonces** se responde `409 ADMISSION_ALREADY_OPEN` con el episodio vigente; si no existe, `404`; si está inactivo o fallecido, `409 PATIENT_INACTIVE`. |
| RF-ADM-03 | UC-ADM-01 | El sistema debe validar que el médico responsable tenga activa la especialidad indicada. | **Dado** un médico sin esa especialidad activa en `doctor_specialties`, **cuando** se registra la admisión, **entonces** se responde `422 DOCTOR_SPECIALTY_INVALID` y no se crea ningún episodio. |
| RF-ADM-04 | UC-ADM-03 | El sistema debe listar las camas elegibles por sala y tipo, aplicando las mismas reglas que la asignación. | **Dado** un episodio `inpatient` abierto, **cuando** se buscan camas de la sala «MED-A», **entonces** solo aparecen camas `available` de salas `active`, del tipo pedido y que cumplan las reglas registradas (por ejemplo, edad en sala pediátrica). |
| RF-ADM-05 | UC-ADM-04 | El sistema debe asignar una cama al episodio de forma atómica. | **Dado** una cama elegible, **cuando** se confirma la asignación, **entonces** se crea una `bed_stays` abierta, la cama pasa a `occupied` y se audita, todo en una transacción, y se responde `201` con sala, cama y hora de inicio. |
| RF-ADM-06 | UC-ADM-05 | El sistema debe rechazar la asignación cuando la cama dejó de ser elegible o hubo otra asignación simultánea. | **Dado** que dos usuarios confirman la misma cama a la vez, **cuando** ambas transacciones terminan, **entonces** una responde `201` y la otra `409 BED_ALREADY_ASSIGNED`. Si la cama se ocupó o la sala se inactivó antes del bloqueo, `409 BED_NOT_AVAILABLE`; si el episodio ya tiene cama, `409 EPISODE_ALREADY_HAS_BED`. |
| RF-ADM-07 | UC-ADM-09 | Cuando no haya camas elegibles, la admisión debe quedar en espera. | **Dado** que la búsqueda no devuelve camas, **cuando** termina, **entonces** se responde `200` con lista vacía, el episodio sigue abierto sin estancia y la consulta muestra «En espera de cama» con el tiempo de espera. |
| RF-ADM-08 | UC-ADM-08 | El sistema debe mostrar el detalle de la admisión con su sala y cama o su estado de espera. | **Dado** un usuario con `admisiones.ver`, **cuando** abre una admisión, **entonces** ve episodio, médico responsable, sala, cama y fecha de asignación, o «En espera de cama». |
| RF-ADM-09 | UC-ADM-07 | El sistema debe auditar la admisión, la asignación de cama y las consultas. | **Dado** una admisión o asignación exitosa, **cuando** termina la transacción, **entonces** existe el evento correspondiente en `local_access_audits` con usuario, paciente, episodio, sala y cama. |

## Requerimientos no funcionales

| Código | Categoría | Requerimiento no funcional | Criterio de aceptación (medible) |
|---|---|---|---|
| RNF-ADM-01 | Seguridad | Acceso por permiso RBAC y por tenant. | El 100 % de los intentos sin `admisiones.*` o `camas.asignar`, o con un `X-Tenant-ID` ajeno, reciben `401`/`403` y quedan trazados. |
| RNF-ADM-02 | Concurrencia | Una cama nunca se asigna a dos pacientes al mismo tiempo. | Con 20 asignaciones simultáneas de la misma cama, exactamente 1 termina en `201`; las demás reciben `409` y no hay dos `bed_stays` solapadas (exclusión GiST). |
| RNF-ADM-03 | Consistencia | Estancia y estado de la cama cambian juntos. | En la prueba de falla inducida (auditoría caída), no queda ninguna cama `occupied` sin estancia abierta ni estancia abierta con cama `available`. |
| RNF-ADM-04 | Rendimiento | La búsqueda de camas es rápida. | `GET /beds/available` responde en ≤ 500 ms (p95) con 500 camas y 10 reglas registradas. |
| RNF-ADM-05 | Usabilidad | El usuario se entera de inmediato cuando pierde una cama. | Ante un `409`, la interfaz muestra el motivo y refresca la lista en ≤ 2 s sin perder los datos de la admisión. |
| RNF-ADM-06 | Disponibilidad del dato | La lista de espera no se pierde. | Todo episodio `inpatient` abierto sin estancia aparece en «En espera de cama» y en el tablero de ocupación (ASII-09). |
| RNF-ADM-07 | Privacidad | Minimización de datos en auditoría. | Los eventos guardan identificadores, sala y cama, no el motivo clínico completo de ingreso. |
| RNF-ADM-08 | Mantenibilidad (OCP) | Las reglas de elegibilidad se extienden sin modificar el verificador. | Agregar una regla (por ejemplo, `PediatricWardAgeRule`) solo crea una clase y su registro; `BedEligibilityChecker` y las reglas existentes no cambian y sus pruebas siguen pasando ([demo](./evidencia/demo-ocp.php)). |

## Escenarios de aceptación

### Admisión válida

```gherkin
Dado que el paciente existe, está activo y no tiene hospitalización abierta
Y el médico responsable tiene la especialidad «Medicina interna» activa
Cuando el personal de admisión registra la admisión
Entonces el sistema responde 201 con status open y bed null
Y existe un evento «admision.registrada» en local_access_audits
```

### Hospitalización duplicada

```gherkin
Dado que el paciente ya tiene un episodio inpatient abierto
Cuando se intenta registrar otra admisión
Entonces el sistema responde 409 ADMISSION_ALREADY_OPEN
Y muestra el episodio vigente
```

### Dos usuarios eligen la misma cama

```gherkin
Dado que la cama MED-A-01 está available
Cuando admisión y la jefatura de enfermería la confirman al mismo tiempo para episodios distintos
Entonces una solicitud responde 201
Y la otra responde 409 BED_ALREADY_ASSIGNED y su lista se refresca
Y la cama tiene una sola estancia abierta
```

### Sin camas disponibles

```gherkin
Dado que ninguna cama de la sala pedida es elegible
Cuando se buscan camas para el episodio
Entonces el sistema responde 200 con lista vacía
Y la admisión aparece como «En espera de cama»
```

### Regla nueva sin modificar el verificador (OCP)

```gherkin
Dado que pediatría pide que sus camas sean solo para menores de 15 años
Cuando se registra la clase PediatricWardAgeRule
Entonces un paciente de 40 años ya no ve ni puede recibir la cama PED-1-01 (PEDIATRIC_WARD_AGE)
Y un paciente de 8 años sí puede recibirla
Y BedEligibilityChecker y las reglas existentes no se modifican
```
