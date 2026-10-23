<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Application;

use MicroHis\QaCalidad\Domain\TestRun;
use MicroHis\QaCalidad\Domain\TestRunRepository;

/** RF-03 y RF-04: registrar el resultado de una suite junto con su evidencia. */
final class RecordSuiteResult
{
    public function __construct(
        private readonly Authorizer $authorizer,
        private readonly RunInputParser $parser,
        private readonly RunLoader $loader,
        private readonly TestRunRepository $runs,
        private readonly AuditTrail $audit,
    ) {}

    public function execute(Actor $actor, string $runId, array $body, string $traceId): TestRun
    {
        $this->authorizer->require($actor, QaPermissions::REGISTER, $runId, $traceId);
        $result = $this->parser->result($body);
        $run = $this->loader->load($actor, $runId);
        $run->record($result);
        $this->runs->save($run);
        $this->audit->record($actor, 'qa.resultado.registrado', 'success', $run->id, $traceId, null, ['suite' => $result->suite, 'fallidas' => $result->failed]);

        return $run;
    }
}
