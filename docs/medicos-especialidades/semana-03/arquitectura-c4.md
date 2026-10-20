# Vista arquitectónica C4/UML — Micro-HIS Médicos, especialidades y disponibilidad

## Nivel 1: contexto

```mermaid
flowchart LR
    Admin([Administrador del hospital]) -->|alta de médico y especialidades| MH[Micro-HIS Médicos<br>PHP 8.2 vanilla]
    Recep([Recepción]) -->|consulta de disponibilidad| MH
    MH -->|lee horas ocupadas, sin datos del paciente| Citas[Módulo Citas]
    MH -->|lee y escribe con PDO| DB[(BD hospital<br>doctor_profiles, specialties,<br>doctor_specialties, local_access_audits)]
```

## Nivel 2: contenedores

```mermaid
flowchart LR
    T[tests/run.php<br>cliente de prueba] --> App[Aplicación PHP<br>bootstrap.php + src/]
    App -->|PDO, sentencias preparadas| DB[(SQLite en memoria en pruebas<br>PostgreSQL hospital en el SHI)]
    App -->|arreglo de configuración| Cfg[Horario semanal<br>ConfigScheduleProvider]
```

En el prototipo el cliente es `tests/run.php`. En el SHI será la ruta HTTP con JWT y `X-Tenant-ID` (fuera de alcance esta semana).

## Nivel 3: componentes por capa

```mermaid
flowchart TB
    subgraph Presentation
        H[DoctorHttpHandler]
    end
    subgraph Application
        R[RegisterDoctor]
        A[AssociateSpecialty]
        Q[QueryAvailability]
        P1[[DoctorProfileStore]]
        P2[[DoctorSpecialtyStore]]
        P3[[SpecialtyCatalog]]
        P4[[DoctorsBySpecialty]]
        P5[[DoctorScheduleProvider]]
        P6[[AppointmentReader]]
    end
    subgraph Domain
        Pol[SpecialtyAssignmentPolicy]
        Slot[SlotPlanner]
        VO[Period, SpecialtyLink, DoctorRuleViolation]
    end
    subgraph Persistence
        Pdo[PdoDoctorStore]
        Cfg[ConfigScheduleProvider]
    end
    H --> R & A & Q
    R --> P1 & P3
    A --> P2 & P3 & Pol
    Q --> P3 & P4 & P5 & P6 & Slot
    Pol --> VO
    Pdo -.implementa.-> P1 & P2 & P3 & P4 & P6
    Cfg -.implementa.-> P5
```

## UML de clases: puertos segregados (ISP de la Semana 2)

```mermaid
classDiagram
    direction LR
    class DoctorProfileStore {
        +licenseExists(license) bool
        +createDoctor(userRef, license, primary, actorId) string
    }
    <<interface>> DoctorProfileStore
    class DoctorSpecialtyStore {
        +specialtiesOf(doctorId) array
        +associate(doctorId, link, actorId) void
    }
    <<interface>> DoctorSpecialtyStore
    class SpecialtyCatalog {
        +activeSpecialtyId(code) string
    }
    <<interface>> SpecialtyCatalog
    class QueryAvailability {
        +execute(specialtyCode, date) array
    }
    class PdoDoctorStore
    PdoDoctorStore ..|> DoctorProfileStore
    PdoDoctorStore ..|> DoctorSpecialtyStore
    PdoDoctorStore ..|> SpecialtyCatalog
    QueryAvailability --> SpecialtyCatalog
```

`QueryAvailability` es de solo lectura: no conoce `DoctorProfileStore` ni `DoctorSpecialtyStore`. La prueba `ISP` de `tests/run.php` lo verifica por reflexión.

## Decisiones arquitectónicas

| Decisión | Justificación |
|---|---|
| Puertos en Application, adaptadores en Persistence | Inversión de dependencias: Application define lo que necesita y PDO queda en el borde. |
| Seis puertos pequeños (ISP) y un adaptador PDO que implementa cinco | Cada caso de uso depende solo de los métodos que usa. Un adaptador de solo lectura (vista de agenda) no tiene que implementar el alta. |
| `SpecialtyAssignmentPolicy` y `SlotPlanner` sin dependencias | Las reglas de vigencia, duplicado y principal y el cálculo de franjas se prueban sin BD. |
| Alta, especialidad principal y auditoría en una transacción | RNF-MED-02: si falla la auditoría no queda un perfil sin traza (rollback). |
| Horario por configuración | `doctor_schedules` no existe en el esquema federado (acuerdo C1). Se cambia por un adaptador PDO sin tocar Application. |
