<?php

declare(strict_types=1);

namespace MicroHis\Medicos\Presentation;

use MicroHis\Medicos\Application\AssociateSpecialty;
use MicroHis\Medicos\Application\QueryAvailability;
use MicroHis\Medicos\Application\RegisterDoctor;
use MicroHis\Medicos\Domain\DoctorRuleViolation;

/** Controlador MVC: lee la petición, invoca el caso de uso (modelo) y entrega el resultado a la vista. Sin SQL ni reglas de negocio. */
final class DoctorController
{
    public function __construct(
        private readonly RegisterDoctor $register,
        private readonly AssociateSpecialty $associate,
        private readonly QueryAvailability $availability,
        private readonly JsonView $view,
    ) {
    }

    public function register(Request $request): array
    {
        return $this->respond(201, fn () => ['doctor_profile_id' => $this->register->execute(
            $request->actor(),
            $request->text('local_user_ref_id'),
            $request->text('license_number'),
            $request->text('specialty_code'),
            $request->text('valid_from'),
        )]);
    }

    public function associateSpecialty(Request $request, string $doctorId): array
    {
        return $this->respond(201, function () use ($request, $doctorId): array {
            $to = $request->text('valid_to');
            $link = $this->associate->execute($request->actor(), $doctorId, $request->text('specialty_code'), ($request->body['is_primary'] ?? false) === true, $request->text('valid_from'), $to === '' ? null : $to);
            return ['doctor_profile_id' => $doctorId, 'specialty_id' => $link->specialtyId, 'is_primary' => $link->isPrimary];
        });
    }

    public function availability(Request $request): array
    {
        return $this->respond(200, fn () => $this->availability->execute((string) ($request->query['specialty'] ?? ''), (string) ($request->query['date'] ?? '')));
    }

    /** Traduce resultado o excepción a estado + JSON; cada error termina la petición aquí. */
    private function respond(int $status, callable $action): array
    {
        try {
            return $this->view->render($status, $action());
        } catch (DoctorRuleViolation $e) {
            return $this->view->render($e->status, ['code' => $e->getMessage()]);
        } catch (\RuntimeException) {
            return $this->view->render(500, ['code' => 'DOCTOR_PERSISTENCE_ERROR']);
        }
    }
}
