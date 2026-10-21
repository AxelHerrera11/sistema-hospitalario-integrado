# Vista arquitectonica C4/UML

## Contexto

El micro-HIS permite que un bioquimico revise, valide o rechace resultados de laboratorio sin depender del framework principal. La implementacion es vanilla PHP 8.2+ para evidenciar las capas y dependencias.

## Contenedores / capas

```mermaid
flowchart LR
  User[Bioquimico] --> Presentation[Presentation\nHttpReviewController]
  Presentation --> Application[Application\nValidateReviewedResult\nRejectReviewedResult]
  Application --> Domain[Domain\nReviewableLabResult\nBiochemistReviewPolicy]
  Application --> Ports[Ports\nReviewResultRepository\nReviewAuditLogger]
  Ports --> Persistence[Persistence\nPDO / InMemory]
  Persistence --> DB[(lab_results_for_review)]
```

## Componentes

```mermaid
classDiagram
  class HttpReviewController
  class ValidateReviewedResult
  class RejectReviewedResult
  class ReviewableLabResult
  class BiochemistReviewPolicy
  class ReviewResultRepository {
    <<interface>>
    +getPending(tenantId, resultId)
    +save(result)
  }
  class ReviewAuditLogger {
    <<interface>>
    +record(event)
  }
  class PdoReviewResultRepository
  class InMemoryReviewResultRepository
  class ArrayReviewAuditLogger

  HttpReviewController --> ValidateReviewedResult
  HttpReviewController --> RejectReviewedResult
  ValidateReviewedResult --> ReviewResultRepository
  ValidateReviewedResult --> ReviewAuditLogger
  ValidateReviewedResult --> ReviewableLabResult
  RejectReviewedResult --> ReviewResultRepository
  RejectReviewedResult --> ReviewAuditLogger
  RejectReviewedResult --> ReviewableLabResult
  ReviewableLabResult --> BiochemistReviewPolicy
  PdoReviewResultRepository ..|> ReviewResultRepository
  InMemoryReviewResultRepository ..|> ReviewResultRepository
  ArrayReviewAuditLogger ..|> ReviewAuditLogger
```

## Decisiones arquitectonicas

| Decision | Justificacion |
| --- | --- |
| PHP vanilla en `micro-his/` | Cumple la restriccion de no usar framework para esta semana. |
| Casos de uso en Application | Evita SQL y reglas de negocio en Presentation. |
| Entidad `ReviewableLabResult` en Domain | Centraliza reglas de estado, rol, unidad y decision. |
| `ReviewResultRepository` como puerto | Permite probar con InMemory y persistir con PDO. |
| `PdoReviewResultRepository` con `prepare/execute` | Cumple sentencia preparada y evita concatenar SQL. |

## Trazabilidad hacia pruebas

| Requisito de la semana | Evidencia |
| --- | --- |
| Separar Presentation, Application, Domain y Persistence | Carpetas `src/Presentation`, `src/Application`, `src/Domain`, `src/Persistence`. |
| PDO con sentencias preparadas | `PdoReviewResultRepository::getPending` y `save`. |
| Camino feliz | `tests/run.php`, validacion de `rev-001`. |
| Regla de dominio | `tests/run.php`, rechazo sin motivo y doble decision. |
| Error de persistencia | `tests/run.php`, repositorio anonimo que lanza excepcion. |
