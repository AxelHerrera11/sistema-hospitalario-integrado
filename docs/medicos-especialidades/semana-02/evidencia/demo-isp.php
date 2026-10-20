<?php

declare(strict_types=1);

/*
 * ASII-04 · Semana 2 · Evidencia verificable de ISP. PHP 8.2+, sin framework ni BD; datos ficticios.
 * Ejecutar: php docs/medicos-especialidades/semana-02/evidencia/demo-isp.php
 * Compara una interfaz "gorda" DoctorDirectory (antes) con interfaces pequeñas por cliente (después).
 */

final readonly class Result { public function __construct(public int $status, public string $code = 'OK', public array $data = []) {} }

// ANTES: una interfaz con ocho métodos que todos los clientes deben conocer o implementar.
interface DoctorDirectory
{
    public function licenseExists(string $license): bool;
    public function createDoctor(string $doctor, string $license, string $primarySpecialty): void;
    public function specialtiesOf(string $doctor): array; // specialty => is_primary
    public function associate(string $doctor, string $specialty, bool $primary): void;
    public function isSpecialtyActive(string $specialty): bool;
    public function doctorsWith(string $specialty): array;
    public function scheduleSlots(string $doctor): array;
    public function busySlots(string $doctor): array;
}

final class DoctorServiceAntes
{
    public function __construct(private DoctorDirectory $dir) {}
    public function register(string $doctor, string $license, string $specialty): Result
    {
        if ($this->dir->licenseExists($license)) { return new Result(409, 'LICENSE_ALREADY_REGISTERED'); }
        if (!$this->dir->isSpecialtyActive($specialty)) { return new Result(422, 'SPECIALTY_INACTIVE'); }
        $this->dir->createDoctor($doctor, $license, $specialty);
        return new Result(201);
    }
    public function associate(string $doctor, string $specialty, bool $primary): Result
    {
        if (!$this->dir->isSpecialtyActive($specialty)) { return new Result(422, 'SPECIALTY_INACTIVE'); }
        $current = $this->dir->specialtiesOf($doctor);
        if (isset($current[$specialty])) { return new Result(409, 'SPECIALTY_ALREADY_ASSOCIATED'); }
        if ($primary && in_array(true, $current, true)) { return new Result(409, 'PRIMARY_SPECIALTY_CONFLICT'); }
        $this->dir->associate($doctor, $specialty, $primary);
        return new Result(201);
    }
}
final class AvailabilityServiceAntes
{
    public function __construct(private DoctorDirectory $dir) {}
    public function search(string $specialty): Result
    {
        if (!$this->dir->isSpecialtyActive($specialty)) { return new Result(422, 'SPECIALTY_INACTIVE'); }
        $out = [];
        foreach ($this->dir->doctorsWith($specialty) as $d) { $out[$d] = array_values(array_diff($this->dir->scheduleSlots($d), $this->dir->busySlots($d))); }
        return new Result(200, $out === [] ? 'NO_DOCTORS_FOR_SPECIALTY' : 'OK', $out);
    }
}

