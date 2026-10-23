<?php

namespace App\Repositories\Contracts;

use App\Models\Appointment;
use Illuminate\Pagination\LengthAwarePaginator;

interface AppointmentRepositoryContract
{
    /**
     * @param array{status?: list<string>, doctor_profile_id?: string, local_patient_id?: string, date_from?: string, date_to?: string, per_page?: int} $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator;

    public function find(string $appointmentId): Appointment;

    /**
     * @param array{local_patient_id: string, doctor_profile_id: string, specialty_id: string, starts_at: string, ends_at: string, reason: string} $attributes
     */
    public function create(array $attributes): Appointment;

    /**
     * @param array{local_patient_id?: string, doctor_profile_id?: string, specialty_id?: string, starts_at?: string, ends_at?: string, reason?: string} $attributes
     */
    public function update(Appointment $appointment, array $attributes): Appointment;

    public function changeStatus(Appointment $appointment, string $status): Appointment;
}
