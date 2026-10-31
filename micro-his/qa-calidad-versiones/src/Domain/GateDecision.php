<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Domain;

/** Resultado inmutable de aplicar una política a una ejecución. */
final readonly class GateDecision
{
    /** @param list<GateFinding> $findings */
    public function __construct(
        public string $runId,
        public string $tenantId,
        public GateVerdict $verdict,
        public string $policyVersion,
        public array $findings,
        public string $evaluatedBy,
        public string $evaluatedAt,
    ) {}

    /** El veredicto se deriva de los hallazgos: blocker > rejection > aprobado. */
    public static function verdictFor(array $findings): GateVerdict
    {
        $severities = array_map(static fn (GateFinding $f): string => $f->severity, $findings);

        return match (true) {
            in_array('blocker', $severities, true) => GateVerdict::Blocked,
            in_array('rejection', $severities, true) => GateVerdict::Rejected,
            default => GateVerdict::Approved,
        };
    }
}
