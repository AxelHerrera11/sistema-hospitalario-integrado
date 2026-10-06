<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionService
{
    /**
     * Crea una admisión hospitalaria y ocupa la cama asignada.
     *
     * @param  array{patient_id:int, bed_id:int, doctor_id:int}  $data
     */
    public function create(array $data, User $user, Tenant $tenant): Admission
    {
        return DB::transaction(function () use ($data, $user, $tenant): Admission {
            /*
             * Bloqueamos al paciente durante la operación para evitar que dos
             * solicitudes creen simultáneamente dos admisiones activas.
             */
            $patient = Patient::query()
                ->lockForUpdate()
                ->findOrFail($data['patient_id']);

            if (! $patient->medicalRecord()->exists()) {
                throw ValidationException::withMessages([
                    'patient_id' => [
                        'El paciente debe tener un expediente clínico antes de ser admitido.',
                    ],
                ]);
            }

            if ($patient->currentAdmission()->exists()) {
                throw ValidationException::withMessages([
                    'patient_id' => [
                        'El paciente ya tiene una admisión activa.',
                    ],
                ]);
            }

            /*
             * La cama también se bloquea mientras se crea la admisión para
             * evitar que dos pacientes reciban la misma cama simultáneamente.
             */
            $bed = Bed::query()
                ->lockForUpdate()
                ->findOrFail($data['bed_id']);

            if ($bed->status !== 'disponible') {
                throw ValidationException::withMessages([
                    'bed_id' => [
                        'La cama seleccionada no está disponible.',
                    ],
                ]);
            }

            $doctor = Doctor::query()->findOrFail($data['doctor_id']);

            /*
             * Serializamos la generación del correlativo por hospital.
             */
            Tenant::query()
                ->whereKey($tenant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $admission = Admission::query()->create([
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'bed_id' => $bed->id,
                'doctor_id' => $doctor->id,
                'admitted_by' => $user->id,
                'code' => $this->nextCode($tenant),
                'admitted_at' => now(),
                'discharged_at' => null,
                'status' => 'activa',
                'discharge_type' => null,
                'discharge_notes' => null,
            ]);

            $bed->update([
                'status' => 'ocupada',
            ]);

            return $admission->load([
                'patient',
                'bed.ward',
                'doctor.user',
            ]);
        });
    }

    private function nextCode(Tenant $tenant): string
    {
        $prefix = $this->prefixFor($tenant);
        $year = now()->format('Y');

        $lastSequence = Admission::query()
            ->where('code', 'like', sprintf('ADM-%s-%s%%', $prefix, $year))
            ->pluck('code')
            ->map(fn (string $code): int => $this->trailingNumber($code))
            ->max() ?? 0;

        return sprintf(
            'ADM-%s-%s%04d',
            $prefix,
            $year,
            $lastSequence + 1
        );
    }

    private function prefixFor(Tenant $tenant): string
    {
        return strtoupper(
            substr(
                (string) preg_replace('/[^a-z0-9]/i', '', $tenant->slug),
                0,
                6
            )
        );
    }

    private function trailingNumber(string $code): int
    {
        return preg_match('/(\d{4})$/', $code, $matches) === 1
            ? (int) $matches[1]
            : 0;
    }
}
