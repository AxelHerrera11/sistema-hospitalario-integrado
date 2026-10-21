# Vista arquitectónica C4/UML — Micro-HIS Notas SOAP y diagnósticos

## Nivel 1: contexto

```mermaid
flowchart LR
    Med([Médico tratante]) -->|registra nota SOAP y diagnósticos| MH[Micro-HIS Notas SOAP<br>PHP 8.2 vanilla]
    MH -->|valida código y obtiene descripción| CIE[Catálogo CIE-10<br>origen por acordar]
    MH -->|consulta asignación activa| Asig[Asignaciones del episodio<br>care_assignments]
    MH -->|lee y escribe con PDO| DB[(BD hospital<br>clinical_notes, diagnoses,<br>local_access_audits)]
```

## Nivel 2: contenedores

```mermaid
flowchart LR
    T[tests/run.php<br>cliente de prueba] --> App[Aplicación PHP<br>bootstrap.php + src/]
    App -->|PDO, sentencias preparadas| DB[(SQLite en memoria en pruebas<br>PostgreSQL hospital en el SHI)]
    App -->|puerto Icd10Catalog| CIE[Tabla icd10_codes<br>o doble de prueba]
```

En el prototipo el cliente es `tests/run.php`. En el SHI será la ruta HTTP con JWT y `X-Tenant-ID` (fuera de alcance esta semana).

## Nivel 3: componentes por capa

```mermaid
flowchart TB
    subgraph Presentation
        H[SoapNoteHttpHandler]
    end
    subgraph Application
        R[RegisterSoapNote]
        A[AssociateDiagnosis]
        G[TreatingPhysicianGuard]
        P1[[ClinicalNoteRepository]]
        P2[[CareAssignmentReader]]
        P3[[Icd10Catalog]]
    end
    subgraph Domain
        Pol[DiagnosisPolicy]
        VO[SoapSections, Diagnosis,<br>ClinicalNote, SoapRuleViolation]
    end
    subgraph Persistence
        Store[PdoClinicalNoteStore]
        Cat[PdoIcd10Catalog]
    end
    H --> R & A
    H --> VO
    R --> G & P1
    A --> G & P3 & Pol & P1
    G --> P2
    Pol --> VO
    Store -.implementa.-> P1 & P2
    Cat -.implementa.-> P3
```

## UML de clases: una responsabilidad por clase (SRP de la Semana 2)

```mermaid
classDiagram
    direction LR
    class AssociateDiagnosis {
        +execute(userId, noteId, diagnosis) void
    }
    class TreatingPhysicianGuard {
        +assertAllowed(userId, episodeId, authorId) void
    }
    class DiagnosisPolicy {
        +assertCanAdd(note, diagnosis) void
    }
    class Icd10Catalog {
        +description(code) string
    }
    <<interface>> Icd10Catalog
    class ClinicalNoteRepository {
        +find(noteId) ClinicalNote
        +add(episodeId, authorId, sections) string
        +addDiagnosis(note, diagnosis, description) void
    }
    <<interface>> ClinicalNoteRepository
    AssociateDiagnosis --> TreatingPhysicianGuard : acceso
    AssociateDiagnosis --> DiagnosisPolicy : reglas clínicas
    AssociateDiagnosis --> Icd10Catalog : catálogo
    AssociateDiagnosis --> ClinicalNoteRepository : persistencia
    PdoIcd10Catalog ..|> Icd10Catalog
    PdoClinicalNoteStore ..|> ClinicalNoteRepository
```

Cada razón de cambio de la Semana 2 tiene una sola clase: acceso (`TreatingPhysicianGuard`), formato (`SoapSections`), catálogo (`Icd10Catalog`), reglas clínicas (`DiagnosisPolicy`) y persistencia (`ClinicalNoteRepository`).

## Decisiones arquitectónicas

| Decisión | Justificación |
|---|---|
| Puertos en Application, adaptadores en Persistence | Inversión de dependencias: Application define lo que necesita y PDO queda en el borde. |
| `SoapSections` y `Diagnosis` como objetos de valor | Si se pueden construir, son válidos. Presentation no puede pasar secciones vacías ni tipos no permitidos al caso de uso. |
| `DiagnosisPolicy` sin dependencias | Las reglas de duplicado, principal único y nota firmada se prueban sin BD. |
| Falla segura del catálogo | `CatalogUnavailable` se traduce a `503 ICD10_UNAVAILABLE` antes de escribir: la nota queda igual (RF-SOAP-06). |
| Nota o diagnóstico y auditoría en una transacción | Si falla la auditoría no queda un registro clínico sin traza (rollback). |
