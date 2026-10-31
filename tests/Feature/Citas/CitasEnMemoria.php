<?php

declare(strict_types=1);

namespace Tests\Feature\Citas;

use App\Exceptions\AppointmentWriteException;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryContract;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Doble de AppointmentRepositoryContract: reproduce en memoria las reglas que en HOSPITAL imponen la
 * exclusión GiST (solape) y los triggers de estado, y lanza las mismas AppointmentWriteException.
 */
final class CitasEnMemoria implements AppointmentRepositoryContract
{
    private const TRANSICIONES = ['scheduled' => ['confirmed', 'cancelled'], 'confirmed' => ['completed', 'cancelled']];

    /** @var array<string, Appointment> */
    public array $citas = [];

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $items = array_values(array_filter($this->citas, static fn (Appointment $c): bool => empty($filters['status'])
            || in_array($c->status, $filters['status'], true)));

        return new LengthAwarePaginator($items, count($items), (int) ($filters['per_page'] ?? 20));
    }

    public function find(string $appointmentId): Appointment
    {
        return $this->citas[$appointmentId] ?? throw (new ModelNotFoundException())->setModel(Appointment::class, [$appointmentId]);
    }

    public function create(array $attributes): Appointment
    {
        foreach ($this->citas as $c) {
            $activa = in_array($c->status, ['scheduled', 'confirmed'], true);
            $mismoActor = $c->local_patient_id === $attributes['local_patient_id'] || $c->doctor_profile_id === $attributes['doctor_profile_id'];
            $solapa = $c->starts_at->lt(Carbon::parse($attributes['ends_at'])) && Carbon::parse($attributes['starts_at'])->lt($c->ends_at);
            if ($activa && $mismoActor && $solapa) {
                throw AppointmentWriteException::new('appointments_overlap', 409, 'La cita se solapa con otra cita activa del mismo doctor o paciente.');
            }
        }
        $cita = (new Appointment())->forceFill($attributes + ['appointment_id' => (string) Str::uuid(), 'status' => 'scheduled']);

        return $this->citas[$cita->appointment_id] = $cita;
    }

    public function update(Appointment $appointment, array $attributes): Appointment
    {
        return $appointment->forceFill($attributes);
    }

    public function changeStatus(Appointment $appointment, string $status): Appointment
    {
        if (in_array($appointment->status, ['completed', 'cancelled'], true)) {
            throw AppointmentWriteException::new('appointments_finalized_immutable', 409, 'La cita finalizada ya no puede modificarse.');
        }
        if (! in_array($status, self::TRANSICIONES[$appointment->status] ?? [], true)) {
            throw AppointmentWriteException::new('appointments_status_time_check', 422, 'Transición de estado no válida.');
        }

        return $appointment->forceFill(['status' => $status]);
    }
}
