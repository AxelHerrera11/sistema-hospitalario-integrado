# Vista arquitectónica C4/UML — Micro-HIS Signos vitales y alertas

## Nivel 1: contexto

```mermaid
flowchart LR
    Med([Médico tratante]) -->|captura signos vitales| MH[Micro-HIS Signos vitales<br>PHP 8.2 vanilla]
    MH -->|consulta episodio y asignación| Adm[Admisión<br>care_episodes, care_assignments]
    MH -->|lee y escribe con PDO| DB[(BD hospital<br>vital_signs, local_access_audits)]
    MH -->|alerta después del COMMIT| Canal[Canal de notificación<br>acuerdo C3]
```

## Nivel 2: contenedores

```mermaid
flowchart LR
    T[tests/run.php<br>cliente de prueba] --> App[Aplicación PHP<br>bootstrap.php + src/]
    App -->|PDO, sentencias preparadas| DB[(SQLite en memoria en pruebas<br>PostgreSQL hospital en el SHI)]
    App -->|puerto AlertNotifier| Cola[QueuedAlertNotifier<br>cola en memoria]
```

En el prototipo el cliente es `tests/run.php`. En el SHI será la ruta HTTP con JWT y `X-Tenant-ID` (fuera de alcance esta semana).

## Nivel 3: componentes por capa

```mermaid
flowchart TB
    subgraph Presentation
        H[VitalSignHttpHandler]
    end
    subgraph Application
        R[RecordVitalSigns]
        P1[[CareEpisodeReader]]
        P2[[VitalSignRepository]]
        P3[[AlertNotifier]]
    end
    subgraph Domain
        VS[VitalSigns]
        E[ThresholdEvaluator]
        Rule[[VitalSignRule]]
        Reglas[RangeRule y subtipos,<br>NoAlertRule]
        A[Assessment, Severity]
    end
    subgraph Persistence
        Store[PdoVitalSignStore]
        Q[QueuedAlertNotifier]
    end
    H --> VS & R
    R --> P1 & E & P2 & P3
    E --> Rule
    Reglas -.implementa.-> Rule
    E --> A
    Store -.implementa.-> P1 & P2
    Q -.implementa.-> P3
```

## UML de clases: jerarquía sustituible (LSP de la Semana 2)

```mermaid
classDiagram
    direction TB
    class VitalSignRule {
        +name() string
        +evaluate(vitals) Severity
    }
    <<interface>> VitalSignRule
    class RangeRule {
        +name() string
        +evaluate(vitals) Severity
    }
    <<abstract>> RangeRule
    class ThresholdEvaluator {
        +evaluate(vitals) Assessment
    }
    VitalSignRule <|.. RangeRule
    VitalSignRule <|.. NoAlertRule
    RangeRule <|-- HeartRateRule
    RangeRule <|-- OxygenSaturationRule
    RangeRule <|-- GlucoseRule
    RangeRule <|-- RespiratoryRateRule
    ThresholdEvaluator --> VitalSignRule : lista de reglas
```

`RangeRule::evaluate()` es `final`: las subclases solo aportan bandas y no pueden reforzar la precondición. La prueba `honorsContract()` de `tests/run.php` es la misma para las seis reglas: signo ausente → `None`, sin excepciones.

## Decisiones arquitectónicas

| Decisión | Justificación |
|---|---|
| Puertos en Application, adaptadores en Persistence | Inversión de dependencias: Application define lo que necesita y PDO queda en el borde. |
| `VitalSigns` valida plausibilidad al construirse | RF-SV-02: un valor imposible se rechaza antes de evaluar umbrales; las reglas reciben siempre datos válidos. |
| Contrato `VitalSignRule` sin `instanceof` | Agregar una regla es agregar una clase a la lista; `ThresholdEvaluator` no cambia (LSP y OCP). |
| Medición y auditoría en una transacción; notificación después | RF-SV-07: si falla la auditoría hay rollback y no se envía ninguna alerta. |
| Sin tabla de alertas propia | `critical_alert` (slice H7) es de laboratorio y queda sin tocar. La alerta queda trazada en `local_access_audits`. |
