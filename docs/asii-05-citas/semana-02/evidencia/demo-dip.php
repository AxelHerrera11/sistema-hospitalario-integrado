<?php

declare(strict_types=1);
/*
 * Evidencia Semana 2 (DIP) — ASII-05
 * Ejecutar: php docs/asii-05-citas/semana-02/evidencia/demo-dip.php (solo PHP 8.2+)
 */

// ---- ANTES: el servicio crea su propia dependencia concreta (viola DIP) ----
final class EloquentAppointmentRepository
{
    public function crear(array $datos): array
    {
        throw new RuntimeException('requiere base de datos real');
    }
}
final class AppointmentServiceAntes
{
    public function solicitar(array $datos): array
    {
        $repo = new EloquentAppointmentRepository(); // dependencia concreta hardcodeada
        return $repo->crear($datos);
    }
}
// ---- DESPUÉS: se depende de una abstracción (aplica DIP) ----
interface AppointmentRepositoryContract
{
    public function crear(array $datos): array;
    public function buscar(string $id): ?array;
    public function buscarSolape(string $paciente, string $doctor, string $desde, string $hasta): bool;
}
final class AppointmentStatusPolicy
{
    public function puedeConfirmar(string $estado): bool { return $estado === 'scheduled'; }
    public function puedeCancelar(string $estado): bool
    { return in_array($estado, ['scheduled', 'confirmed'], true); }
}
final class InMemoryAppointmentRepository implements AppointmentRepositoryContract
{
    private array $citas = [];
    private int $seq = 0;
    public function crear(array $datos): array
    {
        $id = 'A-' . ++$this->seq;
        return $this->citas[$id] = ['id' => $id, 'status' => 'scheduled'] + $datos;
    }
    public function buscar(string $id): ?array { return $this->citas[$id] ?? null; }
    public function buscarSolape(string $paciente, string $doctor, string $desde, string $hasta): bool
    {
        foreach ($this->citas as $c) {
            $mismoPaciente = ($c['paciente'] ?? null) === $paciente;
            $mismoDoctor = ($c['doctor'] ?? null) === $doctor;
            $seSolapa = ($c['desde'] ?? '') < $hasta && $desde < ($c['hasta'] ?? '');
            if (($mismoPaciente || $mismoDoctor) && $seSolapa) { return true; }
        }
        return false;
    }
}
final class AppointmentController
{
    public function __construct(
        private AppointmentRepositoryContract $repositorio,
        private AppointmentStatusPolicy $politica
    ) {}
    public function store(array $datos): array
    {
        if ($this->repositorio->buscarSolape($datos['paciente'], $datos['doctor'], $datos['desde'], $datos['hasta'])) {
            throw new RuntimeException('409 appointments_overlap');
        }
        return $this->repositorio->crear($datos);
    }
    public function confirmar(string $id): array
    {
        $cita = $this->repositorio->buscar($id) ?? throw new RuntimeException('404');
        if (!$this->politica->puedeConfirmar($cita['status'])) { throw new RuntimeException('409 transicion invalida'); }
        return ['id' => $id, 'status' => 'confirmed'] + $cita;
    }
    public function cancelar(string $id): array
    {
        $cita = $this->repositorio->buscar($id) ?? throw new RuntimeException('404');
        if (!$this->politica->puedeCancelar($cita['status'])) { throw new RuntimeException('409 transicion invalida'); }
        return ['id' => $id, 'status' => 'cancelled'] + $cita;
    }
}
// ---- Datos ficticios y ejecución ----
try {
    (new AppointmentServiceAntes())->solicitar(['paciente' => 'P-1']);
    echo "ANTES acoplado a Eloquent: sin error\n";
} catch (RuntimeException $e) {
    echo 'ANTES acoplado a Eloquent: ', $e->getMessage(), " (no inyectable)\n";
}
$repo = new InMemoryAppointmentRepository();
$ctrl = new AppointmentController($repo, new AppointmentStatusPolicy());
$cita = $ctrl->store(['paciente' => 'P-1', 'doctor' => 'D-1', 'desde' => '2026-10-21T09:00', 'hasta' => '2026-10-21T09:30']);
echo 'DESPUÉS registrar cita: ', $cita['id'], ' ', $cita['status'], "\n";
try {
    $ctrl->store(['paciente' => 'P-1', 'doctor' => 'D-9', 'desde' => '2026-10-21T09:15', 'hasta' => '2026-10-21T09:45']);
    echo "DESPUÉS solape: sin error\n";
} catch (RuntimeException $e) {
    echo 'DESPUÉS solape detectado: ', $e->getMessage(), "\n";
}
echo 'DESPUÉS confirmar: ', $ctrl->confirmar($cita['id'])['status'], "\n";
echo 'DESPUÉS cancelar: ', $ctrl->cancelar($cita['id'])['status'], "\n";
$otro = new AppointmentController(new InMemoryAppointmentRepository(), new AppointmentStatusPolicy());
echo 'DESPUÉS misma logica con otro adaptador: ', (($otro->store(['paciente' => 'P-2', 'doctor' => 'D-2', 'desde' => 'x', 'hasta' => 'y'])['status']) === 'scheduled' ? 'OK' : 'FALLA'), "\n";
