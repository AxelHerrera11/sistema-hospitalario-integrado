<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    private const STATUSES = ['pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio'];

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 50);
        $term = $request->query('q');

        $appointments = Appointment::query()
            ->with([
                'patient:id,tenant_id,code,first_name,last_name,dpi,phone',
                'doctor:id,tenant_id,user_id,specialty_id,license_number,phone',
                'doctor.user:id,tenant_id,name,email',
                'specialty:id,tenant_id,name',
            ])
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('doctor_id'), fn ($query, $doctorId) => $query->where('doctor_id', $doctorId))
            ->when($request->query('patient_id'), fn ($query, $patientId) => $query->where('patient_id', $patientId))
            ->when($request->query('specialty_id'), fn ($query, $specialtyId) => $query->where('specialty_id', $specialtyId))
            ->when($request->query('date_from'), fn ($query, $date) => $query->whereDate('scheduled_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, $date) => $query->whereDate('scheduled_at', '<=', $date))
            ->when($term, function ($query) use ($term): void {
                $query->where(function ($query) use ($term): void {
                    $query->whereSearch(['reason', 'notes'], $term)
                        ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->whereSearch(['code', 'first_name', 'last_name', 'dpi'], $term))
                        ->orWhereHas('doctor.user', fn ($userQuery) => $userQuery->whereSearch(['name', 'email'], $term))
                        ->orWhereHas('specialty', fn ($specialtyQuery) => $specialtyQuery->whereSearch('name', $term));
                });
            })
            ->orderBy('scheduled_at')
            ->paginate($perPage);

        return response()->json($appointments);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateAppointment($request);
        $this->ensureDoctorSpecialtyMatches($validated['doctor_id'], $validated['specialty_id']);

        $appointment = Appointment::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'pendiente',
        ]);

        return response()->json([
            'appointment' => $this->loadAppointment($appointment),
        ], 201);
    }

    public function update(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $this->validateAppointment($request, updating: true);
        $this->ensureDoctorSpecialtyMatches($validated['doctor_id'], $validated['specialty_id']);

        $appointment->update($validated);

        return response()->json([
            'appointment' => $this->loadAppointment($appointment->fresh()),
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update([
            'status' => 'cancelada',
            'notes' => $validated['notes'] ?? $appointment->notes,
        ]);

        return response()->json([
            'appointment' => $this->loadAppointment($appointment->fresh()),
        ]);
    }

    public function status(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(self::STATUSES)],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment->update([
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $appointment->notes,
        ]);

        return response()->json([
            'appointment' => $this->loadAppointment($appointment->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateAppointment(Request $request, bool $updating = false): array
    {
        $tenant = $request->attributes->get('tenant');

        return $request->validate([
            'patient_id' => [
                'required',
                'integer',
                Rule::exists('patients', 'id')->where('tenant_id', $tenant->id),
            ],
            'doctor_id' => [
                'required',
                'integer',
                Rule::exists('doctors', 'id')->where('tenant_id', $tenant->id),
            ],
            'specialty_id' => [
                'required',
                'integer',
                Rule::exists('specialties', 'id')->where('tenant_id', $tenant->id),
            ],
            'scheduled_at' => [$updating ? 'sometimes' : 'required', 'date'],
            'duration_min' => ['required', 'integer', 'min:10', 'max:240'],
            'status' => ['sometimes', 'string', Rule::in(self::STATUSES)],
            'reason' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function ensureDoctorSpecialtyMatches(int $doctorId, int $specialtyId): void
    {
        $doctor = Doctor::query()->findOrFail($doctorId);

        if ((int) $doctor->specialty_id !== $specialtyId) {
            throw ValidationException::withMessages([
                'specialty_id' => ['La especialidad indicada no coincide con la especialidad del médico.'],
            ]);
        }
    }

    private function loadAppointment(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'patient:id,tenant_id,code,first_name,last_name,dpi,phone',
            'doctor:id,tenant_id,user_id,specialty_id,license_number,phone',
            'doctor.user:id,tenant_id,name,email',
            'specialty:id,tenant_id,name',
        ]);
    }
}
