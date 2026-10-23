<?php

declare(strict_types=1);

namespace MicroHis\QaCalidad\Presentation;

use MicroHis\QaCalidad\Application\Actor;
use MicroHis\QaCalidad\Application\AdvanceTestRun;
use MicroHis\QaCalidad\Application\EvaluateQualityGate;
use MicroHis\QaCalidad\Application\PlanTestRun;
use MicroHis\QaCalidad\Application\RecordSuiteResult;
use MicroHis\QaCalidad\Application\ViewTestRuns;

/**
 * Controlador (la C de MVC): traduce petición → caso de uso → vista.
 * No contiene SQL ni reglas de negocio; las excepciones las mapea ErrorMapper.
 */
final class QaController
{
    public function __construct(
        private readonly PlanTestRun $plan,
        private readonly AdvanceTestRun $advance,
        private readonly RecordSuiteResult $record,
        private readonly EvaluateQualityGate $evaluate,
        private readonly ViewTestRuns $view,
        private readonly RunPresenter $presenter,
    ) {}

    public function index(Actor $actor, array $query, string $trace): array
    {
        $limit = filter_var($query['limit'] ?? 20, FILTER_VALIDATE_INT) ?: 20;
        $items = $this->view->list($actor, (int) $limit, $trace);

        return HttpResponse::json(200, ['data' => array_map(fn (array $i): array => $this->presenter->run($i['run'], $i['decision']), $items)], $trace);
    }

    public function store(Actor $actor, array $body, string $trace): array
    {
        return HttpResponse::json(201, ['data' => $this->presenter->run($this->plan->execute($actor, $body, $trace))], $trace);
    }

    public function show(Actor $actor, string $runId, string $trace): array
    {
        $found = $this->view->get($actor, $runId, $trace);

        return HttpResponse::json(200, ['data' => $this->presenter->run($found['run'], $found['decision'])], $trace);
    }

    public function transition(Actor $actor, string $runId, array $body, string $trace): array
    {
        $transition = is_string($body['transition'] ?? null) ? $body['transition'] : '';
        $reason = is_string($body['reason'] ?? null) ? $body['reason'] : null;

        return HttpResponse::json(200, ['data' => $this->presenter->run($this->advance->execute($actor, $runId, $transition, $reason, $trace))], $trace);
    }

    public function result(Actor $actor, string $runId, array $body, string $trace): array
    {
        return HttpResponse::json(201, ['data' => $this->presenter->run($this->record->execute($actor, $runId, $body, $trace))], $trace);
    }

    public function gate(Actor $actor, string $runId, string $trace): array
    {
        return HttpResponse::json(201, ['data' => $this->presenter->decision($this->evaluate->execute($actor, $runId, $trace))], $trace);
    }
}
