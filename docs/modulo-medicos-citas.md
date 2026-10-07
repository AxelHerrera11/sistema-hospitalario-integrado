# ASII-03 - Medicos, especialidades y citas

Responsable: Gerson Orellana  
Rama de trabajo inicial: `feature/asii-03-medicos-citas-gerson`  
Rama de correcciones review: `feature/asii-03-correcciones-review`  
Area asignada: medicos, especialidades y citas  
Modulos originales: 4 y 5

## 1. Alcance del modulo

El modulo administra la disponibilidad funcional de los medicos dentro de cada hospital, el catalogo de especialidades y el ciclo basico de citas medicas. Su proposito es permitir que recepcion y personal medico consulten especialistas disponibles, agenden consultas para pacientes existentes, actualicen datos relevantes de la cita y registren estados como confirmada, completada, cancelada o no asistio.

El modulo no reemplaza el expediente clinico ni las notas SOAP. La cita puede servir como punto de entrada al flujo clinico, pero la atencion medica detallada pertenece a los modulos de expediente, SOAP, diagnosticos y signos vitales.

## 2. Actores

| Actor | Responsabilidad dentro del modulo |
|---|---|
| Recepcionista | Agenda, edita, confirma y cancela citas para pacientes existentes. Consulta medicos y especialidades. |
| Medico | Consulta su agenda y el detalle de sus citas. Puede marcar citas como completadas o no asistidas segun el flujo acordado. |
| Admin | Gestiona especialidades y datos administrativos de medicos dentro del hospital. |
| Paciente | No accede directamente al sistema en esta version, pero es el beneficiario del agendamiento. |

## 3. Casos de uso

| Codigo | Caso de uso | Actor principal | Resultado esperado |
|---|---|---|---|
| CU-01 | Consultar especialidades | Recepcionista, Medico, Admin | Lista filtrable de especialidades del hospital actual. |
| CU-02 | Gestionar especialidad | Admin | Especialidad creada o actualizada con nombre y descripcion. |
| CU-03 | Consultar medicos | Recepcionista, Medico, Admin | Lista de medicos con usuario, especialidad, colegiado y telefono. |
| CU-04 | Gestionar medico | Admin | Perfil medico asociado a un usuario existente y a una especialidad. |
| CU-05 | Consultar agenda | Recepcionista, Medico | Citas filtradas por fecha, medico, paciente, especialidad o estado. |
| CU-06 | Crear cita | Recepcionista | Cita registrada para paciente, medico, especialidad, fecha, duracion y motivo. |
| CU-07 | Editar cita | Recepcionista | Datos reprogramables actualizados sin romper reglas de estado. |
| CU-08 | Cancelar cita | Recepcionista | Cita marcada como cancelada con nota opcional. |
| CU-09 | Completar o marcar inasistencia | Medico, Recepcionista | Cita cerrada como completada o no asistio. |

## 4. Requerimientos funcionales

| Codigo | Requerimiento | Criterio de aceptacion |
|---|---|---|
| RF-01 | El sistema debe listar especialidades del hospital actual. | La respuesta solo incluye registros del `X-Tenant-ID` de la peticion. |
| RF-02 | El sistema debe permitir crear y editar especialidades. | Solo usuarios con permiso `medicos.gestionar` pueden hacerlo. |
| RF-03 | El sistema debe listar medicos con su usuario y especialidad. | La lista permite busqueda por nombre, email, colegiado o especialidad. |
| RF-04 | El sistema debe crear perfiles medicos asociados a usuarios existentes. | El usuario, la especialidad y el numero de colegiado deben pertenecer al hospital actual; el usuario debe tener rol `Medico`. |
| RF-05 | El sistema debe listar citas por filtros operativos. | Se puede filtrar por fecha, medico, paciente, especialidad y estado. |
| RF-06 | El sistema debe crear citas para pacientes existentes. | Valida paciente, medico, especialidad, fecha futura, duracion, estado inicial y motivo. |
| RF-07 | El sistema debe prevenir estados invalidos. | Solo se aceptan transiciones controladas: `pendiente` a `confirmada`, `cancelada` o `no_asistio`; `confirmada` a `completada`, `cancelada` o `no_asistio`; estados cerrados no regresan a estados abiertos. |
| RF-08 | El sistema debe permitir cancelar citas. | Solo usuarios con permiso `citas.cancelar` pueden cancelar citas en estado abierto y la respuesta devuelve el estado actualizado. |
| RF-09 | El sistema debe prevenir traslapes de agenda. | Si el medico ya tiene una cita abierta que cruza con el horario solicitado, la API responde 422. |