// DESPUÉS: una interfaz por necesidad de cliente.
interface DoctorProfileStore { public function licenseExists(string $license): bool; public function createDoctor(string $doctor, string $license, string $primarySpecialty): void; }
interface DoctorSpecialtyStore { public function specialtiesOf(string $doctor): array; public function associate(string $doctor, string $specialty, bool $primary): void; }
interface SpecialtyCatalog { public function isSpecialtyActive(string $specialty): bool; }
interface DoctorsBySpecialty { public function doctorsWith(string $specialty): array; }
interface DoctorScheduleProvider { public function scheduleSlots(string $doctor): array; }
interface AppointmentReader { public function busySlots(string $doctor): array; }
final class DoctorRegistrar // UC-MED-01
{
    public function __construct(private DoctorProfileStore $profiles, private SpecialtyCatalog $catalog) {}
    public function register(string $doctor, string $license, string $specialty): Result
    {
        if ($this->profiles->licenseExists($license)) { return new Result(409, 'LICENSE_ALREADY_REGISTERED'); }
        if (!$this->catalog->isSpecialtyActive($specialty)) { return new Result(422, 'SPECIALTY_INACTIVE'); }
        $this->profiles->createDoctor($doctor, $license, $specialty);
        return new Result(201);
    }
}
final class SpecialtyAssociator // UC-MED-02 y 03
{
    public function __construct(private DoctorSpecialtyStore $specialties, private SpecialtyCatalog $catalog) {}
    public function associate(string $doctor, string $specialty, bool $primary): Result
    {
        if (!$this->catalog->isSpecialtyActive($specialty)) { return new Result(422, 'SPECIALTY_INACTIVE'); }
        $current = $this->specialties->specialtiesOf($doctor);
        if (isset($current[$specialty])) { return new Result(409, 'SPECIALTY_ALREADY_ASSOCIATED'); }
        if ($primary && in_array(true, $current, true)) { return new Result(409, 'PRIMARY_SPECIALTY_CONFLICT'); }
        $this->specialties->associate($doctor, $specialty, $primary);
        return new Result(201);
    }
}
final class AvailabilityService // UC-MED-05 y 06 (solo lectura)
{
    public function __construct(private SpecialtyCatalog $catalog, private DoctorsBySpecialty $doctors, private DoctorScheduleProvider $schedule, private AppointmentReader $appointments) {}
    public function search(string $specialty): Result
    {
        if (!$this->catalog->isSpecialtyActive($specialty)) { return new Result(422, 'SPECIALTY_INACTIVE'); }
        $out = [];
        foreach ($this->doctors->doctorsWith($specialty) as $d) { $out[$d] = array_values(array_diff($this->schedule->scheduleSlots($d), $this->appointments->busySlots($d))); }
        return new Result(200, $out === [] ? 'NO_DOCTORS_FOR_SPECIALTY' : 'OK', $out);
    }
}

// Un adaptador puede implementar varias interfaces pequeñas; la agenda solo implementa las de lectura.
final class MemoryDirectory implements DoctorDirectory, DoctorProfileStore, DoctorSpecialtyStore, SpecialtyCatalog, DoctorsBySpecialty, DoctorScheduleProvider, AppointmentReader
{
    private array $licenses = [];
    private array $assoc = [];
    public function __construct(private array $active, private array $schedule, private array $busy) {}
    public function licenseExists(string $license): bool { return in_array($license, $this->licenses, true); }
    public function createDoctor(string $doctor, string $license, string $primarySpecialty): void { $this->licenses[$doctor] = $license; $this->assoc[$doctor] = [$primarySpecialty => true]; }
    public function specialtiesOf(string $doctor): array { return $this->assoc[$doctor] ?? []; }
    public function associate(string $doctor, string $specialty, bool $primary): void { $this->assoc[$doctor][$specialty] = $primary; }
    public function isSpecialtyActive(string $specialty): bool { return in_array($specialty, $this->active, true); }
    public function doctorsWith(string $specialty): array { return array_keys(array_filter($this->assoc, fn ($s) => isset($s[$specialty]))); }
    public function scheduleSlots(string $doctor): array { return $this->schedule[$doctor] ?? []; }
    public function busySlots(string $doctor): array { return $this->busy[$doctor] ?? []; }
}
class AgendaReadModel implements SpecialtyCatalog, DoctorsBySpecialty, DoctorScheduleProvider, AppointmentReader
{
    public function __construct(private array $doctors, private array $schedule, private array $busy) {}
    public function isSpecialtyActive(string $specialty): bool { return isset($this->doctors[$specialty]); }
    public function doctorsWith(string $specialty): array { return $this->doctors[$specialty] ?? []; }
    public function scheduleSlots(string $doctor): array { return $this->schedule[$doctor] ?? []; }
    public function busySlots(string $doctor): array { return $this->busy[$doctor] ?? []; }
}
// ANTES: con la interfaz gorda, el mismo adaptador de solo lectura queda obligado a "implementar" escrituras.
final class AgendaDirectoryAntes extends AgendaReadModel implements DoctorDirectory
{
    public function licenseExists(string $license): bool { throw new LogicException('no soportado'); }
    public function createDoctor(string $doctor, string $license, string $primarySpecialty): void { throw new LogicException('no soportado'); }
    public function specialtiesOf(string $doctor): array { throw new LogicException('no soportado'); }
    public function associate(string $doctor, string $specialty, bool $primary): void { throw new LogicException('no soportado'); }
}

// Métrica ISP: métodos de las interfaces recibidas que el cliente nunca llama.
function unusedMethods(string $client): array
{
    $ref = new ReflectionClass($client);
    $src = implode('', array_slice(file(__FILE__), $ref->getStartLine() - 1, $ref->getEndLine() - $ref->getStartLine() + 1));
    $unused = [];
    foreach ($ref->getConstructor()->getParameters() as $p) {
        foreach ((new ReflectionClass((string) $p->getType()))->getMethods() as $m) {
            if (!str_contains($src, '->' . $m->getName() . '(')) { $unused[] = $m->getName(); }
        }
    }
    return $unused;
}

