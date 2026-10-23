<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use MicroHis\SalasCamas\Application\{BedRepository, CatalogService, PersistenceFailure, WardRepository};
use MicroHis\SalasCamas\Domain\{Bed, Ward};
use MicroHis\SalasCamas\Presentation\CatalogController;

final class FakeCatalogRepository implements WardRepository, BedRepository
{
    public array $wards = [];
    public array $beds = [];

    public function __construct(private bool $fail = false) {}
    public function listAll(): array
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        return array_values($this->wards);
    }
    public function findByCode(string $code): ?Ward
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        return $this->wards[$code] ?? null;
    }
    public function insertWard(Ward $item): Ward
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        $saved = new Ward('ward-1', $item->code, $item->name, $item->location, $item->status);
        return $this->wards[$saved->code] = $saved;
    }

    public function insertBed(Bed $item): Bed
    {
        if ($this->fail) throw new PersistenceFailure('fake failure');
        $saved = new Bed('bed-1', $item->wardId, $item->code, $item->type, $item->status);
        $this->beds[] = $saved;
        return $saved;
    }
}

$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$repo = new FakeCatalogRepository();
$controller = new CatalogController(new CatalogService($repo, $repo));
$ward = $controller->createWard(['code' => 'W-1', 'name' => 'Pediatría', 'location' => 'Piso 1']);
$check($ward['status'] === 201, 'ward creation failed');
$bed = $controller->createBed('W-1', ['code' => 'B-1', 'type' => 'standard']);
$check($bed['status'] === 201 && $bed['body']['item']->status === 'available', 'bed creation/default status failed');
echo "PASS camino feliz (sala, cama y estado inicial)\n";

$invalid = $controller->createBed('W-1', ['code' => 'B-2', 'type' => 'standard', 'status' => 'broken']);
$check($invalid['status'] === 422 && $invalid['body']['error']['code'] === 'BED_STATUS_INVALID', 'invalid bed state accepted');
echo "PASS regla de dominio (estado operativo inválido)\n";

$missing = $controller->createBed('W-404', ['code' => 'B-3', 'type' => 'standard']);
$check($missing['status'] === 404, 'bed created without ward');
$failed = (new CatalogController(new CatalogService(new FakeCatalogRepository(true), new FakeCatalogRepository(true))))
    ->createWard(['code' => 'W-2', 'name' => 'UCI', 'location' => 'Piso 2']);
$check($failed['status'] === 500 && $failed['body']['error']['code'] === 'PERSISTENCE_FAILURE', 'persistence failure not translated');
echo "PASS error de persistencia (fake)\n";
