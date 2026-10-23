# Médicos, especialidades y disponibilidad — Semana 16: integración y primera entrega funcional

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-04 · **Entrega:** el micro-HIS de médicos deja de ser PHP aislado y queda expuesto en el API de Laravel, con adaptador PDO compatible con PostgreSQL y pruebas de contrato HTTP.

> **Estado: parcial.** La integración de backend está hecha y probada con dobles y SQLite. **No se ejecutó contra PostgreSQL** (el entorno local no tiene `pdo_pgsql` ni servidor) y **no hay interfaz Vue**. Ambas cosas siguen pendientes y no se dan por cumplidas (sección 6).

## 1. Qué se entrega

| PR | Alcance | Estado |
|---|---|---|
| [#1038](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1038) | `PdoDoctorStore` compatible con el esquema HOSPITAL de PostgreSQL | Borrador |
| [#1039](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1039) | Rutas, controlador, provider, actor local y lector de `doctor_schedules` (apilado sobre #1038) | Borrador |
| Este PR | Documentación y evidencia de la semana (apilado sobre #1039) | Borrador |

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
| RNF-MED-02 | La transacción del alta (perfil + vínculo + auditoría) se conserva en PostgreSQL; sin ejecutar allí todavía |

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

Entorno: PHP 8.3.33, PHPUnit 11.5.55, adaptadores PDO sobre SQLite en memoria y dobles InMemory. Las 10 pruebas HTTP cubren: alta con auditoría del actor local, asociación, disponibilidad, códigos de error del módulo, 401 en las tres rutas, permiso propio por operación, tenant ajeno, actor sin referencia local, base caída y base inexistente (503, no 500). Las pruebas del adaptador agregan: id uuid v4, lectura de citas en otra zona horaria y lectura de `doctor_schedules` (vigencia, día sin franjas, médico sin horario, horario inactivo).

## 5. Estado de los defectos de la Semana 15

| ID | Estado tras esta semana |
|---|---|
| D-03 (tenant ajeno acepta 201) | Cubierto en la ruta de Laravel por `tenant` (prueba `TENANT_MISMATCH`); sigue abierto en el micro-HIS aislado |
| D-05 (ruta sin permiso) | Cubierto: cada ruta declara `rbac:` |
| D-01 (licencia con espacios) · D-02 (citas fuera de la rejilla) · D-04 (`specialty` ausente) | **Abiertos**; son de dominio y van en PR aparte |

## 6. Pendientes y riesgos

| # | Pendiente | Efecto |
|---|---|---|
| 1 | Prueba E2E contra PostgreSQL 16 (esquema HOSPITAL migrado) | Hasta entonces el adaptador está probado solo en SQLite |
| 2 | `slot_minutes` por horario (issue #207 con Citas) | Las franjas se parten en bloques fijos de 30 min |
| 3 | Zona horaria del hospital | Hoy sale de `config('app.timezone')`; confirmar el valor real |
| 4 | Interfaz Vue (hay prototipo de la Semana 11) | Sin pantalla integrada |
| 5 | Usuarios sin `local_user_refs` | Responden 403 hasta que la sincronización los provisione |

**Semana 17** (seguridad y despliegue) depende de lo anterior: la matriz de amenazas y las pruebas de permisos pueden avanzar; el despliegue final queda bloqueado por el entorno.

[Declaración de uso de IA](./DECLARACION_IA.md)
