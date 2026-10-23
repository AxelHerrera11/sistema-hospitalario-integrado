<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/**
 * Servicio de dominio puro: aplica la política a una ejecución y devuelve hallazgos.
 * No conoce CI, HTTP ni base de datos (DIP, Semana 2).
 *
 * Reglas (código → severidad):
 *   RUN_ABORTED / RUN_NOT_COMPLETED        blocker
 *   SUITE_MISSING, SUITE_EMPTY             blocker en obligatorias, warning en opcionales
 *   EVIDENCE_MISSING                       blocker en obligatorias, warning en opcionales
 *   SUITE_BELOW_THRESHOLD                  rejection en obligatorias, warning en opcionales
 *   FAILURES_TOLERATED                     warning (hay fallos pero se cumple el umbral)
 *   SKIPPED_IN_REQUIRED                    rejection si la política no admite omitidas
 *   E2E_SLOW                               warning
 */
final class QualityGate
{
    public function evaluate(TestRun $run, QualityGatePolicy $policy, string $evaluatedBy, string $now): GateDecision
    {
        $findings = match ($run->status()) {
            RunStatus::Completed => $this->suiteFindings($run, $policy),
            RunStatus::Aborted => [new GateFinding('RUN_ABORTED', 'blocker', null, 'La ejecución fue abortada: '.$run->abortReason())],
            default => [new GateFinding('RUN_NOT_COMPLETED', 'blocker', null, "La ejecución está {$run->status()->value}; el gate solo evalúa ejecuciones completas.")],
        };

        return new GateDecision($run->id, $run->tenantId, GateDecision::verdictFor($findings), $policy->version, $findings, $evaluatedBy, $now);
    }

    /** @return list<GateFinding> */
    private function suiteFindings(TestRun $run, QualityGatePolicy $policy): array
    {
        $findings = [];
        foreach ($run->plan->suites as $planned) {
            $hard = $planned->required ? 'blocker' : 'warning';
            $result = $run->result($planned->name);
            if ($result === null) {
                $findings[] = new GateFinding('SUITE_MISSING', $hard, $planned->name, 'No se registró resultado para la suite.');

                continue;
            }
            if ($result->executed() === 0) {
                // Una suite que no ejecutó nada (vacía o todo omitido) no demuestra calidad: vacío no es éxito.
                $findings[] = new GateFinding('SUITE_EMPTY', $hard, $planned->name, "La suite no ejecutó pruebas ({$result->total} en total, {$result->skipped} omitidas).");

                continue;
            }
            if ($policy->requireEvidence && $result->evidence === []) {
                $findings[] = new GateFinding('EVIDENCE_MISSING', $hard, $planned->name, 'El resultado no tiene evidencia conservada.');
            }
            array_push($findings, ...$this->rateFindings($planned, $result, $policy));
            if ($planned->kind === SuiteKind::E2e && $policy->maxE2eDurationMs !== null && $result->durationMs > $policy->maxE2eDurationMs) {
                $findings[] = new GateFinding('E2E_SLOW', 'warning', $planned->name, "Duró {$result->durationMs} ms (máximo {$policy->maxE2eDurationMs} ms).");
            }
        }

        return $findings;
    }

    /** @return list<GateFinding> */
    private function rateFindings(PlannedSuite $planned, SuiteResult $result, QualityGatePolicy $policy): array
    {
        $findings = [];
        $min = $policy->minPassRateFor($planned->kind);
        $rate = (float) $result->passRate();
        if ($rate < $min) {
            $findings[] = new GateFinding(
                'SUITE_BELOW_THRESHOLD',
                $planned->required ? 'rejection' : 'warning',
                $planned->name,
                sprintf('Aprobó %.1f %% (%d de %d ejecutadas); mínimo %.1f %%.', $rate * 100, $result->passed(), $result->executed(), $min * 100),
            );
        } elseif ($result->failed > 0) {
            $findings[] = new GateFinding('FAILURES_TOLERATED', 'warning', $planned->name, "{$result->failed} fallos tolerados por el umbral.");
        }
        if ($planned->required && $result->skipped > 0 && ! $policy->allowSkippedInRequired) {
            $findings[] = new GateFinding('SKIPPED_IN_REQUIRED', 'rejection', $planned->name, "{$result->skipped} pruebas omitidas en una suite obligatoria.");
        }

        return $findings;
    }
}
