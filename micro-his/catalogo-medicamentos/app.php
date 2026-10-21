<?php

declare(strict_types=1);

// Composición de la entrada MVC: une Router, controladores y casos de uso sobre cualquier MedicationRepository.

use MicroHis\MedicationCatalog\Application\ChangeMedicationValidity;
use MicroHis\MedicationCatalog\Application\ConsultMedications;
use MicroHis\MedicationCatalog\Application\MedicationRepository;
use MicroHis\MedicationCatalog\Application\RegisterMedication;
use MicroHis\MedicationCatalog\Presentation\RegistrationController;
use MicroHis\MedicationCatalog\Presentation\Router;
use MicroHis\MedicationCatalog\Presentation\SearchController;
use MicroHis\MedicationCatalog\Presentation\ValidityController;

require_once __DIR__ . '/bootstrap.php';

function catalog_router(MedicationRepository $repository): Router
{
    return new Router(
        new RegistrationController(new RegisterMedication($repository)),
        new SearchController(new ConsultMedications($repository)),
        new ValidityController(new ChangeMedicationValidity($repository)),
    );
}
