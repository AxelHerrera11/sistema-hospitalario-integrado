# Diseño de las pruebas

Solo se prueba el expediente. RBAC, Gobernanza y Pacientes se usan con sus clases reales, pero no se prueban
sus reglas internas (son de otros módulos).

## Unitarias (mínimo 3 → 13 en PHP + 11 en la interfaz)

| Id | Regla | Archivo |
|---|---|---|
| U-01 | Apertura de paciente activo: abierto, sin cierre, `EXP-<código>` | `tests/unitarias.php` |
| U-02 | Paciente fallecido no recibe expediente (409) | idem |
| U-03, U-03b, U-03c | `medical_records_closure_check` replicada | idem |
| U-04, U-04b | Período y tipo del episodio | idem |
| U-05 | Orden de la línea de tiempo y episodios abiertos | idem |
| U-06, U-06b | Episodio o expediente de otro paciente → 500 sin datos | idem |
| U-07, U-07b, U-07c | Límites del código (40/41) e inyección | idem |
| V-01…V-11 | Validación, interpretación de respuestas, reintentos, etiquetas, zona horaria | `tests/ui-estados.test.mjs` |

## Caja blanca (mínimo 2 → 16 casos)

Cobertura de ramas y condiciones de los dos casos de uso. Cada fila es un camino distinto del flujo.

### `ConsultLongitudinalRecord::execute`

| Decisión / condición | Verdadero | Falso |
|---|---|---|
| `assertCode` válido | C1…C8 | N-07c (422) |
| RBAC `allowed` | C2…C8 | **C1** |
| `forHospital(...) !== null` | C3…C8 | **C2** |
| `$patient === null` (1.ª condición del `||`) | **C3** | C4, C5, C7 |
| `!hasActiveAssignment(...)` (2.ª condición) | **C4** | C5, C7 |
| `findByPatient(...) !== null` | C6, C7 | **C5** |
| Auditoría persistida | C7 | **C6** |
| Almacén disponible | C1…C7 | **C6b** |
| Referencia local ≠ identidad CENTRAL | **C8** | C1…C7 |

### `OpenMedicalRecord::execute` (+ `Repository::open`)

| Decisión / condición | Verdadero | Falso |
|---|---|---|
| RBAC concede el permiso de apertura | A2…A7 | **A1** |
| Paciente existe | A3…A7 | **A2** |
| Ya tiene expediente | **A3** | A4…A7 |
| Paciente activo (`MedicalRecord::open`) | A5…A7 | **A4** |
| `$confirm` (auditoría) no lanza | A7 | **A5** (se revierte) |
| Otro proceso ganó la carrera | **A6** | A7 |

## Caja negra (mínimo 4 → 21 casos por adaptador, PDO e InMemory)

Diseñadas desde el contrato (`openapi.yaml`), sin mirar el código.

### Clases de equivalencia

| Variable | Clases válidas | Clases inválidas |
|---|---|---|
| Rol en el hospital | medico con vínculo (N-01) | enfermeria sin vínculo (N-02); recepcion (N-03); admin_hospital (N-04); sin membresía (N-05) |
| Hospital | con base (N-01) | membresía sin base (N-05b); cabecera ausente (N-06b) |
| Identidad | presente (N-01) | ausente (N-06) |
| Código de paciente | existente (N-01); inexistente bien formado (N-07) | caracteres no permitidos (N-07d) |
| Operación | GET, POST | DELETE (N-13); ruta vacía (N-13) |
| Infraestructura | disponible | almacén caído (N-11); bitácora caída (N-12) |

### Valores límite del código

| Longitud | Resultado esperado | Caso |
|---|---|---|
| 1 | válido (403 al no existir) | N-07 |
| 40 | válido | N-07b |
| 41 | 422 | N-07c |
| 0 | ruta no encontrada (404) | N-13 |

### Permisos y errores

N-08 (apertura con la matriz real → 403), N-09/N-10 (apertura con la propuesta activa: 201, 200 repetida, 409, 404),
N-10b (auditoría de apertura y consulta).
