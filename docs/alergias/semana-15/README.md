# Alergias clínicas y alerta visual en expediente — Semana 15: pruebas unitarias, caja blanca y caja negra

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-12 · **Entrega:** casos de prueba y evidencia de ejecución.

| Archivo | Tipo | Objetivo |
|---|---|---|
| `micro-his/alergias/tests/whitebox.php` | Caja blanca | Recorrer cada rama de `Allergy::register`, `AllergyAlert::from` y la falla segura de `GetAllergyAlert` (12 casos) |
| `micro-his/alergias/tests/blackbox.php` | Caja negra | Probar el contrato HTTP con particiones de equivalencia y valores límite (14 casos) |
| `tests/run.php` y `tests/api.php` | Unitarias y de contrato | Las baterías de las Semanas 3 a 5, que siguen pasando |

## Caja blanca: ramas

| Rama | Condición en el código | Casos |
|---|---|---|
| B1 | Sin código ni nombre (vacíos o solo espacios) | código « » y nombre «» |
| B2 | Basta el código o basta el nombre | solo código; solo nombre |
| B3 | Tipo fuera de `allergy`, `intolerance` y `unknown` | «alergia» |
| B4 | Severidad fuera de las 5 permitidas | «grave» |
| B5 | Camino válido: recorta y convierte vacíos en `null` | código « RXN-2 », reacción « » |
| B6 | Sin alergias activas → `none` | lista vacía |
| B7 | Alguna `severe` o `life_threatening` → `critical` | una severa; una que amenaza la vida entre leves |
| B8 | Solo leves, moderadas o desconocidas → `warning` | mild, moderate y unknown |
| B9 | `unavailable()` nunca trae alergias | fábrica directa |
| B10 | La lectura falla → `unavailable` | repositorio con `failReads` |

Todas las ramas tienen al menos un caso. 

## Caja negra: particiones y valores límite (solo el contrato)

| Entrada | Particiones válidas | Particiones inválidas | Valores límite |
|---|---|---|---|
| `severity` | los 5 valores del enum (3 → `warning`, 2 → `critical`) | fuera del enum | — |
| `allergy_type` | `intolerance` | fuera del enum | — |
| `allergen_name` | con texto | solo espacios y sin código | 1 carácter frente a «   » |
| Equivalencia activa | otro tipo de alergia | mismo alérgeno en mayúsculas (409) | — |
| Paciente | existente | inexistente (404) | — |
| Almacenamiento | disponible | caído → `unavailable`, nunca `none` | — |

## Evidencia de ejecución (PHP 8.4.21)

```text
$ php micro-his/alergias/tests/whitebox.php
OK: B1 sin código ni nombre (solo espacios) → ALLERGEN_REQUIRED
OK: B2 solo código → válida
OK: B2 solo nombre → válida
OK: B3 tipo fuera de la lista → ALLERGY_TYPE_INVALID
OK: B4 severidad fuera de la lista → SEVERITY_INVALID
OK: B5 válida: recorta textos y convierte los vacíos en null
OK: B6 sin alergias activas → none
OK: B7 una severe → critical
OK: B7 una life_threatening entre leves → critical
OK: B8 solo mild, moderate o unknown → warning
OK: B9 unavailable() nunca trae alergias
OK: B10 la lectura falla → GetAllergyAlert devuelve unavailable (falla segura)
Todas las pruebas de caja blanca pasaron.

$ php micro-his/alergias/tests/blackbox.php
OK: severity mild → banner warning
OK: severity moderate → banner warning
OK: severity unknown → banner warning
OK: severity severe → banner critical
OK: severity life_threatening → banner critical
OK: severity fuera del enum → 422 SEVERITY_INVALID
OK: allergy_type intolerance (válido) → 201
OK: allergy_type fuera del enum → 422 ALLERGY_TYPE_INVALID
OK: allergen_name de 1 carácter → 201
OK: allergen_name solo espacios y sin código → 422 ALLERGEN_REQUIRED
OK: mismo alérgeno en mayúsculas → 409 ALLERGY_ALREADY_ACTIVE
OK: mismo alérgeno con otro tipo (intolerancia) → 201
OK: paciente inexistente → 404 PATIENT_NOT_FOUND
OK: lectura sin base → 200 con level unavailable (nunca none)
Todas las pruebas de caja negra pasaron.
```

`tests/run.php` (30 verificaciones) y `tests/api.php` (15) también terminan con código 0. Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
