# RF/RNF y criterios de aceptación — Alergias clínicas y alerta visual en expediente

Los requerimientos se derivan de los casos de uso y flujos de la [Semana 1](../semana-01/README.md). Cada RF indica el caso de uso que lo origina para mantener la trazabilidad.

## Requerimientos funcionales

| Código | UC | Requerimiento funcional | Criterio de aceptación |
|---|---|---|---|
| RF-ALG-01 | UC-ALG-01 | El sistema debe permitir registrar una alergia del paciente con alérgeno (código o nombre), tipo, severidad, reacción opcional y fecha de inicio. | **Dado** un médico o enfermero con permiso `alergias.registrar` y relación de cuidado, **cuando** envía datos válidos, **entonces** se crea una alergia `active` con `verified_by_user_ref_id` = usuario y se responde `201`. |
| RF-ALG-02 | UC-ALG-02 | El sistema debe validar el alérgeno, el tipo, la severidad y la fecha de inicio antes de guardar. | **Dado** un registro sin código ni nombre de alérgeno, con tipo o severidad fuera del catálogo o con fecha de inicio futura, **cuando** se intenta guardar, **entonces** se responde `422` con el error por campo y no se persiste nada. |
| RF-ALG-03 | UC-ALG-03 | El sistema debe rechazar una alergia activa equivalente a otra ya registrada, incluso si dos usuarios la registran al mismo tiempo. | **Dado** que el paciente ya tiene «Penicilina» activa de tipo `allergy`, **cuando** se registra otra vez (o en paralelo), **entonces** se responde `409 ALLERGY_DUPLICATED` con la alergia existente y solo queda una fila activa. |
| RF-ALG-04 | UC-ALG-04, 05 | Al abrir el expediente, el sistema debe mostrar una alerta visual según la severidad máxima de las alergias activas. | **Dado** un paciente con una alergia `severe` o `life_threatening`, **cuando** se abre el expediente, **entonces** aparece un banner rojo no descartable con alérgeno y reacción. Con solo leves, moderadas, desconocidas o intolerancias, el banner es ámbar; sin alergias activas, el indicador neutro dice «Sin alergias registradas». |
| RF-ALG-05 | UC-ALG-05 | Si las alergias no se pueden leer, el sistema debe advertirlo y nunca mostrar «sin alergias». | **Dado** que la consulta de alergias falla, **cuando** se abre el expediente, **entonces** se muestra el banner gris «Alergias no disponibles — verifique antes de prescribir» y la API responde `503 ALLERGIES_UNAVAILABLE`. |
| RF-ALG-06 | UC-ALG-06 | El médico tratante debe poder resolver o inactivar una alergia activa con fecha y motivo, sin borrarla. | **Dado** una alergia `active`, **cuando** el médico tratante la resuelve con motivo y fecha válidos, **entonces** queda `resolved` con `resolved_at`, sale de la alerta visual y sigue en el historial. Sin motivo, `422`; si ya no está activa, `409`. |
| RF-ALG-07 | UC-ALG-09 | El sistema debe exponer a Prescripciones (ASII-15) la lista de alergias activas del paciente. | **Dado** un token de servicio válido del mismo tenant, **cuando** Prescripciones consulta las alergias activas, **entonces** recibe código, nombre, tipo y severidad de cada una; si la base no responde, recibe `503` y no debe guardar ni firmar. |
| RF-ALG-08 | UC-ALG-08 | El sistema debe auditar el registro, el cambio de estado y la consulta de alergias. | **Dado** un registro o cambio de estado exitoso, **cuando** termina la transacción, **entonces** existe un evento en `local_access_audits` con usuario, paciente, alergia, acción y motivo (si aplica). |

## Requerimientos no funcionales

