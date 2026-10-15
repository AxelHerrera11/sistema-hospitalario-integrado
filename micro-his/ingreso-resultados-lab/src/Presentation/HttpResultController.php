<?php

declare(strict_types=1);

namespace MicroHis\IngresoResultados\Presentation;

use MicroHis\IngresoResultados\Application\CapturePendingResult;
use MicroHis\IngresoResultados\Application\CaptureResultCommand;
use MicroHis\IngresoResultados\Domain\DomainRuleViolation;

final class HttpResultController
{
    public function __construct(private readonly CapturePendingResult $capturePendingResult)
    {
    }

    /**
     * @param array<string, string|null> $request
     * @return array{status:int, body:array<string, mixed>}
     */
    public function capture(array $request): array
    {
        try {
            $response = $this->capturePendingResult->execute(new CaptureResultCommand(
                tenantId: (string) ($request['tenant_id'] ?? ''),
                resultId: (string) ($request['result_id'] ?? ''),
                value: (string) ($request['value'] ?? ''),
                unit: (string) ($request['unit'] ?? ''),
                observation: $request['observation'] ?? null,
                userId: (string) ($request['user_id'] ?? ''),
            ));

            return ['status' => 200, 'body' => $response];
        } catch (DomainRuleViolation $error) {
            return ['status' => 422, 'body' => ['error' => $error->getMessage()]];
        } catch (\Throwable) {
            return ['status' => 500, 'body' => ['error' => 'No fue posible guardar el resultado.']];
        }
    }
}
