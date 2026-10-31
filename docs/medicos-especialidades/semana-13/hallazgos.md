# Autoverificación del autor y hallazgos de preparación

Preparación de la [revisión técnica formal](./README.md), ejecutada el 2026-10-20 sobre la punta de la pila de ASII-04 (PR #372, que contiene #126, #355 y #356). Todos los hallazgos están **Abiertos**: los confirma o descarta la reunión.

## Cómo se reproduce

```bash
git archive origin/feature/asii-04-semana-05-medicos-especialidades-api-jhos-hgnu micro-his/medicos-especialidades | tar -x -C <carpeta-vacía>
php <carpeta>/micro-his/medicos-especialidades/tests/run.php     # Todas las pruebas pasaron.
php <carpeta>/micro-his/medicos-especialidades/tests/api.php     # Todas las pruebas del API pasaron.
php docs/medicos-especialidades/semana-13/evidencia/sondas.php <carpeta>/micro-his/medicos-especialidades
```

Las dos suites existentes pasan: los hallazgos no son pruebas rotas, son casos que las suites **no cubren**. Las sondas (P1 a P10) los demuestran sobre el mismo código. No se modificó ningún archivo del módulo.

## Resumen

| ID | Ítem | Severidad | Hallazgo | Responsable |
|---|---|---|---|---|
| H-01 | C11 | **Mayor** | El módulo no tiene aislamiento de tenant | Autor + María (RBAC) |
| H-02 | C13 | **Mayor** | La disponibilidad ignora la duración de la cita | Autor + Lis (Citas) |
| H-03 | C03 | **Mayor** | Un usuario puede tener dos perfiles de médico activos | Autor |
| H-04 | C09 | **Mayor** | Una ruta sin permiso declarado pasa sin autorización | Autor |
| H-05 | C03 | Medio | La carrera de unicidad termina en 500 en vez de 409 | Autor |
| H-06 | C02 | Medio | El esquema de pruebas no tiene las restricciones de la base real | Autor + Javier (BD) |
| H-07 | C08 | Medio | La consulta de disponibilidad no cubre el rango ni `generated_at` de RF-MED-06 | Autor |
| H-08 | C05 | Menor | Dos caminos devuelven un código distinto del que dice el contrato | Autor |
| H-09 | C14 | Menor | Un día sin franjas es indistinguible de una agenda llena | Autor |
| H-10 | C15 | **Mayor** (proceso) | 9 commits llevan atribución de IA en el pie | Decisión de Josué |
| H-11 | C15 | Menor (proceso) | Dos PR en borrador rozan las 400 líneas si se cuentan las borradas | Autor |

## Detalle

### H-01 · Sin aislamiento de tenant (C11)

- **Ubicación:** todo el módulo. `grep -rniE "tenant|hospital_id"` sobre `micro-his/medicos-especialidades` no encuentra nada.
- **Evidencia:** RNF-MED-01 exige que el 100 % de las peticiones con `X-Tenant-ID` ajeno reciban 401/403. `ApiKernel` solo lee `X-User-ID`; ninguna consulta de `PdoDoctorStore` filtra por hospital.
- **Impacto:** el contrato de la Semana 5 delega el tenant en un «middleware» que en el micro-HIS no existe. Con una sola base compartida, un administrador de un hospital registraría médicos en otro.
- **Recomendación:** documentar que el tenant lo resuelve la **conexión** (la base es por hospital en el esquema federado) y probarlo con un caso `X-Tenant-ID` ajeno → 403 en el kernel; no agregar `hospital_id` a las tablas sin acordarlo con ASII-09.

### H-02 · La disponibilidad ignora la duración (C13)

- **Evidencia (P1):** una cita confirmada de 08:00 que dura 60 min deja `08:30` libre: `slots: ["08:30","09:00","09:30"]`. `busySlots()` solo lee `starts_at` (`substr(starts_at, 12, 5)`) y la tabla de pruebas no tiene `ends_at`; la tabla real `appointments` sí (`starts_at`, `ends_at`).
- **Impacto:** la recepcionista ve libre una hora ocupada. La restricción `appointments_doctor_active_period_exclusion` rechaza el choque al reservar, pero el usuario lo descubre tarde, justo el riesgo H2 del flujo UX.
- **Recomendación:** `AppointmentReader` devuelve intervalos `[inicio, fin)` y `SlotPlanner` descarta toda franja que los toque; prueba con una cita de 60 min.

### H-03 · Dos perfiles activos para el mismo usuario (C03)

- **Evidencia (P3):** el mismo `local_user_ref_id` con licencias distintas da `201` dos veces. RF-MED-02 pide `409 DOCTOR_ALREADY_REGISTERED` para «un usuario con perfil activo»; ese código no existe en el código ni en el contrato. La migración real lo prohíbe con `doctor_profiles_active_open_user_unique`, de modo que en PostgreSQL la segunda alta fallaría como 500.
- **Recomendación:** `DoctorProfileStore::userHasActiveProfile()` y el 409 en `RegisterDoctor`; agregar el código al contrato.

### H-04 · Permisos permitidos por defecto (C09)

- **Evidencia (P5):** `ApiKernel::permissionFor('GET','/specialties')` y `('GET','/doctors/doc-ana')` devuelven `NULL`, y con `NULL` el kernel despacha sin comprobar nada. Hoy no se explota porque el `Router` responde 404, pero las rutas «propuestas» del contrato nacerían abiertas en cuanto se agreguen al `Router`.
- **Impacto:** falla abierta ante un olvido, lo contrario de la falla cerrada que el kernel aplica cuando RBAC cae (503).
- **Recomendación:** que toda ruta bajo `/api/v1` sin permiso declarado responda 403 y una prueba que recorra las rutas del `Router` contra la tabla.

### H-05 · La carrera de unicidad da 500 (C03)

- **Evidencia (P2):** si otro proceso inserta `COL-1` entre `licenseExists()` y `INSERT`, el `UNIQUE` evita el duplicado (la integridad se conserva), pero `transaction()` lo convierte en `RuntimeException` y el handler en `500 DOCTOR_PERSISTENCE_ERROR`; el contrato dice 409.
- **Recomendación:** distinguir la violación de unicidad (SQLSTATE `23505` en PostgreSQL, `23000` en SQLite) y traducirla a `LICENSE_ALREADY_REGISTERED`. Aplica igual a la asociación duplicada.

### H-06 · El esquema de pruebas no representa la base (C02)

- **Evidencia (P4):** `database/schema.sql` admite dos especialidades principales solapadas sin error; la migración 000100 las impide con `EXCLUDE USING gist`. Además `substr(starts_at, 12, 5)` y los ids `doc-xxxxxxxx` son de SQLite; la base real usa `timestamptz` y `uuid`.
- **Impacto:** las 58 pruebas (43 + 15) pasan sin poder detectar H-03 ni un choque de asociaciones que solo la base decide. **No se ejecutó contra PostgreSQL en esta revisión**, así que el fallo de `substr` sobre `timestamptz` queda como riesgo a confirmar con Javier.
- **Recomendación:** una prueba de contrato contra PostgreSQL con la migración real (ASII-09) antes de dar por buena la persistencia.

### H-07 · RF-MED-06 sin cubrir (C08)

- **Evidencia:** RF-MED-06 pide un rango de hasta 14 días y `generated_at`; el contrato y `QueryAvailability` reciben una sola `date` y no devuelven `generated_at`. Es una decisión de las Semanas 4 y 5 que nunca se reconcilió con el requisito.
- **Recomendación:** decidir en la reunión: ajustar el requisito a una fecha, o agregar `from`/`to` (máximo 14 días) y `generated_at` al contrato v1 como campos nuevos, sin romper clientes.

### H-08 · Códigos distintos del contrato (C05)

- **Evidencia (P6, P7):** `GET /availability` sin `specialty` responde `422 SPECIALTY_INACTIVE` y el contrato dice `PAYLOAD_INVALID`. Asociar con un médico inexistente y una especialidad inactiva responde 422, no `404 DOCTOR_NOT_FOUND`, porque `AssociateSpecialty` valida la especialidad antes de buscar al médico.
- **Recomendación:** buscar al médico primero y tratar el parámetro ausente como `PAYLOAD_INVALID`.

### H-09 · Día sin franjas (C14)

- **Evidencia (P8):** un martes sin horario configurado devuelve `status: "OK", slots: []`, igual que un lunes completamente ocupado. La interfaz no puede decirle a la recepción «no atiende hoy» frente a «sin horas libres».
- **Recomendación:** agregar un estado `NO_SCHEDULE_FOR_DAY` (campo nuevo del contrato) o diferirlo hasta que exista `doctor_schedules` (PR #413).

### H-10 · Atribución de IA en commits (C15)

Nueve commits de las Semanas 2 y 3 terminan con `Co-Authored-By: Claude Opus 5.5`, contra la regla de no mencionar IA en commits: `c59c4fc`, `bb488f0`, `7f9ff6b`, `646672c` (PR #66); `ee35fbf`, `2c4b95e`, `0340c76` (PR #126 y toda la pila hasta #372); `9557ff2`, `ef3fad4` (PR #127). Las Semanas 4 a 12 están limpias, y el formato `tipo(scope): descripción (ASII-04)` se cumple en los 88 commits. Aparte, 22 asuntos nombran «declaración de IA» al referirse al archivo `DECLARACION_IA.md`; no atribuyen autoría, pero conviene que Josué confirme que es aceptable.

**Corregirlo exige reescribir historia ya publicada y empujarla con fuerza**, lo que las reglas prohíben. Por eso queda como decisión de Josué: dejarlo declarado, o autorizar la reescritura de esas cinco ramas y sus dependientes.

### H-11 · Tamaño de los PR (C15)

Líneas propias (insertadas) frente a su base apilada; entre paréntesis, insertadas más borradas.

| PR | Estado | Propias | Observación |
|---|---|---|---|
| #126 | Abierto | 278 | Base `develop` |
| #355 | Abierto | 124 (130) | Muestra 396 porque contiene #126 |
| #356 | Borrador | 313 (431) | Sobre 400 si se cuentan las borradas |
| #372 | Borrador | 392 (399) | Al límite |
| #371, #357, #127 | Abierto | 354, 227, 214 | Cumplen |
| #428, #429 | Abierto | 351, 369 | Cumplen |

Ninguno supera 400 insertadas. Se recomienda que #356 baje de 400 también contando borradas si la revisión usa ese criterio.

## Solapamiento #126 y #355

#355 **no reemplaza** a #126: es el siguiente eslabón de una pila lineal **#126 → #355 → #356 → #372**. `git merge-base --is-ancestor` confirma que cada rama contiene a la anterior, y las uniones por pares (`git merge-tree`) no tienen conflictos. Los archivos que comparten (`PdoDoctorStore`, `ConfigScheduleProvider`, `DoctorHttpHandler`, `tests/run.php`) los modifican en secuencia, no en paralelo.

Cerrar #126 deja a #355 mostrando sus líneas y quita del historial la evidencia de la Semana 3. Orden de integración recomendado: #126, #355, #356, #372; tras cada uno el siguiente reduce su diff a sus líneas propias.