$failures = 0;
function check(string $label, bool $ok): void { global $failures; echo ($ok ? 'PASS' : 'FAIL') . "  {$label}\n"; $failures += $ok ? 0 : 1; }

$slots = ['dr.ana' => ['08:00', '08:30', '09:00'], 'dr.luis' => ['10:00']];
$busy = ['dr.ana' => ['08:30']];
[$old, $new] = [new MemoryDirectory(['CARD', 'MED-INT', 'NEURO'], $slots, $busy), new MemoryDirectory(['CARD', 'MED-INT', 'NEURO'], $slots, $busy)];
[$svcA, $avA] = [new DoctorServiceAntes($old), new AvailabilityServiceAntes($old)];
[$reg, $asoc, $av] = [new DoctorRegistrar($new, $new), new SpecialtyAssociator($new, $new), new AvailabilityService($new, $new, $new, $new)];
$casos = [ // [etiqueta, antes, después, estado esperado]
    ['registrar dr.ana con CARD principal → 201', fn () => $svcA->register('dr.ana', 'COL-1', 'CARD'), fn () => $reg->register('dr.ana', 'COL-1', 'CARD'), 201],
    ['licencia repetida → 409', fn () => $svcA->register('dr.otro', 'COL-1', 'CARD'), fn () => $reg->register('dr.otro', 'COL-1', 'CARD'), 409],
    ['especialidad inactiva → 422', fn () => $svcA->register('dr.luis', 'COL-2', 'PED'), fn () => $reg->register('dr.luis', 'COL-2', 'PED'), 422],
    ['asociar MED-INT adicional → 201', fn () => $svcA->associate('dr.ana', 'MED-INT', false), fn () => $asoc->associate('dr.ana', 'MED-INT', false), 201],
    ['misma especialidad otra vez → 409', fn () => $svcA->associate('dr.ana', 'CARD', false), fn () => $asoc->associate('dr.ana', 'CARD', false), 409],
    ['NEURO como segunda principal → 409', fn () => $svcA->associate('dr.ana', 'NEURO', true), fn () => $asoc->associate('dr.ana', 'NEURO', true), 409],
    ['disponibilidad CARD sin la franja ocupada → 200', fn () => $avA->search('CARD'), fn () => $av->search('CARD'), 200],
];

echo "== Diseño después reproduce el comportamiento del diseño antes ==\n";
foreach ($casos as [$label, $a, $b, $esperado]) {
    [$ra, $rb] = [$a(), $b()];
    check($label, $ra->status === $esperado && $rb->status === $esperado && $ra->code === $rb->code && $ra->data === $rb->data);
}

echo "\n== ISP: ningún cliente depende de métodos que no usa ==\n";
check('antes: AvailabilityServiceAntes recibe 4 métodos de escritura que no usa', count(unusedMethods(AvailabilityServiceAntes::class)) === 4);
check('antes: DoctorServiceAntes recibe 3 métodos de lectura de agenda que no usa', count(unusedMethods(DoctorServiceAntes::class)) === 3);
foreach ([DoctorRegistrar::class, SpecialtyAssociator::class, AvailabilityService::class] as $c) {
    check("después: {$c} usa todos los métodos de sus interfaces", unusedMethods($c) === []);
}
$agenda = ['CARD' => ['dr.ana']];
try { (new AgendaDirectoryAntes($agenda, $slots, $busy))->createDoctor('x', 'y', 'z'); $lanza = false; } catch (LogicException) { $lanza = true; }
check('antes: el adaptador de solo lectura lanza LogicException en createDoctor()', $lanza);
check('después: AgendaReadModel no implementa escritura y sirve a AvailabilityService', !method_exists(AgendaReadModel::class, 'createDoctor')
    && (new AvailabilityService(...array_fill(0, 4, new AgendaReadModel($agenda, $slots, $busy))))->search('CARD')->data === ['dr.ana' => ['08:00', '09:00']]);

echo "\n" . ($failures === 0 ? 'OK: todas las aserciones pasaron' : "ERROR: {$failures} aserciones fallaron") . "\n";
exit($failures === 0 ? 0 : 1);
