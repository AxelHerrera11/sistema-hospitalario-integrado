# Admisión hospitalaria y asignación de cama — Semana 15: pruebas unitarias, caja blanca y caja negra

**Estudiante:** Herrera Sánchez, Axel Eliú (`AxelHerrera11`) · **Módulo:** ASII-07 · **Entrega:** casos de prueba y evidencia de ejecución.

| Archivo | Tipo | Objetivo |
|---|---|---|
| `micro-his/admision-camas/tests/whitebox.php` | Caja blanca | Recorrer cada rama de `Admission::admit` y `AdmitPatient`, incluida la carrera por la misma cama (11 casos) |
| `micro-his/admision-camas/tests/blackbox.php` | Caja negra | Probar el contrato HTTP con particiones de equivalencia y valores límite (12 casos) |
| `tests/run.php` y `tests/api.php` | Unitarias y de contrato | Las baterías de las Semanas 3 a 5, que siguen pasando |

## Caja blanca: ramas

| Rama | Condición en el código | Casos |
|---|---|---|
| B1 | Motivo vacío o solo espacios | «   » |
| B2 | Paciente ya admitido (se evalúa antes que la cama) | admitido y cama ocupada |
| B3 | Cama no disponible | `occupied`, `maintenance` e `inactive` |
| B4 | Tipo pedido distinto del de la cama | adulto en cama pediátrica |
| B5 | Sin tipo pedido: cualquier tipo sirve | cama pediátrica sin `bed_type` |
| B6 | Camino válido: recorta el motivo | «  Neumonía  » |
| B7 | Paciente inexistente (antes de mirar la cama) | repositorio sin pacientes |
| B8 | Cama inexistente | `B-9` |
| B9 | Carrera: la lectura vio la cama libre, pero otra admisión la ocupó antes del guardado → `BED_NOT_AVAILABLE` y una sola estancia | repositorio con lectura vieja |

Todas las ramas tienen al menos un caso. La rama B9 cierra el hallazgo RT-05 de la Semana 13: ahora hay una prueba del camino de carrera, no solo del de dominio.

## Caja negra: particiones y valores límite (solo el contrato)

| Entrada | Particiones válidas | Particiones inválidas | Valores límite |
|---|---|---|---|
| `type` (búsqueda) | vacío; `adult` | inexistente → lista vacía | — |
| `reason` | con texto | vacío | 1 carácter frente a «» |
| Estado de la cama | disponible | mantenimiento (409); inexistente (404) | — |
| `bed_type` | ausente; igual al de la cama | distinto (422) | — |
| Secuencia | primera admisión | misma cama (409); mismo paciente (409) | — |
| Paciente | existente | inexistente (404) | — |

## Evidencia de ejecución (PHP 8.4.21)

```text
$ php micro-his/admision-camas/tests/whitebox.php
OK: B1 motivo solo con espacios → REASON_REQUIRED
OK: B2 paciente ya admitido → PATIENT_ALREADY_ADMITTED (antes que la cama)
OK: B3 cama occupied → BED_NOT_AVAILABLE
OK: B3 cama maintenance → BED_NOT_AVAILABLE
OK: B3 cama inactive → BED_NOT_AVAILABLE
OK: B4 tipo pedido distinto del de la cama → BED_TYPE_MISMATCH
OK: B5 sin tipo pedido → acepta cualquier tipo
OK: B6 válida: recorta el motivo y conserva cama y hora
OK: B7 paciente inexistente → PATIENT_NOT_FOUND (antes de mirar la cama)
OK: B8 cama inexistente → BED_NOT_FOUND
OK: B9 carrera por la misma cama → BED_NOT_AVAILABLE y una sola estancia
Todas las pruebas de caja blanca pasaron.

$ php micro-his/admision-camas/tests/blackbox.php
OK: type vacío → todas las disponibles
OK: type adult → solo MED-101 (MED-103 está en mantenimiento)
OK: type inexistente → lista vacía (200)
OK: reason de 1 carácter → 201
OK: reason vacío → 422 REASON_REQUIRED
OK: cama en mantenimiento → 409 BED_NOT_AVAILABLE
OK: cama inexistente → 404 BED_NOT_FOUND
OK: bed_type ausente con cama pediátrica → 201
OK: bed_type adult con cama pediátrica → 422 BED_TYPE_MISMATCH
OK: otra persona en la cama ya ocupada → 409 BED_NOT_AVAILABLE
OK: el mismo paciente en otra cama → 409 PATIENT_ALREADY_ADMITTED
OK: paciente inexistente → 404 PATIENT_NOT_FOUND
Todas las pruebas de caja negra pasaron.
```

`tests/run.php` (32 verificaciones) y `tests/api.php` (15) también terminan con código 0. Uso de IA: [DECLARACION_IA.md](./DECLARACION_IA.md).
