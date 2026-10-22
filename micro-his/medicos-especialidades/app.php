<?php

declare(strict_types=1);

use MicroHis\Medicos\Application\AppointmentReader;
use MicroHis\Medicos\Application\AssociateSpecialty;
use MicroHis\Medicos\Application\DoctorRepository;
use MicroHis\Medicos\Application\DoctorScheduleProvider;
use MicroHis\Medicos\Application\QueryAvailability;
use MicroHis\Medicos\Application\RegisterDoctor;
use MicroHis\Medicos\Domain\SlotPlanner;
use MicroHis\Medicos\Domain\SpecialtyAssignmentPolicy;
use MicroHis\Medicos\Presentation\DoctorController;
use MicroHis\Medicos\Presentation\JsonView;
use MicroHis\Medicos\Presentation\Router;

require __DIR__ . '/bootstrap.php';

/** Raíz de composición: el mismo MVC funciona con cualquier adaptador del Repository (PDO o InMemory). */
function medicos_router(DoctorRepository $doctors, AppointmentReader $appointments, DoctorScheduleProvider $schedule): Router
{
    $view = new JsonView();
    $controller = new DoctorController(
        new RegisterDoctor($doctors, $doctors),
        new AssociateSpecialty($doctors, $doctors, new SpecialtyAssignmentPolicy()),
        new QueryAvailability($doctors, $doctors, $schedule, $appointments, new SlotPlanner()),
        $view,
    );
    return new Router($controller, $view);
}
