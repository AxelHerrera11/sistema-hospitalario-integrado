# Alertas críticas y notificaciones internas — Semana 15: pruebas unitarias, caja blanca y caja negra

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-21 · **Entrega:** casos de prueba y evidencia de ejecución.

| Archivo | Tipo | Objetivo |
|---|---|---|
| `micro-his/alertas-criticas/tests/whitebox.php` | Caja blanca | Recorrer cada rama de `CriticalAlert::detect` y `CriticalAlert::acknowledge` (15 casos) |
| `micro-his/alertas-criticas/tests/blackbox.php` | Caja negra | Probar el contrato HTTP con particiones de equivalencia y valores límite (11 casos) |
| `tests/run.php` y `tests/api.php` | Unitarias y de contrato | Las baterías de las Semanas 3 a 5, que siguen pasando |

## Caja blanca: ramas

| Rama | Condición en el código | Casos |
|---|---|---|
| B1 | La prueba no tiene umbrales → sin alerta | umbrales `null` |
| B2 | Valor < crítico bajo → `low` (estricto) | 39,9 alerta; 40 no |
| B3 | Valor > crítico alto → `high` (estricto) | 400,1 alerta; 400 no |
| B4 | Con médico tratante → nivel 0 y plazo de 15 min | dra.lopez, vence 08:15 |
| B5 | Sin médico tratante → nivel 1, jefatura | tratante `null` |
| B6 | El destinatario puede acusar sin relación registrada | dra.lopez |
| B7 | Otro usuario con relación de cuidado puede acusar | dr.ruiz con relación |
| B8 | Otro usuario sin relación → `NO_CARE_RELATION` | dr.ruiz sin relación |
| B9 | Lectura con otro paciente u otra prueba → `READ_BACK_MISMATCH` | P-2; K-v1 |
| B10 | Valor de la lectura no numérico, o numérico como texto | «treinta y dos» falla; «32.0» pasa |
| B11 | Ya acusada (se evalúa antes que la lectura) | segundo acuse |

Todas las ramas tienen al menos un caso. 

## Caja negra: particiones y valores límite (solo el contrato)

| Entrada | Particiones válidas | Particiones inválidas | Valores límite |
|---|---|---|---|
| `value` frente a crítico bajo 40 | < 40 | ≥ 40 (sin alerta) | 39,9, 40 y 40,1 |
| `value` frente a crítico alto 400 | > 400 | ≤ 400 (sin alerta) | 399,9, 400 y 400,1 |
| `lab_test_id` | con umbrales | sin umbrales (`not_critical`) | — |
| Campos del evento | completos y del tipo correcto | `value` ausente; `patient_id` numérico (422) | — |
| Secuencia | primer evento (201) | mismo `result_version_id` (200 `duplicate`) | — |

## Evidencia de ejecución (PHP 8.4.21)

```text
$ php micro-his/alertas-criticas/tests/whitebox.php
OK: B1 la prueba no tiene umbrales → sin alerta
OK: B2 valor < crítico bajo → low
OK: B2 valor = crítico bajo (límite de <) → sin alerta
OK: B3 valor > crítico alto → high
OK: B3 valor = crítico alto (límite de >) → sin alerta
OK: B4 con médico tratante → nivel 0, destinatario el médico y plazo de 15 min
OK: B5 sin médico tratante → nivel 1, jefatura de servicio
OK: B6 destinatario sin relación de cuidado registrada → se acepta (es el destinatario)
OK: B7 otro usuario con relación de cuidado → se acepta
OK: B8 otro usuario sin relación de cuidado → NO_CARE_RELATION
OK: B9 lectura con otro paciente → READ_BACK_MISMATCH
OK: B9 lectura con otra prueba → READ_BACK_MISMATCH
OK: B10 lectura con valor no numérico → READ_BACK_MISMATCH
OK: B10 lectura «32.0» como texto numérico → se acepta
OK: B11 alerta ya acusada → ALERT_ALREADY_ACKNOWLEDGED (antes que la lectura)
Todas las pruebas de caja blanca pasaron.

$ php micro-his/alertas-criticas/tests/blackbox.php
OK: value 39.9 → 201 created
OK: value 40 → 200 not_critical
OK: value 40.1 → 200 not_critical
OK: value 399.9 → 200 not_critical
OK: value 400 → 200 not_critical
OK: value 400.1 → 201 created
OK: lab_test_id sin umbrales en el catálogo → 200 not_critical
OK: value ausente → 422 EVENT_INVALID
OK: patient_id numérico → 422 EVENT_INVALID
OK: primer evento → 201 created
OK: mismo result_version_id otra vez → 200 duplicate
Todas las pruebas de caja negra pasaron.
```

`tests/run.php` (30 verificaciones) y `tests/api.php` (14) también terminan con código 0. Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
