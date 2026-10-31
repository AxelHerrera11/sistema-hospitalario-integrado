<?php

declare(strict_types=1);

namespace MicroHis\OrdenesLab\Presentation;

use MicroHis\OrdenesLab\Application\AdvanceLabOrder;
use MicroHis\OrdenesLab\Application\CancelLabOrder;
use MicroHis\OrdenesLab\Application\FindLabOrder;
use MicroHis\OrdenesLab\Application\IssueLabOrder;
use MicroHis\OrdenesLab\Application\ListLabOrders;
use MicroHis\OrdenesLab\Domain\LabOrder;

/**
 * Controller (MVC) de la semana 4: traduce la petición a casos de uso y elige JSON o la View.
 * No contiene SQL ni reglas de negocio; los errores los traduce el presentador.
 */
final class LabOrderController
{
    public function __construct(
        private readonly IssueLabOrder $issue,
        private readonly AdvanceLabOrder $advance,
        private readonly CancelLabOrder $cancel,
        private readonly FindLabOrder $find,
        private readonly ListLabOrders $list,
        private readonly LabOrderPresenter $presenter,
        private readonly LabOrderView $view,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array{status: int, body: array<string, mixed>|string}
     */
    public function handle(string $method, string $path, array $input, ?string $user, string $tenant): array
    {
        if ($user === null) {
            return ['status' => 401, 'body' => ['error' => 'UNAUTHENTICATED']];
        }

        try {
            if ($method === 'POST' && $path === '/lab-orders') {
                $outcome = $this->issue->executeOutcome($input, $user, $tenant);

                return $outcome->replayed
                    ? $this->presenter->ok($outcome->order)
                    : $this->presenter->created($outcome->order);
            }
            if ($method === 'GET' && $path === '/lab-orders') {
                $result = $this->list->execute(
                    $tenant,
                    array_intersect_key($input, array_flip(['patient_id', 'episode_id', 'status'])),
                    (int) ($input['per_page'] ?? 20),
                    (int) ($input['page'] ?? 1),
                );

                return $this->presenter->list($result);
            }
            if ($method === 'GET' && preg_match('#^/lab-orders/([^/]+)$#', $path, $match) === 1) {
                $order = $this->find->execute($tenant, $match[1]);

                return ($input['format'] ?? '') === 'html'
                    ? ['status' => 200, 'body' => $this->view->render($order)]
                    : $this->presenter->ok($order);
            }
            if ($method === 'PATCH' && preg_match('#^/lab-orders/([^/]+)/status$#', $path, $match) === 1) {
                return $this->presenter->ok($this->advance->execute(
                    $tenant,
                    $match[1],
                    (string) ($input['status'] ?? ''),
                    $user,
                ));
            }
            if ($method === 'POST' && preg_match('#^/lab-orders/([^/]+)/cancel$#', $path, $match) === 1) {
                return $this->presenter->ok($this->cancel->execute(
                    $tenant,
                    $match[1],
                    $user,
                    (string) ($input['reason'] ?? ''),
                ));
            }

            return ['status' => 404, 'body' => ['error' => 'NOT_FOUND']];
        } catch (\Throwable $error) {
            return $this->presenter->error($error);
        }
    }

    /** Atajo tipado para consumidores internos que ya tienen la orden. */
    public function render(LabOrder $order): string
    {
        return $this->view->render($order);
    }
}
