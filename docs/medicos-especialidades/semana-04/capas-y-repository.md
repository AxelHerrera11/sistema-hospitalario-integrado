# Capas, responsabilidades y contrato del Repository

## Responsabilidades por capa

| Capa | Clases | Responsabilidad | No hace |
|---|---|---|---|
| Entrada | `public/index.php`, `app.php` | Arma la `Request` desde HTTP, elige el adaptador (PDO o InMemory) y emite la respuesta. | Reglas, SQL |
| Presentation (MVC) | `Router`, `DoctorController`, `Request`, `JsonView` | Ruta → acción; el controlador lee campos (la `Request` rechaza tipos incorrectos con 422), llama al caso de uso y traduce excepciones a estado HTTP; la vista serializa a JSON. | SQL, reglas de negocio |
| Application (modelo) | `RegisterDoctor`, `AssociateSpecialty`, `QueryAvailability`, puertos y `DoctorRepository` | Orquesta cada caso de uso contra sus puertos. | HTTP, PDO |
| Domain | `Period`, `SpecialtyLink`, `SpecialtyAssignmentPolicy`, `SlotPlanner`, `DoctorRuleViolation` | Vigencias, especialidad duplicada, principal única, franjas libres. | Todo lo externo |
| Persistence | `PdoDoctorStore`, `InMemoryDoctorRepository`, `InMemoryAppointmentReader`, `ConfigScheduleProvider` | Implementan los puertos; PDO con sentencias preparadas y transacción. | Reglas, HTTP |

El controlador no decide reglas: `DoctorRuleViolation` ya trae el estado sugerido por el dominio, y el controlador solo lo pasa a la vista.

## Objetos reutilizables

- `JsonView` y `Request` no conocen el módulo: sirven para cualquier controlador del micro-HIS.
- `Period` y `SlotPlanner` no tienen dependencias; Citas o Admisión podrían reutilizarlos para vigencias y franjas.
- `InMemoryDoctorRepository` sirve como doble para los módulos que consumen médicos mientras su integración real no exista.

## Contrato `DoctorRepository` (Application)

```php
interface DoctorRepository extends DoctorProfileStore, DoctorSpecialtyStore, SpecialtyCatalog, DoctorsBySpecialty
{
}
```

| Método | Puerto | Garantía que debe cumplir cada adaptador |
|---|---|---|
| `activeSpecialtyId(code)` | `SpecialtyCatalog` | Id solo si la especialidad está activa; si no, `null`. |
| `licenseExists(license)` | `DoctorProfileStore` | Busca en todos los perfiles, sin importar el estado. |
| `createDoctor(...)` | `DoctorProfileStore` | Perfil + especialidad principal + auditoría, todo o nada. |
| `specialtiesOf(id)` | `DoctorSpecialtyStore` | Vigencia del perfil activo y sus vínculos activos; `null` si no existe o no está activo. |
| `associate(...)` | `DoctorSpecialtyStore` | Vínculo + auditoría, todo o nada. |
| `doctorsWith(specialtyId, date)` | `DoctorsBySpecialty` | Médicos activos con perfil y especialidad vigentes en esa fecha, ordenados por id. |

**Por qué en Application y por qué un solo contrato que agrupa:** los casos de uso son quienes definen lo que necesitan, así que el límite está en Application y Persistence depende de él. Agrupar los cuatro puertos en `DoctorRepository` obliga a que un adaptador nuevo del agregado los implemente todos, sin deshacer ISP: `QueryAvailability` sigue sin ver los métodos de escritura (lo comprueba `run.php`). `AppointmentReader` queda aparte porque los datos son del módulo Citas, y `DoctorScheduleProvider` porque el horario todavía no tiene tabla.

**Cómo se demuestra que los adaptadores son intercambiables:** `run.php` ejecuta los mismos 20 casos contra `PdoDoctorStore` (SQLite en memoria) y contra `InMemoryDoctorRepository`, con los mismos datos y por el mismo router.

## Traducción a HTTP en el controlador

| Ruta | Resultado | Estado | Cuerpo |
|---|---|---|---|
| `POST /doctors` | Médico creado | `201` | `doctor_profile_id` |
| `POST /doctors/{id}/specialties` | Vínculo creado | `201` | `doctor_profile_id`, `specialty_id`, `is_primary` |
| `GET /availability?specialty=&date=` | Consulta | `200` | `code` (`OK` o `NO_DOCTORS_FOR_SPECIALTY`), `doctors[]` |
| Cualquiera | `DoctorRuleViolation` | `404`, `409` o `422` | `code` |
| Cualquiera | Falla del repositorio (`RuntimeException`) | `500` | `code: DOCTOR_PERSISTENCE_ERROR` |
| Otra ruta | — | `404` | `code: ROUTE_NOT_FOUND` |
| Cuerpo JSON inválido | — | `422` | `code: PAYLOAD_INVALID` (en `public/index.php`) |

Cada fila termina la petición. Ningún error sigue al caso de uso ni a la base.
