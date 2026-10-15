<?php

declare(strict_types=1);

namespace MicroHis\ValidacionResultados\Presentation;

use MicroHis\ValidacionResultados\Application\RejectReviewedResult;
use MicroHis\ValidacionResultados\Application\RejectResultCommand;
use MicroHis\ValidacionResultados\Application\ValidateReviewedResult;
use MicroHis\ValidacionResultados\Application\ValidateResultCommand;
use MicroHis\ValidacionResultados\Domain\DomainRuleViolation;

final class HttpReviewController
{
    public function __construct(
        private readonly ValidateReviewedResult $validateReviewedResult,
        private readonly RejectReviewedResult $rejectReviewedResult,
    ) {
    }

    /**
     * @param array<string, string|null> $request
     * @return array{status:int, body:array<string, mixed>}
     */
    public function validate(array $request): array
    {
        try {
            $response = $this->validateReviewedResult->execute(new ValidateResultCommand(
                tenantId: (string) ($request['tenant_id'] ?? ''),
                resultId: (string) ($request['result_id'] ?? ''),
                userId: (string) ($request['user_id'] ?? ''),
                role: (string) ($request['role'] ?? ''),
            ));

            return ['status' => 200, 'body' => $response];
        } catch (DomainRuleViolation $error) {
            return ['status' => 422, 'body' => ['error' => $error->getMessage()]];
        } catch (\Throwable) {
            return ['status' => 500, 'body' => ['error' => 'No fue posible validar el resultado.']];
        }
    }

    /**
     * @param array<string, string|null> $request
     * @return array{status:int, body:array<string, mixed>}
     */
    public function reject(array $request): array
    {
        try {
            $response = $this->rejectReviewedResult->execute(new RejectResultCommand(
                tenantId: (string) ($request['tenant_id'] ?? ''),
                resultId: (string) ($request['result_id'] ?? ''),
                reason: (string) ($request['reason'] ?? ''),
                userId: (string) ($request['user_id'] ?? ''),
                role: (string) ($request['role'] ?? ''),
            ));

            return ['status' => 200, 'body' => $response];
        } catch (DomainRuleViolation $error) {
            return ['status' => 422, 'body' => ['error' => $error->getMessage()]];
        } catch (\Throwable) {
            return ['status' => 500, 'body' => ['error' => 'No fue posible rechazar el resultado.']];
        }
    }
}
