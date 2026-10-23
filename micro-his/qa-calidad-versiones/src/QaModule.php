<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad;

use MicroHis\QaCalidad\Application\AccessGate;
use MicroHis\QaCalidad\Application\AdvanceTestRun;
use MicroHis\QaCalidad\Application\AuditTrail;
use MicroHis\QaCalidad\Application\Authorizer;
use MicroHis\QaCalidad\Application\Clock;
use MicroHis\QaCalidad\Application\EvaluateQualityGate;
use MicroHis\QaCalidad\Application\PlanTestRun;
use MicroHis\QaCalidad\Application\RecordSuiteResult;
use MicroHis\QaCalidad\Application\RunInputParser;
use MicroHis\QaCalidad\Application\RunLoader;
use MicroHis\QaCalidad\Application\ViewTestRuns;
use MicroHis\QaCalidad\Domain\QualityGate;
use MicroHis\QaCalidad\Domain\QualityGatePolicy;
use MicroHis\QaCalidad\Domain\TestRunRepository;
use MicroHis\QaCalidad\Presentation\ErrorMapper;
use MicroHis\QaCalidad\Presentation\QaController;
use MicroHis\QaCalidad\Presentation\QaHttpHandler;
use MicroHis\QaCalidad\Presentation\RunPresenter;

/**
 * Raíz de composición: el único lugar que conoce las clases concretas. La API HTTP,
 * el CLI de CI (bin/quality-gate.php), la demo y las pruebas arman el módulo aquí.
 */
final readonly class QaModule
{
    private function __construct(
        public PlanTestRun $plan,
        public AdvanceTestRun $advance,
        public RecordSuiteResult $record,
        public EvaluateQualityGate $evaluate,
        public ViewTestRuns $view,
        public QaHttpHandler $http,
    ) {}

    /** @param ?callable(string, array<string, mixed>): void $report reporte interno de fallos 5xx */
    public static function compose(
        TestRunRepository $runs,
        AccessGate $gate,
        AuditTrail $audit,
        Clock $clock,
        QualityGatePolicy $policy,
        ?callable $report = null,
    ): self {
        $authorizer = new Authorizer($gate, $audit);
        $parser = new RunInputParser;
        $loader = new RunLoader($runs);
        $plan = new PlanTestRun($authorizer, $parser, $runs, $audit, $clock);
        $advance = new AdvanceTestRun($authorizer, $loader, $runs, $audit, $clock);
        $record = new RecordSuiteResult($authorizer, $parser, $loader, $runs, $audit);
        $evaluate = new EvaluateQualityGate($authorizer, $loader, $runs, new QualityGate, $policy, $audit, $clock);
        $view = new ViewTestRuns($authorizer, $loader, $runs);
        $controller = new QaController($plan, $advance, $record, $evaluate, $view, new RunPresenter);

        return new self($plan, $advance, $record, $evaluate, $view, new QaHttpHandler($controller, new ErrorMapper($report)));
    }
}
