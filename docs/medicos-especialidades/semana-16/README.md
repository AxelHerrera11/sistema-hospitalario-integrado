# Médicos, especialidades y disponibilidad — Semana 16: integración y primera entrega funcional

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** el micro-HIS de médicos deja de ser PHP aislado y queda expuesto en el API de Laravel, con adaptador PDO compatible con PostgreSQL y pruebas de contrato HTTP.

> **Estado: entrega funcional, con dos reservas.** El API, el adaptador PostgreSQL, los defectos de la Semana 15, `slot_minutes`, la zona horaria del hospital y la pantalla Vue están hechos y probados. **La pantalla no se abrió en un navegador** (solo build y pruebas de utilidades; sin capturas) y **el despliegue final** de la Semana 17 sigue bloqueado por el entorno (sección 6).

## 1. Qué se entrega

| PR | Alcance | Estado |
|---|---|---|
| [#1038](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1038) | `PdoDoctorStore` compatible con el esquema HOSPITAL de PostgreSQL | Borrador |
| [#1039](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1039) | Rutas, controlador, provider, actor local y lector de `doctor_schedules` (apilado sobre #1038) | Borrador |
| [#1042](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1042) | Defectos D-01, D-02 y D-04 de la Semana 15 | Borrador |
| [#1043](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1043) | Prueba de base de datos contra PostgreSQL 16 | Borrador |
| [#1044](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1044) | Horario partido con el `slot_minutes` de `doctor_schedules` | Borrador |
| [#1045](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1045) | Pantalla Vue `/medicos` | Borrador |
| [#1046](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1046) | Citas leídas en la zona horaria del hospital del tenant | Borrador |
| Este PR | Documentación y evidencia de la semana | Borrador |

Los PR forman una cadena apilada (#1038 → #1039 → #1042 → #1043 → #1044 → #1045 → #1046); cada uno apunta a la rama del anterior para que su diff sea revisable (menos de 400 líneas) y se reorienta a `develop` cuando el previo se integra. Este PR de documentación está apilado sobre #1039.

### 1.3 Piezas posteriores (#1042 a #1046)

| PR | Qué cambia | Por qué |
|---|---|---|
| #1042 | D-01: la licencia se normaliza (`trim` + mayúsculas). D-02: `AppointmentReader` entrega intervalos `[inicio, fin)` y `SlotPlanner` bloquea todo bloque que solape; una cita que cruza la medianoche ocupa su parte de cada día. D-04: `GET /availability` sin `specialty` responde 422 `PAYLOAD_INVALID` | Eran los defectos abiertos de la Semana 15; D-02 hacía ofrecer franjas ya tomadas |
| #1043 | `DoctorsPostgresTest`: alta, auditoría, atomicidad, asociación y disponibilidad sobre el esquema HOSPITAL migrado | El adaptador solo se había probado en SQLite |
| #1044 | `PdoScheduleProvider` entrega `[inicio, fin, slot_minutes]` y `SlotPlanner` lo usa por franja | Los horarios de 20 o 15 minutos se partían en bloques de 30 |
| #1045 | `DoctorsPage.vue` y utilidades puras: consulta de disponibilidad, alta y asociación, con formularios según permiso | Parte visible de la entrega |
| #1046 | La zona de lectura de citas es `hospitals.timezone` del tenant; si no es un identificador IANA válido, la de la aplicación | El servidor está en UTC; «08:00» debe ser la hora local del hospital |

### 1.1 Adaptador PDO (#1038)

| Cambio | Motivo |
|---|---|
| Ids de perfil uuid v4 | `doctor_profile_id` es `uuid`; el adaptador generaba `doc-xxxx`, que PostgreSQL rechaza |
| Auditoría con las columnas reales de `local_access_audits` (`correlation_id`, `actor_user_ref_id`, `module`, `action`, `resource_type`, `resource_id`) | Antes insertaba `event`, `actor_id`, `detail`, que no existen en la migración 000700 |
| `is_primary` enviado como booleano | La columna es `boolean` en PostgreSQL |
| Citas ocupadas por rango de `starts_at`, convertidas a la zona del hospital | `starts_at` es `timestamptz`; el `substr` sobre texto solo servía en SQLite |

### 1.2 API en Laravel (#1039)

| Método | Ruta | Permiso | Descripción |
|---|---|---|---|
| POST | `/api/v1/doctors` | `medicos.registrar` | Alta con especialidad principal |
| POST | `/api/v1/doctors/{doctor}/specialties` | `medicos.asociar_especialidad` | Asociación con vigencia |
| GET | `/api/v1/availability?specialty=&date=` | `medicos.ver_disponibilidad` | Franjas libres por especialidad y fecha |

Las tres pasan por `tenant` y `jwt.auth`; cada una declara su propio `rbac:`, de modo que una ruta nueva no nace abierta (cierra D-05 de la [Semana 15](../semana-15/README.md)). El tenant lo valida el middleware de la plataforma (cierra D-03).

| Componente | Responsabilidad |
|---|---|
| `DoctorApiController` | Sin reglas: arma la petición del módulo y delega en su `Router`, que conserva los códigos del [contrato de la Semana 5](../semana-05/contrato-api.md) |
| `HospitalActors` | Traduce el usuario autenticado (CENTRAL) a `local_user_refs.local_user_ref_id`, que es lo que exigen la auditoría y `doctor_profiles`. Sin referencia activa: 403 `ACTOR_NOT_PROVISIONED` y no escribe |
| `MedicosServiceProvider` | Cablea el adaptador PDO sobre la conexión `hospital`; zona horaria de `config('app.timezone')` |
| `PdoScheduleProvider` | Lee `doctor_schedules` (migración 000900) en lugar del horario por configuración |

Errores: 401 sin token · 403 `RBAC_DENIED`, `TENANT_MISMATCH`, `ACTOR_NOT_PROVISIONED` · 404, 409 y 422 del módulo · 503 `DOCTOR_STORAGE_UNAVAILABLE` si la base no responde.

## 2. Qué necesitan los demás módulos de este

Verificado contra las migraciones y los documentos de `develop`. **No existe un contrato escrito de Expediente hacia Médicos**: su dependencia sale de los triggers de las migraciones.

| Módulo | Qué toma de Médicos | Dónde consta |
|---|---|---|
| Citas (ASII-05) | `appointments.doctor_profile_id` es FK; el trigger exige la especialidad activa en `starts_at`. Espera además slots derivados de `slot_minutes` | Migración 000300, `docs/asii-05-citas/contrato-api.md`, issue #207 |
| Admisión (ASII-07) | `care_assignments` apunta a `doctor_profile_id` y `specialty_id`; trigger de especialidad activa | Migración 000200, `docs/admision-camas/semana-01` |
| Expediente (ASII-10) | Notas y signos vitales validan que el médico exista y tenga `care_assignments` | Migraciones 000400 y 000401 |
| Signos vitales y Alertas | `doctor_profiles` (con `care_assignments`) y `local_user_refs` | `docs/signos-vitales/semana-04`, `docs/alertas-criticas/semana-04` |
| Base de datos (ASII-09) y RBAC | `doctor_schedules` (entidad 28, aprobada por ASII-04 el 20-oct-2026) y permisos `medicos.*` | `docs/verificacion-postgresql/doctor-schedules/`, `config/rbac.php` |

Lo que más pesa: (1) un `doctor_profile_id` uuid real, creado por el alta; (2) las especialidades vigentes en `doctor_specialties`, contra las que validan todos los triggers; (3) la disponibilidad calculada, que solo necesita Citas.

## 3. Trazabilidad RF/RNF

| Requisito | Cómo queda cubierto en esta semana |
|---|---|
| RF-MED-01, 03, 05 | Alta y asociación expuestas por HTTP; reglas del dominio intactas |
| RF-MED-06, 07 | Disponibilidad con horario real de `doctor_schedules`; sin horario se informa `SCHEDULE_NOT_CONFIGURED` |
| RNF-MED-01 | Permiso por operación, tenant y actor local; falla cerrada ante RBAC o base caídos |
| RNF-MED-02 | La transacción del alta (perfil + vínculo + auditoría) se verificó en PostgreSQL: si la auditoría falla, no queda perfil ni vínculo (#1043) |

## 4. Evidencia ejecutada

```bash
# php vendor/bin/phpunit tests/Feature/Medicos
# resultado: OK (10 tests, 38 assertions)
# php vendor/bin/phpunit tests/Feature/Medicos tests/Feature/Rbac tests/Feature/Ocupacion
# resultado: OK (66 tests, 3946 assertions)
# php micro-his/medicos-especialidades/tests/run.php
# resultado: Todas las pruebas pasaron.
# php micro-his/medicos-especialidades/tests/core.php
# resultado: Todas las pruebas pasaron.
# php artisan route:list --path=api/v1
# resultado: las tres rutas de médicos registradas (DoctorApiController)
```

Evidencia agregada por los PR posteriores (se ejecutó en la rama de cada uno):

```bash
# php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit tests/Database/Hospital/DoctorsPostgresTest.php   [PostgreSQL 16]
# resultado: OK (7 tests, 24 assertions)
# php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit tests/Database/Hospital/DoctorScheduleIntegrityTest.php
# resultado: OK (16 tests, 135 assertions)
# php -d extension=pdo_pgsql -d extension=pgsql vendor/bin/phpunit --testsuite Database
# resultado: 135 tests, 1 falla en tests/Database/Central/AuditChainIntegrityTest.php (escritores concurrentes de CENTRAL),
#            archivo ajeno a este módulo, no investigado; confirmar en CI
# php vendor/bin/phpunit tests/Feature/Medicos
# resultado: OK (11 tests, 42 assertions)
# node <archivo de pruebas de utilidades de la pantalla, en la carpeta modules del frontend de médicos>
# resultado: OK: aserciones de la pantalla de médicos aprobadas
# npm run build
# resultado: ✓ 130 modules transformed; DoctorsPage-B-lfS94Z.js 8.91 kB
```

PostgreSQL: contenedores efímeros `postgres:16` en los puertos de la CI (55431 y 55433) con `btree_gist`, migraciones HOSPITAL reales y PHP con `pdo_pgsql`.

Entorno de las pruebas anteriores: PHP 8.3.33, PHPUnit 11.5.55, adaptadores PDO sobre SQLite en memoria y dobles InMemory. Las 10 pruebas HTTP cubren: alta con auditoría del actor local, asociación, disponibilidad, códigos de error del módulo, 401 en las tres rutas, permiso propio por operación, tenant ajeno, actor sin referencia local, base caída y base inexistente (503, no 500). Las pruebas del adaptador agregan: id uuid v4, lectura de citas en otra zona horaria y lectura de `doctor_schedules` (vigencia, día sin franjas, médico sin horario, horario inactivo).

## 5. Estado de los defectos de la Semana 15

| ID | Estado tras esta semana |
|---|---|
| D-03 (tenant ajeno acepta 201) | Cubierto en la ruta de Laravel por `tenant` (prueba `TENANT_MISMATCH`); sigue abierto en el micro-HIS aislado |
| D-05 (ruta sin permiso) | Cubierto: cada ruta declara `rbac:` |
| D-01 (licencia con espacios) | Corregido en #1042: se normaliza al registrar. Las licencias ya guardadas sin normalizar no se migran |
| D-02 (citas fuera de la rejilla) | Corregido en #1042: franjas por intervalo `[inicio, fin)` |
| D-04 (`specialty` ausente) | Corregido en #1042: 422 `PAYLOAD_INVALID` |

## 6. Pendientes y riesgos

| # | Pendiente | Efecto |
|---|---|---|
| 1 | Revisión visual de la pantalla en navegador (escritorio y móvil) y capturas | Solo se verificó con build y pruebas de utilidades |
| 2 | Formato de los slots que espera Citas (issue #207) | Se entregan franjas `HH:MM` partidas por `slot_minutes`; falta confirmar con Citas que ese formato les sirve |
| 3 | Falla de `AuditChainIntegrityTest` en la suite `Database` | Ajena al módulo; confirmar en CI si es del entorno Windows |
| 4 | Usuarios sin `local_user_refs` | Responden 403 hasta que la sincronización los provisione |
| 5 | Lectura real de `hospitals.timezone` desde `TenantMiddleware` | Se probó con un doble del tenant; la columna es `NOT NULL` en CENTRAL |
| 6 | Licencias ya guardadas sin normalizar | La corrección de D-01 actúa en el alta; no migra datos existentes |

**Semana 17** (seguridad y despliegue) depende de lo anterior: la matriz de amenazas y las pruebas de permisos pueden avanzar; el despliegue final queda bloqueado por el entorno.

[Declaración de uso de IA](./DECLARACION_IA.md)
