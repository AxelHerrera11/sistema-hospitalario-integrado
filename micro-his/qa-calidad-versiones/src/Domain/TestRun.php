<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Agregado "ejecución automatizada": un plan aplicado a un commit, en un ambiente
 * aislado y dentro del hospital de pruebas (tenant) que la ejecuta.
 * Toda transición inválida lanza RunStateConflict; nada se modifica a medias.
 */
final class TestRun
{
    /** @var array<string, SuiteResult> */
    private array $results = [];

    private function __construct(
        public readonly string $id,
        public readonly string $tenantId,
        public readonly TestPlan $plan,
        public readonly TestEnvironment $environment,
        public readonly string $createdBy,
        public readonly string $createdAt,
        private RunStatus $status,
        private ?string $finishedAt = null,
        private ?string $abortReason = null,
    ) {}

    public static function plan(string $id, string $tenantId, TestPlan $plan, TestEnvironment $environment, string $createdBy, string $now): self
    {
        return new self($id, $tenantId, $plan, $environment, $createdBy, $now, RunStatus::Planned);
    }

    /** @param list<SuiteResult> $results Rehidratación desde persistencia, sin volver a validar transiciones. */
    public static function restore(
        string $id, string $tenantId, TestPlan $plan, TestEnvironment $environment, string $createdBy,
        string $createdAt, RunStatus $status, ?string $finishedAt, ?string $abortReason, array $results,
    ): self {
        $run = new self($id, $tenantId, $plan, $environment, $createdBy, $createdAt, $status, $finishedAt, $abortReason);
        foreach ($results as $result) {
            $run->results[$result->suite] = $result;
        }

        return $run;
    }

    public function start(): void
    {
        $this->expect(RunStatus::Planned, 'La ejecución solo puede iniciarse desde planned.');
        $this->status = RunStatus::Running;
    }

    public function record(SuiteResult $result): void
    {
        $this->expect(RunStatus::Running, 'Solo se registran resultados mientras la ejecución está running.');
        if ($this->plan->suite($result->suite) === null) {
            throw new InvalidQaInput('suite_no_planificada', "La suite {$result->suite} no pertenece al plan.");
        }
        if (isset($this->results[$result->suite])) {
            // Un resultado ya registrado es evidencia: no se sobrescribe (RNF-03).
            throw new RunStateConflict('resultado_duplicado', "La suite {$result->suite} ya tiene resultado registrado.");
        }
        $this->results[$result->suite] = $result;
    }

    public function complete(string $now): void
    {
        $this->expect(RunStatus::Running, 'Solo una ejecución running puede completarse.');
        $this->status = RunStatus::Completed;
        $this->finishedAt = $now;
    }

    public function abort(string $reason, string $now): void
    {
        if (in_array($this->status, [RunStatus::Completed, RunStatus::Aborted], true)) {
            throw new RunStateConflict('ejecucion_finalizada', 'La ejecución ya terminó y no puede abortarse.');
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 200) {
            throw new InvalidQaInput('motivo_invalido', 'El motivo de aborto es obligatorio (máximo 200 caracteres).');
        }
        $this->status = RunStatus::Aborted;
        $this->abortReason = $reason;
        $this->finishedAt = $now;
    }

    public function status(): RunStatus
    {
        return $this->status;
    }

    public function finishedAt(): ?string
    {
        return $this->finishedAt;
    }

    public function abortReason(): ?string
    {
        return $this->abortReason;
    }

    public function result(string $suite): ?SuiteResult
    {
        return $this->results[$suite] ?? null;
    }

    /** @return list<SuiteResult> */
    public function results(): array
    {
        return array_values($this->results);
    }

    private function expect(RunStatus $expected, string $message): void
    {
        if ($this->status !== $expected) {
            throw new RunStateConflict('estado_invalido', $message." Estado actual: {$this->status->value}.");
        }
    }
}
