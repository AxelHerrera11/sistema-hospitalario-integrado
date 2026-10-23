<?php

namespace App\Repositories;

use App\Exceptions\AppointmentWriteException;
use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryContract;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentAppointmentRepository implements AppointmentRepositoryContract
{
    private const ALLOWED_TRANSITIONS = [
        Appointment::STATUS_SCHEDULED => [Appointment::STATUS_CONFIRMED, Appointment::STATUS_CANCELLED],
        Appointment::STATUS_CONFIRMED => [Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED],
        Appointment::STATUS_COMPLETED => [],
        Appointment::STATUS_CANCELLED => [],
    ];

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Appointment::query()->orderBy('starts_at');

        if (! empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }

        foreach (['doctor_profile_id', 'local_patient_id'] as $foreignId) {
            if (! empty($filters[$foreignId])) {
                $query->where($foreignId, $filters[$foreignId]);
            }
        }

        if (! empty($filters['date_from'])) {
            $query->where('ends_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('starts_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->paginate(min((int) ($filters['per_page'] ?? 20), 100));
    }

    public function find(string $appointmentId): Appointment
    {
        return Appointment::query()->findOrFail($appointmentId);
    }

    public function create(array $attributes): Appointment
    {
        return $this->write(function () use ($attributes): Appointment {
            $appointment = Appointment::query()->create($attributes);

            return $appointment->refresh();
        });
    }

    public function update(Appointment $appointment, array $attributes): Appointment
    {
        $this->assertNotFinalized($appointment);

        return $this->write(function () use ($appointment, $attributes): Appointment {
            $appointment->fill($attributes)->save();

            return $appointment->refresh();
        });
    }

    public function changeStatus(Appointment $appointment, string $status): Appointment
    {
        $this->assertNotFinalized($appointment);

        if (! in_array($status, self::ALLOWED_TRANSITIONS[$appointment->status] ?? [], true)) {
            throw AppointmentWriteException::new(
                'appointments_status_time_check',
                422,
                sprintf('La cita no puede pasar de "%s" a "%s".', $appointment->status, $status),
            );
        }

        return $this->write(function () use ($appointment, $status): Appointment {
            $appointment->status = $status;
            $appointment->save();

            return $appointment->refresh();
        });
    }

    private function assertNotFinalized(Appointment $appointment): void
    {
        $finalStatuses = [Appointment::STATUS_COMPLETED, Appointment::STATUS_CANCELLED];

        if (in_array($appointment->status, $finalStatuses, true)) {
            throw AppointmentWriteException::new(
                'appointments_finalized_immutable',
                409,
                'La cita finalizada ya no puede modificarse.',
            );
        }
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     *
     * @throws AppointmentWriteException
     */
    private function write(callable $operation): mixed
    {
        try {
            return $operation();
        } catch (QueryException $exception) {
            throw AppointmentWriteException::fromQueryException($exception);
        }
    }
}