## 5. Requerimientos no funcionales

| Codigo | Requerimiento | Criterio de aceptacion |
|---|---|---|
| RNF-01 | Seguridad por rol y permiso. | Todas las rutas privadas usan `tenant`, `auth.jwt` y `permission:*`. |
| RNF-02 | Aislamiento por hospital. | No se filtra `tenant_id` manualmente; se usa el trait `BelongsToTenant` ya aplicado en los modelos. |
| RNF-03 | Compatibilidad PostgreSQL. | Las busquedas usan `whereSearch()` para evitar problemas con mayusculas y acentos. |
| RNF-04 | Validacion defensiva. | IDs, enums, fechas y duraciones se validan antes de consultar o guardar. |
| RNF-05 | Respuestas consistentes. | La API responde JSON con datos cargados y mensajes claros de error. |

## 6. Principio SOLID aplicado

Principio aplicado: Single Responsibility.

Cada controlador del modulo tendra una responsabilidad clara: `SpecialtyController` gestiona el catalogo de especialidades, `DoctorController` gestiona perfiles medicos y `AppointmentController` gestiona el ciclo de citas. Las reglas de entrada viven en FormRequests y las reglas de negocio de citas viven en `AppointmentService`, para que el controlador no mezcle validacion HTTP con transiciones, traslapes y coherencia medico-especialidad.

## 7. Vista arquitectonica

| Capa | Responsabilidad |
|---|---|
| UI Vue | Pantallas para listar, crear, editar y cambiar estado de citas, medicos y especialidades. |
| Router Vue | Rutas protegidas por `meta.requiresAuth` y `meta.permission`. |
| API Laravel | Endpoints REST bajo `/api/v1`, protegidos por tenant, JWT y permisos. |
| Validacion | FormRequests para IDs, enums, fechas, duracion y unicidad por hospital. |
| Servicio de dominio | `AppointmentService` para transiciones, traslapes, fecha futura y coincidencia medico-especialidad. |
| Modelos Eloquent | `Specialty`, `Doctor`, `Appointment`, `Patient` y `User` con relaciones. |
| Persistencia | PostgreSQL con indices existentes por tenant, medico, paciente, fecha y estado. |

## 8. Contrato API preliminar

Todas las rutas requieren `X-Tenant-ID`. Las rutas privadas tambien requieren `Authorization: Bearer <token>`.

### Especialidades

| Metodo | Ruta | Permiso | Proposito |
|---|---|---|---|
| GET | `/api/v1/specialties` | `medicos.ver` | Listar especialidades. |
| POST | `/api/v1/specialties` | `medicos.gestionar` | Crear especialidad. |
| PUT | `/api/v1/specialties/{specialty}` | `medicos.gestionar` | Actualizar especialidad. |

Payload de creacion o edicion:

```json
{
  "name": "Cardiologia",
  "description": "Atencion cardiovascular"
}
```

### Medicos

| Metodo | Ruta | Permiso | Proposito |
|---|---|---|---|
| GET | `/api/v1/doctors` | `medicos.ver` | Listar medicos. |
| POST | `/api/v1/doctors` | `medicos.gestionar` | Crear perfil medico. |
| PUT | `/api/v1/doctors/{doctor}` | `medicos.gestionar` | Actualizar perfil medico. |

Payload de creacion:

```json
{
  "user_id": 10,
  "specialty_id": 3,
  "license_number": "MED-SM-01234",
  "phone": "5555-1111"
}
```

Reglas de negocio:

- `status` no se acepta en `POST /appointments` ni en `PUT /appointments/{appointment}`. Toda cita nueva inicia como `pendiente`.
- El cambio de estado se hace por `/appointments/{appointment}/status` y solo permite transiciones abiertas.
- La cancelacion se hace por `/appointments/{appointment}/cancel`; no se puede cancelar una cita `completada`, `cancelada` o `no_asistio`.
- `scheduled_at` debe ser futuro al crear o reprogramar.
- El medico no puede tener otra cita abierta en un horario traslapado.

