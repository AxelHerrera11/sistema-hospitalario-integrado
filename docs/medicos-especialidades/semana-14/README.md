# Médicos, especialidades y disponibilidad — Semana 14: plan SQA y confiabilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** plan de aseguramiento de la calidad (SQA) con métricas y controles, cada uno con responsable, umbral, fuente y acción.

**Tarea individual:** Defina el plan SQA de Médicos, especialidades y disponibilidad: calidad de «registro de médico, asociación de especialidad y consulta de disponibilidad», confiabilidad, seguridad, trazabilidad y riesgos de médicos, especialidades, horarios, disponibilidad y conflictos de agenda.

> Este plan parte de la [revisión técnica de la Semana 13](../semana-13/README.md). Los valores de «Hoy» salen de su [autoverificación](../semana-13/hallazgos.md) (ejecutada el 2026-10-20 sobre la pila #126 → #355 → #356 → #372); lo que no se midió se marca **sin medir**. Los umbrales son propuestas del autor hasta que la revisora los confirme. Los responsables también son propuestos, no confirmados.

## 1. Objeto, alcance y política de calidad

El objeto son las tres operaciones del contrato `/api/v1` ([Semana 5](../semana-05/contrato-api.md)) y los requisitos que las gobiernan.

| Operación | Ruta | Permiso | Requisitos |
|---|---|---|---|
| Registro de médico | `POST /api/v1/doctors` | `medicos.registrar` | RF-MED-01 a 04, RNF-MED-02 |
| Asociación de especialidad | `POST /api/v1/doctors/{id}/specialties` | `medicos.asociar_especialidad` | RF-MED-03 a 05, RNF-MED-02 |
| Consulta de disponibilidad | `GET /api/v1/availability` | `medicos.ver_disponibilidad` | RF-MED-06, 07, RNF-MED-03 |

Transversal a las tres: permisos y tenant (RNF-MED-01) y trazabilidad por `trace_id`.

**Fuera del alcance:** la interfaz, las demás operaciones del módulo, la creación de `doctor_schedules` (la propone ASII-09 en el PR #413) y la infraestructura de despliegue.

**Política de calidad.** Una agenda que miente es peor que una agenda que no responde. Por eso el plan prioriza, en este orden: (1) que nadie vea ni cambie datos sin permiso o de otro hospital, (2) que la disponibilidad nunca ofrezca una franja ocupada, (3) que el médico y sus especialidades queden íntegros aunque algo falle a mitad, (4) que cada resultado sea rastreable.

## 2. Roles y responsables

| Rol | Responsabilidad en el plan | Propuesto |
|---|---|---|
| **Autor / dueño del módulo** | Mantiene el plan, corrige defectos, ejecuta las pruebas y las sondas en cada PR | Josué Hicho García (ASII-04) |
| **Revisora SQA** | Confirma umbrales, revisa el plan y las evidencias, firma el cierre | Yamilet (ASII-04) |
| **Dueño de agenda y citas** | Fuente de la tabla `appointments` y de las citas que ocupan franjas | Lis (Citas) |
| **Dueño de base de datos** | Restricciones, migraciones y `doctor_schedules` | Javier (ASII-09) |
| **Dueño de permisos y tenant** | Catálogo RBAC, comprobador de permisos y aislamiento por hospital | María (ASII-02) |

Una métrica o un control sin responsable no cuenta como definido. Cuando el responsable es de otro módulo, el autor **avisa** y registra la fecha; no corrige desde este módulo.

## 3. Métricas de calidad

Cada métrica indica quién responde por ella, a partir de qué valor se actúa, de dónde sale el dato y qué se hace. **Hoy** es la línea base de la Semana 13.

### 3.1 Calidad de registro, asociación y consulta

| ID | Métrica | Umbral | Fuente | Responsable | Acción si se incumple | Hoy |
|---|---|---|---|---|---|---|
| M01 | Pruebas que pasan (`tests/run.php` + `tests/api.php`) | 100 %, 0 fallos | Salida de ambas suites en cada PR | Autor | Bloquea el merge; se corrige antes de revisar | 58 de 58 |
| M02 | Requisitos (7 RF + 3 RNF) con una prueba que falla si se rompen | 10 de 10 | Matriz de trazabilidad de la sección 6 | Autor | Se escribe la prueba faltante antes de cerrar la semana | 7 de 10 (sin prueba: RF-MED-02, RF-MED-06 en rango, RNF-MED-01) |
| M03 | Respuestas con el estado y el `code` del contrato en todos los caminos | 100 % | Sondas de la Semana 13 (P6, P7) y `tests/api.php` | Autor | Se corrige el camino o se enmienda el contrato (PR aparte) | 2 desvíos (H-08) |
| M04 | Franjas ofrecidas que chocan con una cita activa del mismo médico | 0 | Consulta de control sobre `appointments` y la respuesta; sonda P1 | Autor + Lis | Es Mayor: se corrige `SlotPlanner` y se agrega la prueba de cita de 60 min | Falla (H-02) |
| M05 | Errores de unicidad (licencia, asociación) que terminan en 500 en vez de 409 | 0 | Sonda P2 y bitácora del kernel | Autor | Se traduce `23505`/`23000` a su código de contrato | 1 camino (H-05) |

### 3.2 Confiabilidad

| ID | Métrica | Umbral | Fuente | Responsable | Acción si se incumple | Hoy |
|---|---|---|---|---|---|---|
| M06 | Altas con perfil sin especialidad principal o sin auditoría (atomicidad) | 0 | Consulta de integridad sobre `doctor_profiles` y `doctor_specialties` frente a la bitácora; prueba de auditoría caída | Autor | Mayor: se revisa `PdoDoctorStore::transaction`; se repara el dato con aviso a Javier | 0 en pruebas (C12 ✔); sin medir en la base real |
| M07 | Perfiles activos por usuario | ≤ 1 | `SELECT local_user_ref_id, count(*) … HAVING count(*) > 1` | Autor + Javier | Mayor: se bloquea el alta (409 `DOCTOR_ALREADY_REGISTERED`) y se depuran duplicados | Falla en pruebas (H-03, P3) |
| M08 | Asociaciones solapadas de la misma especialidad o con segunda principal | 0 | Restricciones `EXCLUDE` de la migración 000100 y consulta de control | Javier | Mayor: se investiga cómo llegó el dato y se corrige | 0 en la base (la restricción lo impide); la prueba local no la tiene (H-06) |
| M09 | Peticiones a `GET /availability` con error 5xx | < 1 % | Bitácora del `ApiKernel` | Autor | Dos días seguidos por encima: incidente y análisis de causa | Sin medir |
| M10 | Latencia p95 de `GET /availability` | < 500 ms (propuesto) | Medición por `trace_id` en la bitácora, una vez agregada la duración | Autor | Por encima en dos revisiones: se revisa la consulta de citas del día | Sin medir; el umbral lo confirma Yamilet |

### 3.3 Seguridad y trazabilidad

| ID | Métrica | Umbral | Fuente | Responsable | Acción si se incumple | Hoy |
|---|---|---|---|---|---|---|
| M11 | Peticiones con `X-Tenant-ID` ajeno rechazadas con 401/403 (RNF-MED-01) | 100 % | Prueba de kernel con tenant ajeno | Autor + María | Mayor: se bloquea el release del módulo hasta documentar y probar el aislamiento | No hay prueba en el módulo (H-01); la plataforma lo valida con `tenant` y `rbac:` (confirmado por María el 2026-10-20), falta montar el módulo |
| M12 | Rutas de `/api/v1` sin permiso declarado que se despachan | 0 | Prueba que recorre las rutas del `Router` contra `ApiKernel::PERMISSIONS` | Autor | Mayor: la ruta nace denegada (403) y se declara su permiso | 2 rutas devuelven `NULL` (H-04, P5); en la plataforma cada ruta declara su `rbac:medicos.*` |
| M13 | Respuestas, también las de error, con `trace_id` y `X-Trace-Id` | 100 % | `tests/api.php` y bitácora | Autor | Se corrige el camino sin cabecera | 100 % (C07 ✔) |
| M14 | Respuestas de disponibilidad con datos del paciente | 0 | Revisión del contrato frente a `AppointmentReader` | Autor + Lis | Crítico: se retira el campo y se avisa a Citas | 0 (solo horas, RNF-MED-03) |
| M15 | Denegaciones (403) y fallos de RBAC (503) registrados con actor y permiso faltante | 100 % | Bitácora del kernel | María | Se agrega el registro; sin él no se puede auditar el rechazo | Sin medir |

### 3.4 Proceso

| ID | Métrica | Umbral | Fuente | Responsable | Acción si se incumple | Hoy |
|---|---|---|---|---|---|---|
| M16 | PR del módulo con menos de 400 líneas propias | 100 % | `gh pr view` / `git diff --stat origin/develop...HEAD` | Autor | Se parte el PR antes de abrirlo | 100 % (insertadas); #356 pasa de 400 con las borradas |
| M17 | Hallazgos Mayores o Críticos abiertos | 0 al cerrar el ciclo; > 3 repite la revisión | [hallazgos.md](../semana-13/hallazgos.md) | Revisora SQA | No se aprueba la salida; cada uno recibe responsable y fecha | 4 Mayores (H-01 a H-04) |
| M18 | Hallazgos con corrección verificada por una prueba que antes fallaba | 100 % de los corregidos | Prueba + PR de la corrección | Revisora SQA | El hallazgo vuelve a Abierto | 0 corregidos |

## 4. Controles

Un control **preventivo** evita el defecto; uno **detectivo** lo encuentra a tiempo. «Estado» indica si existe hoy (✔), existe con reserva (⚠) o falta (✖).

| ID | Tipo | Control | Umbral | Fuente | Responsable | Acción cuando falla | Estado |
|---|---|---|---|---|---|---|---|
| K01 | Preventivo | Ruta nueva nace denegada: sin permiso declarado responde 403 | 0 rutas abiertas por omisión | Prueba de rutas contra `PERMISSIONS` | Autor | Se declara el permiso o se quita la ruta | ⚠ H-04: lo cubre `rbac:` por ruta al montar el módulo |
| K02 | Preventivo | Falla cerrada: RBAC caído responde 503, sin actor 401, sin permiso 403 | 100 % de casos | `tests/api.php` | Autor + María | Se corrige antes de cualquier release | ✔ |
| K03 | Preventivo | El tenant lo fija la conexión del hospital y el kernel rechaza `X-Tenant-ID` ajeno | 100 % de rechazos | Prueba de tenant ajeno | Autor + María | Se documenta la decisión con ASII-09 y se prueba | ⚠ H-01: lo valida `tenant`/`rbac`; el módulo debe usar la conexión `HOSPITAL` del contexto |
| K04 | Preventivo | Perfil, especialidad principal y auditoría en una sola transacción | 0 altas parciales | Prueba de auditoría caída | Autor | Mayor: se revierte y se revisa `transaction()` | ✔ |
| K05 | Preventivo | La base decide la unicidad: índice único parcial por usuario, `EXCLUDE` de vigencias | 0 duplicados | Migración 000100 | Javier | Se avisa a ASII-09 con el caso | ⚠ la unicidad por usuario solo existe en la base real (H-03, H-06) |
| K06 | Preventivo | Disponibilidad como horario menos intervalos `[inicio, fin)` de citas activas | 0 choques | Prueba de cita de 60 min | Autor + Lis | Se corrige `SlotPlanner` y `AppointmentReader` | ✖ H-02 |
| K07 | Preventivo | Validación de entrada: tipos, JSON malformado, parámetros ausentes → 422 sin guardar | 100 % | Pruebas de `Request` | Autor | Se corrige el camino | ⚠ H-08 |
| K08 | Preventivo | Solo se usan especialidades activas y la vigencia `[desde, hasta)` se respeta | 0 violaciones | Pruebas de `Period` y de la política | Autor | Se corrige la política | ✔ |
| K09 | Preventivo | Prueba de contrato contra PostgreSQL con la migración real, no solo SQLite | Pasa en cada release | Entorno de ASII-09 | Autor + Javier | No se da por buena la persistencia | ✖ H-06 |
| K10 | Preventivo | Puerta de PR: menos de 400 líneas, commits `tipo(scope): descripción (ASII-04)`, suites en 0 fallos | 100 % | `gh`, `git log`, suites | Autor | No se pide revisión | ⚠ H-10 |
| K11 | Detectivo | Bitácora del kernel con `trace_id`, actor, permiso y resultado, sin cuerpo de la petición | 100 % de peticiones | Bitácora | María | Se agrega el campo faltante | ✔ salvo duración |
| K12 | Detectivo | Consultas de control de integridad (M06 a M08) | 0 filas | SQL contra la base | Javier | Se abre incidente con el dato afectado | ✖ sin ejecutar |
| K13 | Detectivo | Reconciliación de agenda: franjas ofrecidas frente a `appointments` | 0 choques | Consulta de control con Citas | Lis | Se revisa M04 y se avisa al autor | ✖ |
| K14 | Detectivo | Sondas reproducibles de la Semana 13 (P1 a P10) en cada PR del módulo | Cada sonda cambia de «falla» a «pasa» al corregirse | [`sondas.php`](../semana-13/evidencia/sondas.php) | Autor | El hallazgo no se cierra | ✔ |
| K15 | Detectivo | Revisión de la salida con la checklist de 15 ítems | 0 Mayores abiertos | [Checklist](../semana-13/README.md) | Revisora SQA | Resultado «Repetir revisión» | ✔ |

## 5. Riesgos

Probabilidad e impacto: Baja, Media, Alta. Nivel: 🔴 Crítico, 🟠 Alto, 🟡 Medio. Las referencias H-nn son de la Semana 13.

| ID | Riesgo | Prob. | Impacto | Nivel | Controles | Métricas | Responsable | Acción si ocurre |
|---|---|---|---|---|---|---|---|---|
| R01 | Un administrador registra o consulta médicos de otro hospital (H-01) | Media | Alto | 🔴 | K03, K02 | M11 | Autor + María | Se suspende el acceso, se revisa la bitácora por `trace_id` y se avisa al hospital afectado |
| R02 | La recepción ve libre una franja ocupada y descubre el choque al reservar (H-02) | Alta | Alto | 🔴 | K06, K13 | M04 | Autor + Lis | Se corrige el planificador; entre tanto la reserva la protege la restricción de la base |
| R03 | Un usuario con dos perfiles de médico activos (H-03, H-05) | Media | Alto | 🟠 | K05, K12 | M05, M07 | Autor + Javier | Se depuran los perfiles y se bloquea el alta con 409 |
| R04 | Una ruta nueva queda abierta por no tener permiso declarado (H-04) | Media | Crítico | 🔴 | K01, K15 | M12 | Autor | Se deniega la ruta y se revisan los accesos desde su alta |
| R05 | La lógica pasa en SQLite pero falla en PostgreSQL (`substr` sobre `timestamptz`, ids) (H-06) | Media | Alto | 🟠 | K09 | M01, M09 | Autor + Javier | Se bloquea el release y se adapta la consulta |
| R06 | `doctor_schedules` no llega y los médicos quedan sin horario, indistinguibles de una agenda llena (H-09) | Media | Medio | 🟡 | K07, K14 | M02 | Autor + Javier | Se informa «sin horario» como estado propio y se registra la dependencia |
| R07 | Un fallo a mitad del alta deja un médico sin especialidad principal o sin auditoría | Baja | Alto | 🟠 | K04, K12 | M06 | Autor | Se revierte, se repara el dato y se revisa la transacción |
| R08 | La consulta de disponibilidad expone datos del paciente | Baja | Crítico | 🟠 | K07, K15 | M14 | Autor + Lis | Se retira el campo, se revisan respuestas ya emitidas y se avisa a Citas |
| R09 | Una especialidad desactivada o una vigencia vencida se sigue usando | Baja | Medio | 🟡 | K08 | M01 | Autor | Se corrige la política y se revisan las asociaciones creadas |
| R10 | El comprobador de permisos cae y el módulo queda inutilizable | Media | Medio | 🟡 | K02, K11 | M09, M15 | María | Falla cerrada (503): se prefiere no responder a responder sin permiso |
| R11 | Un PR supera el tamaño o lleva atribución indebida en commits (H-10, H-11) | Media | Medio | 🟡 | K10 | M16 | Autor | Se parte el PR; la reescritura de historia queda como decisión del autor |

## 6. Trazabilidad requisito → control → evidencia

| Requisito | Qué garantiza | Control | Métrica | Evidencia hoy |
|---|---|---|---|---|
| RF-MED-01 | Registro de médico | K07, K04 | M01, M03 | `tests/run.php` |
| RF-MED-02 | Licencia única y un perfil activo por usuario | K05 | M05, M07 | ✖ H-03, H-05 |
| RF-MED-03 | Solo especialidades activas | K08 | M01 | Prueba `SPECIALTY_INACTIVE` |
| RF-MED-04 | Vigencia `[desde, hasta)` | K08 | M01 | Pruebas de `Period` |
| RF-MED-05 | Sin solapes ni segunda principal | K05, K09 | M08 | ⚠ H-06 |
| RF-MED-06 | Franjas por fecha, rango de 14 días y `generated_at` | K06, K13 | M04 | ✖ H-02, H-07 |
| RF-MED-07 | Médico sin horario informado, no oculto | K07 | M02 | ⚠ H-09 |
| RNF-MED-01 | Permisos y tenant | K01, K02, K03 | M11, M12 | ✖ H-01, H-04 |
| RNF-MED-02 | Atomicidad | K04, K12 | M06 | ✔ C12 |
| RNF-MED-03 | Sin datos del paciente | K06, K15 | M14 | ✔ C13 (solo horas) |

## 7. Cadencia, umbrales de salida y mantenimiento

| Momento | Qué se ejecuta | Quién | Producto |
|---|---|---|---|
| Cada PR del módulo | K10, M01, M16 y las sondas K14 | Autor | Salida pegada en la descripción del PR |
| Cada release | M04, M06 a M08, M11, M12 y K09 | Autor, Javier y María | Registro del resultado por métrica |
| Cierre de ciclo | M17 y M18, checklist K15 | Revisora SQA | Resultado: Aprobado / Aprobado con cambios / Repetir |

**Salida del plan:** M01 al 100 %, M04 y M11 y M12 cumplidos, cero Mayores o Críticos abiertos o con aceptación escrita de la revisora, y todo valor «sin medir» con fecha de medición.

**Mantenimiento:** un umbral se cambia solo con motivo escrito y firma de la revisora; una métrica nueva nace con responsable, umbral, fuente y acción, o no entra. Este plan se revisa cuando cambie el contrato `/api/v1` o llegue `doctor_schedules`.

## 8. Pendientes y supuestos

- Los umbrales M09 y M10 son propuestas sin base de medición; se confirman con datos reales.
- M06 a M08 y K12 requieren ejecutar consultas contra PostgreSQL, que no se hizo en la Semana 13.
- La duración en la bitácora (M10) y el registro de denegaciones (M15) deben comprobarse con María antes de comprometer el umbral.
- Los responsables son propuestos; el autor avisa a Lis, Javier y María antes de que figuren como confirmados.
- El plan no corrige ningún defecto: esos cambios se hacen en PR aparte, uno por hallazgo, con la prueba que antes fallaba.

### Actualización del 2026-10-20: dependencias confirmadas
- **RBAC (María, ASII-02):** los permisos `medicos.registrar`, `medicos.asociar_especialidad` y `medicos.ver_disponibilidad` ya están en `config/rbac.php` de `develop`. Cada ruta usa `tenant`, `jwt.auth` y `rbac:<permiso>`; RBAC valida el permiso y la membresía vigente, y el módulo no duplica esa matriz. Los códigos de error cambian a los de RBAC (`AUTH_REQUIRED`, `RBAC_DENIED`, `RBAC_UNAVAILABLE`, `TENANT_REQUIRED`, `TENANT_MISMATCH`) y el contrato debe actualizarse.
- **`doctor_schedules` (Javier, issue #24):** ASII-04 respondió `A-1: (b)` (la migración la mantiene el módulo de verificación de BD) y `A-3: (b)` (trigger de integridad en la misma migración), con el número `000900`. La integración del PR #413 depende del grupo antes del 2026-10-22 12:00. Con la tabla, R06 y K12 pasan a medirse.
- **Pendiente:** montar el módulo en Laravel con la conexión `HOSPITAL` (K03, M11, M12) es trabajo de integración posterior; hasta entonces M11 y M12 siguen sin prueba propia.
