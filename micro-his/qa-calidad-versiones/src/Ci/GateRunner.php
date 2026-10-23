<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\InvalidQaInput;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\QaModule;

/**
 * Recorre el flujo completo con los MISMOS casos de uso que la API:
 * planificar → iniciar → registrar cada suite con su evidencia → completar → gate.
 * Una suite sin reporte JUnit no se inventa: queda sin resultado y el gate la bloquea.
 */
final class GateRunner
{
    public function __construct(
        private readonly QaModule $module,
        private readonly JUnitReportReader $junit,
        private readonly EvidenceCollector $evidence,
    ) {}

    /**
     * @param  array{suites: list<array{name: string, kind: string, required?: bool, junit: string, evidence?: list<array{kind: string, path: string}>}>}  $manifest
     * @return array{run: TestRun, decision: GateDecision, notes: list<string>}
     */
    public function run(Actor $ci, array $manifest, string $release, string $sha, string $environment, string $trace): array
    {
        $suites = array_map(static fn (array $s): array => [
            'name' => $s['name'], 'kind' => $s['kind'], 'required' => $s['required'] ?? true,
        ], $manifest['suites'] ?? []);
        $run = $this->module->plan->execute($ci, ['release_version' => $release, 'commit_sha' => $sha, 'environment' => $environment, 'suites' => $suites], $trace);
        $this->module->advance->execute($ci, $run->id, 'start', null, $trace);

        $notes = [];
        foreach ($manifest['suites'] as $suite) {
            $file = $this->evidence->resolve($suite['junit']);
            if ($file === null) {
                $notes[] = "{$suite['name']}: no se encontró {$suite['junit']} (la etapa no corrió o no publicó su reporte).";

                continue;
            }
            try {
                $counts = $this->junit->read($file);
            } catch (\RuntimeException $e) {
                $notes[] = "{$suite['name']}: ".$e->getMessage();

                continue;
            }
            $refs = [$this->evidence->collect('junit', $suite['junit'])];
            foreach ($suite['evidence'] ?? [] as $extra) {
                try {
                    $refs[] = $this->evidence->collect($extra['kind'], $extra['path']);
                } catch (\RuntimeException|InvalidQaInput $e) {
                    // Un log ausente o vacío se informa; no invalida el reporte JUnit de la suite.
                    $notes[] = "{$suite['name']}: evidencia adicional descartada {$extra['path']} ({$e->getMessage()}).";
                }
            }
            $this->module->record->execute($ci, $run->id, [
                'suite' => $suite['name'],
                'evidence' => array_map(static fn ($r): array => ['kind' => $r->kind, 'path' => $r->path, 'sha256' => $r->sha256, 'bytes' => $r->bytes], $refs),
            ] + $counts, $trace);
        }
        $this->module->advance->execute($ci, $run->id, 'complete', null, $trace);
        $decision = $this->module->evaluate->execute($ci, $run->id, $trace);

        return ['run' => $this->module->view->get($ci, $run->id, $trace)['run'], 'decision' => $decision, 'notes' => $notes];
    }
}