### Citas

| Metodo | Ruta | Permiso | Proposito |
|---|---|---|---|
| GET | `/api/v1/appointments` | `citas.ver` | Listar citas con filtros. |
| POST | `/api/v1/appointments` | `citas.crear` | Crear cita. |
| PUT | `/api/v1/appointments/{appointment}` | `citas.editar` | Reprogramar o editar cita. |
| POST | `/api/v1/appointments/{appointment}/cancel` | `citas.cancelar` | Cancelar cita. |
| POST | `/api/v1/appointments/{appointment}/status` | `citas.editar` | Cambiar estado controlado. |

Payload de creacion:

```json
{
  "patient_id": 15,
  "doctor_id": 2,
  "specialty_id": 4,
  "scheduled_at": "2026-10-05 09:30:00",
  "duration_min": 30,
  "reason": "Consulta de seguimiento",
  "notes": "Paciente refiere dolor recurrente"
}
```

## 9. Plan de rama, PR y evidencia

| Elemento | Valor |
|---|---|
| Rama | `feature/asii-03-medicos-citas-gerson` |
| Base del PR | `develop` |
| Titulo sugerido | `ASII-03: medicos, especialidades y citas - Gerson` |
| Evidencia inicial | Este documento, capturas de endpoints, capturas de UI y comandos ejecutados. |

Validaciones antes del PR:

```powershell
php artisan test
npm run build
```

Validacion final recomendada con PostgreSQL:

```powershell
php artisan test --configuration=phpunit.pgsql.xml
```

## 10. Avance implementado

| Fecha | Avance | Evidencia |
|---|---|---|
| 2026-10-01 | Configuracion local del proyecto con dependencias Composer/npm, `.env`, SQLite, llaves Laravel/JWT y seeders demo. | `composer install`, `npm install`, `php artisan key:generate`, `php artisan jwt:secret --force`, `php artisan migrate:fresh --seed`. |
| 2026-10-01 | API inicial del modulo: especialidades, medicos y citas. | Controladores `SpecialtyController`, `DoctorController`, `AppointmentController`; rutas bajo `/api/v1`. |
| 2026-10-01 | UI inicial del modulo en Vue. | Pantalla `/medicos-citas` con listas y formularios basicos. |
| 2026-10-01 | Pruebas especificas del modulo. | `tests/Feature/MedicalSchedulingTest.php`. |
| 2026-10-01 | Rediseño operativo inspirado en Clinical Precision UI. | Tabs separados para Agenda, Medicos y Especialidades; KPIs de citas, filtros visuales, drawers de creacion/edicion y salto desde especialidad hacia agenda filtrada. |
| 2026-10-06 | Correcciones de revision tecnica #19. | `AppointmentService`, FormRequests de citas/medicos, pruebas de transiciones, traslapes, fecha pasada, permisos y selector/paginacion UI. |

Validaciones ejecutadas:

```powershell
php artisan route:list --path=api
php artisan test
npm run build
```

Resultado tras PR inicial:

- `php artisan test`: 21 pruebas pasaron, 1 omitida por depender de PostgreSQL.
- `npm run build`: compilacion Vite correcta.

Evidencia de correccion #19:

- `php artisan test tests\Feature\MedicalSchedulingTest.php`: 10 pruebas pasaron, 46 aserciones.
- `npm run build`: compilacion Vite correcta.

## 11. Pendientes proximos

- Selector de usuarios para perfil medico cuando el endpoint de usuarios (#15) este disponible en la UI.
- Agregar vistas de detalle profundas para perfil medico y auditoria de cambios de citas.
- Integrar `AuditLogger` para crear, editar y cancelar citas cuando exista el servicio comun de auditoria (#14).
- Validar en PostgreSQL con `php artisan test --configuration=phpunit.pgsql.xml`.
- Tomar capturas de pantalla para adjuntar al issue o PR.
- Abrir PR hacia `develop` cuando el avance sea revisable por el lider tecnico.
