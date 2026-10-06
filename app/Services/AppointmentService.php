<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Doctor;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public const STATUSES = ['pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio'];

    private const OPEN_STATUSES = ['pendiente', 'confirmada'];

    private const TRANSITIONS = [
        'pendiente' => ['pendiente', 'confirmada', 'cancelada', 'no_asistio'],
        'confirmada' => ['confirmada', 'completada', 'cancelada', 'no_asistio'],
        'completada' => ['completada'],
        'cancelada' => ['cancelada'],
        'no_asistio' => ['no_asistio'],
    ];

    /** @param array<string, mixed> $data */
    public function create(array $data): Appointment
    {
        $this->ensureDoctorSpecialtyMatches((int) $data['doctor_id'], (int) $data['specialty_id']);
        $this->ensureFutureSchedule($data['scheduled_at']);
        $this->ensureDoctorIsAvailable((int) $data['doctor_id'], $data['scheduled_at'], (int) $data['duration_min']);

        return Appointment::query()->create([
            ...$data,
            'status' => 'pendiente',
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(Appointment $appointment, array $data): Appointment
    {
        $doctorId = (int) $data['doctor_id'];
        $specialtyId = (int) $data['specialty_id'];
        $scheduledAt = $data['scheduled_at'];
        $durationMin = (int) $data['duration_min'];

        $this->ensureDoctorSpecialtyMatches($doctorId, $specialtyId);
        $this->ensureFutureSchedule($scheduledAt);
        $this->ensureDoctorIsAvailable($doctorId, $scheduledAt, $durationMin, $appointment);

        $appointment->update($data);

        return $appointment->fresh();
    }

    public function cancel(Appointment $appointment, ?string $notes = null): Appointment
    {
        $this->ensureTransitionAllowed($appointment, 'cancelada');

        $appointment->update([
            'status' => 'cancelada',
            'notes' => $notes ?? $appointment->notes,
        ]);

        return $appointment->fresh();
    }

    public function changeStatus(Appointment $appointment, string $status, ?string $notes = null): Appointment
    {
        $this->ensureTransitionAllowed($appointment, $status);

        $appointment->update([
            'status' => $status,
            'notes' => $notes ?? $appointment->notes,
        ]);

        return $appointment->fresh();
    }

    public function ensureDoctorSpecialtyMatches(int $doctorId, int $specialtyId): void
    {
        $doctor = Doctor::query()->findOrFail($doctorId);

        if ((int) $doctor->specialty_id !== $specialtyId) {
            throw ValidationException::withMessages([
                'specialty_id' => ['La especialidad indicada no coincide con la especialidad del médico.'],
            ]);
        }
    }

    private function ensureFutureSchedule(mixed $scheduledAt): void
    {
        if (Carbon::parse($scheduledAt)->lte(now())) {
            throw ValidationException::withMessages([
                'scheduled_at' => ['La fecha y hora de la cita debe ser futura.'],
            ]);
        }
    }

    private function ensureTransitionAllowed(Appointment $appointment, string $nextStatus): void
    {
        $currentStatus = $appointment->status;

        if (! in_array($nextStatus, self::TRANSITIONS[$currentStatus] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ["No se puede cambiar una cita de {$currentStatus} a {$nextStatus}."],
            ]);
        }
    }

    private function ensureDoctorIsAvailable(
        int $doctorId,
        mixed $scheduledAt,
        int $durationMin,
        ?Appointment $currentAppointment = null
    ): void {
        $start = Carbon::parse($scheduledAt);
        $end = $start->copy()->addMinutes($durationMin);
        $candidateFrom = $start->copy()->subMinutes(240);

        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('status', self::OPEN_STATUSES)
            ->where('scheduled_at', '>=', $candidateFrom)
            ->where('scheduled_at', '<', $end)
            ->when($currentAppointment, fn ($query) => $query->whereKeyNot($currentAppointment->id))
            ->get(['id', 'scheduled_at', 'duration_min']);

        foreach ($appointments as $appointment) {
            $existingStart = Carbon::parse($appointment->scheduled_at);
            $existingEnd = $existingStart->copy()->addMinutes((int) $appointment->duration_min);

            if ($this->rangesOverlap($start, $end, $existingStart, $existingEnd)) {
                throw ValidationException::withMessages([
                    'scheduled_at' => ['El médico ya tiene una cita programada en ese horario.'],
                ]);
            }
        }
    }

    private function rangesOverlap(
        CarbonInterface $start,
        CarbonInterface $end,
        CarbonInterface $existingStart,
        CarbonInterface $existingEnd
    ): bool {
        return $start->lt($existingEnd) && $end->gt($existingStart);
    }
}
