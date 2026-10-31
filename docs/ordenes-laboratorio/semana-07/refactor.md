# Refactor concreto: separar orquestación de integración en la emisión

**Punto elegido:** `IssueLabOrder` (semana 3) es la clase con más razones para cambiar: idempotencia,
contrato del expediente, catálogo de pruebas, creación de ítems, evento inicial y carrera de
persistencia. Es el cruce entre **órdenes, expediente, pruebas solicitadas, estados y trazabilidad**.

## 1. Antes

```php
final class IssueLabOrder
{
    public function __construct(
        private LabOrderRepository $repository,
        private ExpedienteGateway $expediente,
        private Closure $newId,
        private Closure $newEventId,
        private Closure $now,
        private Closure $newCorrelationId,
    ) {}

    public function execute(array $input, string $actor, string $tenant): LabOrder
    {
        $key = trim((string) ($input['idempotency_key'] ?? ''));
        if ($key === '') { throw new DomainRuleViolation('IDEMPOTENCY_KEY_REQUIRED', 'idempotency_key'); }
        if (($existing = $this->repository->findByIdempotency($tenant, $key)) !== null) { return $existing; }

        $patientId = trim((string) ($input['local_patient_id'] ?? ''));
        $episodeId = trim((string) ($input['care_episode_id'] ?? ''));
        $context = $this->expediente->episodeContext($tenant, $patientId, $episodeId);
        if (($context['patient_id'] ?? '') !== $patientId || !($context['open'] ?? false)) {
            throw new DomainRuleViolation('CARE_EPISODE_NOT_OPEN', 'care_episode_id');
        }
        // ... nota, testIds, activeTests, LabOrderItem, LabOrder::issue, create, carrera ...
    }
}
```

Problemas:

- Conoce **7 claves de array** de dos contratos externos.
- Mezcla validación de integración con creación de dominio.
- Cambiar el catálogo o el expediente obliga a tocar la orquestación.
- La prueba unitaria de emisión necesita montar todo el contexto.

## 2. Después

```php
final class LabOrderDraft
{
    /** @param list<LabOrderItem> $items */
    public function __construct(
        public readonly string $patientId,
        public readonly string $episodeId,
        public readonly ?string $noteId,
        public readonly string $priority,
        public readonly ?string $clinicalContext,
        public readonly array $items,
    ) {}
}

interface ExpedienteGateway
{
    public function episodeContext(string $tenant, string $patientId, string $episodeId): ?EpisodeContext;

    /** @param list<string> $testIds @return array<string, LabTestSnapshot> */
    public function activeTests(string $tenant, array $testIds): array;
}

final class LabOrderDraftAssembler
{
    public function __construct(private ExpedienteGateway $expediente) {}

    public function assemble(array $input, string $tenant): LabOrderDraft
    {
        $patientId = trim((string) ($input['local_patient_id'] ?? ''));
        $episodeId = trim((string) ($input['care_episode_id'] ?? ''));
        $context = $this->expediente->episodeContext($tenant, $patientId, $episodeId)
            ?? throw new DomainRuleViolation('CARE_EPISODE_NOT_OPEN', 'care_episode_id');
        if (!$context->belongsTo($patientId, $episodeId) || !$context->isOpen) {
            throw new DomainRuleViolation('CARE_EPISODE_NOT_OPEN', 'care_episode_id');
        }
        // ... nota, pruebas activas, ítems ...
        return new LabOrderDraft($patientId, $episodeId, $noteId, $priority, $contextText, $items);
    }
}

final class EmitLabOrderHandler
{
    public function __construct(
        private LabOrderRepository $repository,
        private LabOrderDraftAssembler $assembler,
        private IdsAndClock $deps, // nuevos IDs, evento, correlación y fecha
    ) {}

    public function execute(array $input, string $actor, string $tenant): IssueLabOrderOutcome
    {
        $key = trim((string) ($input['idempotency_key'] ?? ''));
        if ($key === '') { throw new DomainRuleViolation('IDEMPOTENCY_KEY_REQUIRED', 'idempotency_key'); }
        if (($existing = $this->repository->findByIdempotency($tenant, $key)) !== null) {
            return new IssueLabOrderOutcome($existing, true);
        }
        $draft = $this->assembler->assemble($input, $tenant);
        $order = LabOrder::issue($draft, $actor, $this->deps);
        try {
            $this->repository->create($tenant, $order);
        } catch (PersistenceFailure $failure) {
            if ($failure->duplicate && ($existing = $this->repository->findByIdempotency($tenant, $key)) !== null) {
                return new IssueLabOrderOutcome($existing, true);
            }
            throw $failure;
        }

        return new IssueLabOrderOutcome($order, false);
    }
}
```

## 3. Comparación

| Métrica | Antes | Después |
|---|---|---|
| Responsabilidades en la clase de emisión | 6 | 2 (idempotencia + persistencia) |
| Claves de array externas conocidas por la orquestación | 7 | 0 |
| Dependencias del constructor | 6 | 3 |
| Clases nuevas | — | 3 (`LabOrderDraft`, `EpisodeContext`, `LabTestSnapshot`) + 1 assembler |
| Cambio de contrato HTTP | — | Ninguno |
| Cambio de esquema | — | Ninguno |
| Códigos de error | 6 | Los mismos 6 |

## 4. Por qué no amplía el módulo

- No agrega endpoints, tablas ni estados.
- Solo mueve conocimiento de integración a un colaborador especializado.
- `LabOrder::issue` conserva las mismas invariantes y el evento inicial.
- El orden de validación se preserva: **idempotencia → expediente → catálogo → dominio → persistencia**.
- La carrera de `UNIQUE` se sigue resolviendo con `findByIdempotency` y `PersistenceFailure`.

## 5. Plan de pruebas de la refactorización

| Prueba | Antes | Después |
|---|---|---|
| Emisión válida | `run.php` | Igual |
| Episodio cerrado | `run.php` | `LabOrderDraftAssembler` |
| Prueba inactiva | `run.php` | `LabOrderDraftAssembler` |
| Replay de idempotencia | `api.php` | `EmitLabOrderHandler` |
| Carrera de clave duplicada | `run.php` | `EmitLabOrderHandler` |
| Fallo de persistencia | `run.php` | `EmitLabOrderHandler` |

Criterio de salida: las 25 verificaciones de `run.php`, 12 de `mvc.php`, 18 de `api.php` y 6 de
`client.php` siguen en verde **sin cambiar sus expectativas**.

## 6. Riesgos

| Riesgo | Mitigación |
|---|---|
| Divergencia de mensajes de error | Mantener códigos y campos idénticos; pruebas de contrato |
| DTOs que se convierten en un mini-framework | Value objects mínimos, sin herencia ni magia |
| Doble validación | El dominio sigue validando; el assembler solo traduce y rechaza lo que no puede construir |
| Cambio grande para una semana | Se entrega como diseño y comparación; la implementación puede ir en un PR técnico posterior |

## 7. Justificación

El refactor no busca “más clases”, sino **una sola razón de cambio** para la orquestación. Hoy un
cambio en el contrato del expediente o del catálogo puede romper la emisión completa; después, el
assembler absorbe esa variación y el handler queda estable. Es SRP aplicado al punto de mayor
acoplamiento, sin ampliar el alcance funcional.
