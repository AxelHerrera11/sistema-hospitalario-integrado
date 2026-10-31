# Catálogo de pruebas de laboratorio — Semana 15: pruebas unitarias, caja blanca y caja negra

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-17 · **Entrega:** casos de prueba y evidencia de ejecución.

| Archivo | Tipo | Objetivo |
|---|---|---|
| `micro-his/catalogo-pruebas-lab/tests/whitebox.php` | Caja blanca | Recorrer cada rama de `LabTest::register` y sus límites internos (16 casos) |
| `micro-his/catalogo-pruebas-lab/tests/blackbox.php` | Caja negra | Probar el contrato HTTP con particiones de equivalencia y valores límite (13 casos) |
| `tests/run.php` y `tests/api.php` | Unitarias y de contrato | Las baterías de las Semanas 3 a 5, que siguen pasando |

## Caja blanca: ramas de `LabTest::register`

| Rama | Condición en el código | Casos |
|---|---|---|
| B1 | Un obligatorio ausente, vacío o que no es texto | `name` vacío, `code` numérico |
| B2 | `min_volume_ml <= 0` (lo no numérico se toma como 0) | volumen 0, volumen «mucho» |
| B3 | `result_type` fuera de `numeric`, `qualitative` y `text` | `ordinal` |
| B4 | Numérica sin unidad o con algún rango nulo | sin unidad, sin crítico alto |
| B5 | Numérica con los rangos fuera de orden (`<`, `≤`, `<`) | crítico bajo = referencia baja; referencia baja = alta (válida); referencia alta = crítico alto |
| B6 | No numérica con algún rango | cualitativa con un solo rango |
| B7 | No numérica sin rangos | cualitativa válida |
| B8 | Vigencia con formato inválido o anterior a hoy | «18/10/2026», ayer |
| B9 | Camino válido y normalización | vigencia de hoy; código en mayúsculas, nombre sin espacios, volumen «0.5» → 0.5 |

Las 9 ramas tienen al menos un caso. Esto cierra los dos caminos que la métrica de la Semana 14 marcaba sin prueba: el tipo no soportado y la numérica sin unidad.

## Caja negra: particiones y valores límite (solo el contrato)

| Entrada | Particiones válidas | Particiones inválidas | Valores límite |
|---|---|---|---|
| `result_type` | `numeric`, `text` | fuera del `enum` (`titer`) | — |
| `min_volume_ml` | > 0 | 0, negativos | 0 (excluido) y 0,01 |
| `critical_low` frente a `reference_low` = 70 | menor | igual o mayor | 69,99 y 70 |
| `valid_from` | hoy o después | antes de hoy | hoy y ayer |
| `code` | nuevo | con vigencia abierta (409) | — |
| Obligatorios | presentes | ausentes (`name`) | — |

## Evidencia de ejecución (PHP 8.4.21)

```text
$ php micro-his/catalogo-pruebas-lab/tests/whitebox.php
OK: B1 falta un obligatorio (name vacío) → FIELD_REQUIRED:name
OK: B1 obligatorio que no es texto (code numérico) → FIELD_REQUIRED:code
OK: B2 volumen 0 → SPECIMEN_REQUIREMENT_INVALID
OK: B2 volumen no numérico se toma como 0 → SPECIMEN_REQUIREMENT_INVALID
OK: B3 tipo no soportado (ordinal) → RESULT_TYPE_UNSUPPORTED
OK: B4 numérica sin unidad → RANGES_INCOHERENT:unit
OK: B4 numérica con un rango faltante → RANGES_INCOHERENT:unit
OK: B5 crítico bajo = referencia baja (límite de <) → RANGES_INCOHERENT
OK: B5 referencia baja = referencia alta (límite de ≤) → válida
OK: B5 referencia alta = crítico alto (límite de <) → RANGES_INCOHERENT
OK: B6 cualitativa con un solo rango → RESULT_DEFINITION_INVALID
OK: B7 cualitativa sin rangos → válida
OK: B8 vigencia con formato inválido → VALIDITY_INVALID
OK: B8 vigencia de ayer → VALIDITY_INVALID
OK: B9 vigencia de hoy (límite) → válida
OK: B9 normaliza código y nombre, convierte números y conserva la versión
Todas las pruebas de caja blanca pasaron.

$ php micro-his/catalogo-pruebas-lab/tests/blackbox.php
OK: result_type numeric (válido) → 201
OK: result_type text sin rangos (válido) → 201
OK: result_type fuera del enum → 422 RESULT_TYPE_UNSUPPORTED
OK: min_volume_ml = 0 (límite excluido) → 422
OK: min_volume_ml = 0.01 (justo encima) → 201
OK: min_volume_ml = -1 (partición negativa) → 422
OK: critical_low = 69.99 (justo debajo de 70) → 201
OK: critical_low = 70 (igual a la referencia) → 422 RANGES_INCOHERENT
OK: valid_from = hoy → 201
OK: valid_from = ayer → 422 VALIDITY_INVALID
OK: mismo código con vigencia abierta → 409 LAB_TEST_VALIDITY_OVERLAP
OK: otro código en el mismo catálogo → 201
OK: campo obligatorio ausente (name) → 422 FIELD_REQUIRED
Todas las pruebas de caja negra pasaron.
```

`tests/run.php` (30 verificaciones) y `tests/api.php` (15) también terminan con código 0. Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