| Código | Categoría | Requerimiento no funcional | Criterio de aceptación (medible) |
|---|---|---|---|
| RNF-ALG-01 | Seguridad | Acceso por permiso RBAC y por tenant. | El 100 % de los intentos sin `alergias.*` o con un `X-Tenant-ID` ajeno reciben `401`/`403` y quedan trazados; ningún usuario lee alergias de otro hospital. |
| RNF-ALG-02 | Seguridad del paciente | Falla segura de la alerta. | En las pruebas con la base caída, el expediente muestra «Alergias no disponibles» en el 100 % de los casos y nunca «Sin alergias registradas». |
| RNF-ALG-03 | Consistencia | Registro y auditoría atómicos. | Si la auditoría falla, la alergia no queda guardada: 0 alergias sin evento de auditoría en la prueba de falla inducida. |
| RNF-ALG-04 | Concurrencia | Sin duplicados activos. | Con 10 registros simultáneos de la misma alergia, queda exactamente 1 fila `active` (índice `allergies_active_equivalent_unique`). |
| RNF-ALG-05 | Rendimiento | La alerta aparece rápido al abrir el expediente. | `GET /patients/{patient}/allergies?status=active` responde en ≤ 300 ms (p95) con hasta 50 alergias por paciente; el banner se dibuja en ≤ 1 s. |
| RNF-ALG-06 | Usabilidad y accesibilidad | La alerta no depende solo del color. | El banner rojo y el ámbar incluyen ícono y texto («Alergia grave», «Alergia»), contraste ≥ 4.5:1 y se anuncian con `role="alert"`. |
| RNF-ALG-07 | Privacidad | Minimización de datos en auditoría. | Los eventos de auditoría guardan identificadores, severidad y acción, pero no el texto libre de la reacción. |
| RNF-ALG-08 | Mantenibilidad (OCP) | Las reglas de alerta se extienden sin modificar el evaluador. | Agregar una regla nueva (por ejemplo, `LatexAllergyRule`) solo crea una clase y su registro; `AllergyAlertEvaluator` y las reglas existentes no cambian y sus pruebas siguen pasando ([demo](./evidencia/demo-ocp.php)). |

## Escenarios de aceptación

### Registro válido

```gherkin
Dado que el médico tiene permiso alergias.registrar y un care_assignment activo con el paciente
Cuando registra «Penicilina», tipo allergy, severidad severe, reacción «urticaria»
Entonces el sistema responde 201
Y la alergia queda active con el médico como verificador
Y existe un evento «alergia.registrada» en local_access_audits
```

### Duplicado concurrente

```gherkin
Dado que dos enfermeros registran «Penicilina» tipo allergy para el mismo paciente al mismo tiempo
Cuando ambas transacciones intentan hacer COMMIT
Entonces una responde 201 y la otra 409 ALLERGY_DUPLICATED
Y el paciente tiene una sola alergia activa equivalente
```

### Alerta roja al abrir el expediente

```gherkin
Dado que el paciente tiene «Maní» life_threatening y «Polen» mild activas
Cuando cualquier usuario autorizado abre el expediente
Entonces se muestra un banner rojo no descartable con «Maní» primero
Y el banner tiene ícono, texto y role="alert"
```

### Falla segura

```gherkin
Dado que la base de alergias no responde
Cuando el médico abre el expediente
Entonces se muestra el banner gris «Alergias no disponibles — verifique antes de prescribir»
Y nunca se muestra «Sin alergias registradas»
```

### Resolución sin motivo

```gherkin
Dado que existe una alergia active
Cuando el médico tratante intenta resolverla sin escribir motivo
Entonces el sistema responde 422
Y la alergia sigue active y en la alerta visual
```

### Regla nueva sin modificar el evaluador (OCP)

```gherkin
Dado que el comité de quirófano pide que la alergia al látex siempre sea roja
Cuando se registra la clase LatexAllergyRule en el contenedor
Entonces una alergia «Látex» mild produce banner rojo
Y AllergyAlertEvaluator, SevereAllergyRule y NonSevereAllergyRule no se modifican
```
