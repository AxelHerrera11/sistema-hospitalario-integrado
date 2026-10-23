<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Ci;

use MicroHis\QaCalidad\Domain\GateDecision;
use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Presentation\RunPresenter;

/** Publica la decisión como Markdown (resumen del job) y JSON (artefacto conservado). */
final class GateReportWriter
{
    private const ICON = ['approved' => '✅ APROBADO', 'rejected' => '❌ RECHAZADO', 'blocked' => '⛔ BLOQUEADO'];

    public function json(TestRun $run, GateDecision $decision, array $notes): string
    {
        $presenter = new RunPresenter;

        return json_encode(
            ['run' => $presenter->run($run, $decision), 'notes' => $notes],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        )."\n";
    }

    public function markdown(TestRun $run, GateDecision $decision, array $notes): string
    {
        $lines = [
            '## Quality gate: '.self::ICON[$decision->verdict->value],
            '',
            "Versión `{$run->plan->releaseVersion}` · commit `".substr($run->plan->commitSha, 0, 12)."` · ambiente `{$run->environment->value}` · política `{$decision->policyVersion}`",
            '',
            '| Suite | Tipo | Obligatoria | Total | Aprobadas | Fallidas | Omitidas | ms | Evidencias |',
            '|---|---|---|---:|---:|---:|---:|---:|---:|',
        ];
        foreach ($run->plan->suites as $suite) {
            $r = $run->result($suite->name);
            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s | %s | %s | %s |',
                $suite->name, $suite->kind->value, $suite->required ? 'sí' : 'no',
                ...($r === null ? array_fill(0, 6, '—') : [$r->total, $r->passed(), $r->failed, $r->skipped, $r->durationMs, count($r->evidence)]),
            );
        }
        $lines[] = '';
        $lines[] = $decision->findings === [] ? 'Sin hallazgos.' : '### Hallazgos';
        foreach ($decision->findings as $f) {
            $lines[] = sprintf('- **%s** `%s`%s: %s', $f->severity, $f->rule, $f->suite === null ? '' : " ({$f->suite})", $f->detail);
        }
        if ($notes !== []) {
            $lines[] = '';
            $lines[] = '### Notas de recolección';
            foreach ($notes as $note) {
                $lines[] = "- {$note}";
            }
        }

        return implode("\n", $lines)."\n";
    }
}
