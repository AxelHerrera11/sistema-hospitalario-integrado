<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeAppointmentStatusRequest;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(private readonly AppointmentService $appointments) {}

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

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->appointments->create($request->validated());

        return response()->json([
            'appointment' => $this->loadAppointment($appointment),
        ], 201);
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): JsonResponse
    {
        $appointment = $this->appointments->update($appointment, $request->validated());

        return response()->json([
            'appointment' => $this->loadAppointment($appointment),
        ]);
    }

    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $appointment = $this->appointments->cancel($appointment, $validated['notes'] ?? null);

        return response()->json([
            'appointment' => $this->loadAppointment($appointment),
        ]);
    }

    public function status(ChangeAppointmentStatusRequest $request, Appointment $appointment): JsonResponse
    {
        $validated = $request->validated();
        $appointment = $this->appointments->changeStatus($appointment, $validated['status'], $validated['notes'] ?? null);

        return response()->json([
            'appointment' => $this->loadAppointment($appointment),
        ]);
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
