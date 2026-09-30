<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Allergy;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\Medication;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\SoapNote;
use App\Models\Specialty;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VitalSign;
use App\Models\Ward;
use Carbon\Carbon;
use Database\Seeders\Concerns\GeneratesTenantCodes;
use Database\Seeders\Concerns\SeedsDemoReferenceData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder2026 extends Seeder
{
    use GeneratesTenantCodes;
    use SeedsDemoReferenceData;

    private const PATIENTS_PER_TENANT = 25;

    public function run(): void
    {
        $tenants = Tenant::query()->get();

        foreach ($tenants as $tenant) {
            $this->command?->info("→ Demo 2026: {$tenant->name}");
            $this->seedTenant($tenant);
        }

        $this->command?->info('✓ Demo data 2026 generada.');
    }

    private function seedTenant(Tenant $tenant): void
    {
        $prefix = $this->tenantPrefix($tenant);

        $wards = $this->seedWards($tenant);
        $beds = $this->seedBeds($tenant, $wards, $prefix);
        $specialties = $this->seedSpecialties($tenant);

        $staff = $this->seedStaff($tenant, $specialties);
        $labTests = $this->seedLabTests($tenant);

        $patients = $this->seedPatients($tenant, $prefix);
        $this->seedMedicalRecords($patients, $tenant, $prefix);

        $admissions = $this->seedAdmissions($patients, $beds, $staff, $tenant, $prefix);
        $this->finalizeBedStatuses($beds, $admissions);

        $this->seedAllergies($patients, $staff['admin'], $tenant);
        $soapNotes = $this->seedSoapAndPrescriptions($patients, $admissions, $staff, $tenant);
        $this->seedVitalSigns($patients, $staff['admin'], $tenant);
        $this->seedLaboratory($patients, $soapNotes, $labTests, $staff, $tenant, $prefix);
        $this->seedAppointments($patients, $staff, $specialties, $tenant);
        $this->seedBedTransfers($admissions, $beds, $staff['admin'], $tenant, $prefix);
        $this->seedAuditAndNotifications($patients, $staff, $tenant);
    }

    private function seedWards(Tenant $tenant): Collection
    {
        $names = ['Emergencias', 'UCI', 'Pediatría', 'Medicina Interna', 'Cirugía', 'Maternidad'];

        return collect($names)->map(fn (string $name) => Ward::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $name],
            [
                'tenant_id' => $tenant->id,
                'name' => $name,
                'floor' => (string) random_int(1, 5),
                'building' => ['A', 'B', 'C'][array_rand(['A', 'B', 'C'])],
            ]
        ));
    }

    private function seedBeds(Tenant $tenant, Collection $wards, string $prefix): Collection
    {
        $beds = collect();
        $counts = ['Emergencias' => 8, 'UCI' => 6, 'Pediatría' => 8, 'Medicina Interna' => 12, 'Cirugía' => 8, 'Maternidad' => 6];
        $prefixes = ['Emergencias' => 'E', 'UCI' => 'UCI', 'Pediatría' => 'PED', 'Medicina Interna' => 'MG', 'Cirugía' => 'CIR', 'Maternidad' => 'MAT'];

        foreach ($wards as $ward) {
            $count = $counts[$ward->name] ?? 6;
            $wardPrefix = $prefixes[$ward->name] ?? 'GEN';

            for ($i = 1; $i <= $count; $i++) {
                $code = sprintf('CAM-%s-%s-%02d', $prefix, $wardPrefix, $i);
                $beds->push(Bed::query()->firstOrCreate(
                    ['code' => $code],
                    [
                        'tenant_id' => $tenant->id,
                        'ward_id' => $ward->id,
                        'code' => $code,
                        'status' => 'disponible',
                        'notes' => null,
                    ]
                ));
            }
        }

        return $beds;
    }

    private function seedSpecialties(Tenant $tenant): Collection
    {
        $names = ['Medicina Interna', 'Pediatría', 'Cirugía General', 'Cardiología', 'Ginecología', 'Medicina Familiar'];

        return collect($names)->map(fn (string $name) => Specialty::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $name],
            ['tenant_id' => $tenant->id, 'name' => $name, 'description' => null]
        ));
    }

    /** @return array{admin: User, labTech: User, recep: User, doctors: Collection<int, Doctor>} */
    private function seedStaff(Tenant $tenant, Collection $specialties): array
    {
        $prefix = $this->tenantPrefix($tenant);

        $admin = $this->seedUserWithRole($tenant, "admin+{$tenant->slug}@demo.local", 'Administrador Demo', 'Admin');
        $labTech = $this->seedUserWithRole($tenant, "lab+{$tenant->slug}@demo.local", 'Técnico Laboratorio Demo', 'TecnicoLab');
        $recep = $this->seedUserWithRole($tenant, "recep+{$tenant->slug}@demo.local", 'Recepción Demo', 'Recepcionista');

        $doctors = collect(range(1, 5))->map(function (int $i) use ($tenant, $specialties, $prefix) {
            $user = $this->seedUserWithRole(
                $tenant,
                "doctor{$i}+{$tenant->slug}@demo.local",
                "Dr(a). Demo {$i}",
                'Médico'
            );

            return Doctor::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id' => $user->id,
                    'tenant_id' => $tenant->id,
                    'specialty_id' => $specialties[($i - 1) % $specialties->count()]->id,
                    'license_number' => sprintf('MED-%s-%05d', $prefix, 1000 + $i),
                    'phone' => $this->generateGuatemalaPhone(),
                ]
            );
        });

        return compact('admin', 'labTech', 'recep', 'doctors');
    }

    private function seedUserWithRole(Tenant $tenant, string $email, string $name, string $role): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenant->id,
                'name' => $name,
                'password' => Hash::make('password'),
            ]
        );

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }

        return $user;
    }

    private function seedPatients(Tenant $tenant, string $prefix): Collection
    {
        $male = $this->guatemalanFirstNamesMale();
        $female = $this->guatemalanFirstNamesFemale();
        $lastNames = $this->guatemalanLastNames();

        return collect(range(1, self::PATIENTS_PER_TENANT))->map(function (int $i) use ($tenant, $prefix, $male, $female, $lastNames) {
            $gender = ['M', 'F', 'otro'][$i % 3];
            $firstName = $gender === 'F' ? $female[($i - 1) % count($female)] : $male[($i - 1) % count($male)];
            $lastName = $lastNames[($i - 1) % count($lastNames)] . ' ' . $lastNames[($i + 3) % count($lastNames)];

            return Patient::query()->create([
                'tenant_id' => $tenant->id,
                'code' => sprintf('PAC-%s-%04d', $prefix, $i),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'birth_date' => Carbon::now()->subYears(random_int(18, 85))->subDays(random_int(0, 364))->toDateString(),
                'gender' => $gender,
                'dpi' => $this->generateDpi(),
                'nit' => null,
                'phone' => $this->generateGuatemalaPhone(),
                'email' => strtolower(Str::slug($firstName)) . '.' . $i . '@demo.local',
                'address' => $this->randomAddress(),
                'insurance_company' => random_int(0, 1) ? 'IGSS' : 'Seguro Privado',
                'insurance_policy' => random_int(0, 1) ? 'POL-' . random_int(10000, 99999) : null,
                'emergency_contact_name' => fake()->name(),
                'emergency_contact_phone' => $this->generateGuatemalaPhone(),
                'blood_type' => $this->randomBloodType(),
                'notes' => random_int(0, 4) === 0 ? 'Paciente demo — sin antecedentes relevantes adicionales.' : null,
            ]);
        });
    }

    private function seedMedicalRecords(Collection $patients, Tenant $tenant, string $prefix): void
    {
        $patients->each(function (Patient $patient, int $idx) use ($tenant, $prefix) {
            MedicalRecord::query()->firstOrCreate(
                ['patient_id' => $patient->id],
                [
                    'tenant_id' => $tenant->id,
                    'patient_id' => $patient->id,
                    'record_number' => sprintf('EXP-%s-%05d', $prefix, $idx + 1),
                    'opened_at' => Carbon::now()->subDays(random_int(1, 90))->toDateString(),
                    'background' => 'Sin antecedentes patológicos de relevancia documentados en admisión.',
                    'family_background' => null,
                    'surgical_history' => null,
                    'obstetric_history' => null,
                ]
            );
        });
    }

    /** @return Collection<int, Admission> */
    private function seedAdmissions(Collection $patients, Collection $beds, array $staff, Tenant $tenant, string $prefix): Collection
    {
        $admitCount = min(12, $patients->count(), $beds->count());
        $admissions = collect();
        $year = Carbon::now()->format('Y');

        for ($i = 0; $i < $admitCount; $i++) {
            $patient = $patients[$i];
            $bed = $beds[$i];
            $doctor = $staff['doctors'][$i % $staff['doctors']->count()];

            $admission = Admission::query()->create([
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'bed_id' => $bed->id,
                'doctor_id' => $doctor->id,
                'admitted_by' => $staff['admin']->id,
                'code' => sprintf('ADM-%s-%s%04d', $prefix, $year, $i + 1),
                'admitted_at' => Carbon::now()->subDays(random_int(0, 14))->subHours(random_int(0, 12)),
                'discharged_at' => null,
                'status' => 'activa',
                'discharge_type' => null,
                'discharge_notes' => null,
            ]);

            $bed->update(['status' => 'ocupada']);
            $admissions->push($admission);
        }

        // Algunas altas históricas
        for ($j = $admitCount; $j < min($admitCount + 3, $patients->count()); $j++) {
            if ($j >= $beds->count()) {
                break;
            }
            $admissions->push(Admission::query()->create([
                'tenant_id' => $tenant->id,
                'patient_id' => $patients[$j]->id,
                'bed_id' => $beds[$j]->id,
                'doctor_id' => $staff['doctors'][$j % $staff['doctors']->count()]->id,
                'admitted_by' => $staff['admin']->id,
                'code' => sprintf('ADM-%s-%s%04d', $prefix, $year, 100 + $j),
                'admitted_at' => Carbon::now()->subDays(random_int(20, 40)),
                'discharged_at' => Carbon::now()->subDays(random_int(1, 10)),
                'status' => 'alta',
                'discharge_type' => 'medica',
                'discharge_notes' => 'Alta médica programada.',
            ]));
        }

        return $admissions;
    }

    private function finalizeBedStatuses(Collection $beds, Collection $admissions): void
    {
        $occupiedBedIds = $admissions
            ->where('status', 'activa')
            ->pluck('bed_id')
            ->all();

        $freeBeds = $beds->filter(fn (Bed $b) => ! in_array($b->id, $occupiedBedIds, true))->values();

        $maintenanceCount = (int) ceil($freeBeds->count() * 0.08);
        $cleaningCount = (int) ceil($freeBeds->count() * 0.05);

        $freeBeds->shuffle()->slice(0, $maintenanceCount)->each(fn (Bed $b) => $b->update(['status' => 'mantenimiento']));
        $freeBeds->shuffle()->slice(0, $cleaningCount)->each(fn (Bed $b) => $b->update(['status' => 'limpieza']));
    }

    private function seedAllergies(Collection $patients, User $admin, Tenant $tenant): void
    {
        $catalog = $this->allergyCatalog();
        $severities = ['leve', 'moderada', 'grave', 'anafilaxis'];

        $patients->each(function (Patient $patient) use ($catalog, $severities, $admin, $tenant) {
            if (random_int(1, 10) > 6) {
                return;
            }

            $types = array_keys($catalog);
            $count = random_int(1, 2);

            for ($a = 0; $a < $count; $a++) {
                $type = $types[array_rand($types)];
                $entry = $catalog[$type][array_rand($catalog[$type])];

                Allergy::query()->create([
                    'tenant_id' => $tenant->id,
                    'patient_id' => $patient->id,
                    'registered_by' => $admin->id,
                    'allergen' => $entry['allergen'],
                    'allergen_type' => $type,
                    'severity' => $severities[array_rand($severities)],
                    'reaction' => $entry['reaction'],
                    'active' => true,
                ]);
            }
        });
    }

    /** @return Collection<int, SoapNote> */
    private function seedSoapAndPrescriptions(Collection $patients, Collection $admissions, array $staff, Tenant $tenant): Collection
    {
        $soapNotes = collect();
        $diagnoses = $this->diagnosesCie10();
        $medications = Medication::query()->where('tenant_id', $tenant->id)->get();

        $patients->each(function (Patient $patient, int $idx) use ($admissions, $staff, $tenant, &$soapNotes, $diagnoses, $medications) {
            $mr = $patient->medicalRecord;
            if (! $mr) {
                return;
            }

            $admission = $admissions->firstWhere('patient_id', $patient->id);
            $doctor = $staff['doctors'][$idx % $staff['doctors']->count()];

            $soap = SoapNote::query()->create([
                'tenant_id' => $tenant->id,
                'medical_record_id' => $mr->id,
                'doctor_id' => $doctor->id,
                'admission_id' => $admission?->id,
                'subjective' => 'Paciente refiere malestar general y síntomas compatibles con cuadro actual.',
                'objective' => 'Paciente consciente, orientado. Signos vitales estables al examen.',
                'assessment' => 'Impresión diagnóstica según hallazgos clínicos y paraclínicos.',
                'plan' => 'Monitoreo, tratamiento sintomático y seguimiento según protocolo.',
                'electronic_sign' => 'DEMO-SIGN',
                'signed_at' => Carbon::now()->subDays(random_int(0, 5)),
            ]);

            $soapNotes->push($soap);

            $diag = $diagnoses[array_rand($diagnoses)];
            DB::table('diagnoses')->insert([
                'tenant_id' => $tenant->id,
                'soap_note_id' => $soap->id,
                'cie10_code' => $diag['code'],
                'description' => $diag['desc'],
                'type' => $diag['type'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($medications->isEmpty()) {
                return;
            }

            $rxCount = random_int(1, 3);
            $patientAllergens = $patient->allergies()->where('active', true)->pluck('allergen')->map(fn ($a) => strtolower($a));

            for ($r = 0; $r < $rxCount; $r++) {
                $med = $medications->random();
                $blocked = $patientAllergens->contains(fn ($a) => str_contains(strtolower($med->name), $a) || str_contains(strtolower($med->generic_name ?? ''), $a));

                Prescription::query()->create([
                    'tenant_id' => $tenant->id,
                    'soap_note_id' => $soap->id,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'medication_id' => $med->id,
                    'dose' => '500mg',
                    'frequency' => 'cada 8 horas',
                    'duration_days' => random_int(3, 10),
                    'route' => 'oral',
                    'indications' => 'Tomar con alimentos.',
                    'blocked' => $blocked,
                    'blocked_reason' => $blocked ? 'Alergia registrada al medicamento o familia.' : null,
                    'electronic_sign' => $blocked ? null : 'DEMO-SIGN',
                    'signed_at' => $blocked ? null : Carbon::now(),
                ]);
            }
        });

        return $soapNotes;
    }

    private function seedVitalSigns(Collection $patients, User $admin, Tenant $tenant): void
    {
        $patients->each(function (Patient $patient) use ($admin, $tenant) {
            $mr = $patient->medicalRecord;
            if (! $mr) {
                return;
            }

            $admissionId = $patient->currentAdmission?->id;
            $readings = random_int(5, 7);

            for ($i = 0; $i < $readings; $i++) {
                $hasAlert = random_int(1, 100) <= 15;
                $o2 = $hasAlert ? random_int(85, 92) : random_int(94, 100);
                $sys = $hasAlert ? random_int(150, 190) : random_int(100, 135);

                VitalSign::query()->create([
                    'tenant_id' => $tenant->id,
                    'medical_record_id' => $mr->id,
                    'admission_id' => $admissionId,
                    'registered_by' => $admin->id,
                    'temperature' => $hasAlert ? random_int(376, 403) / 10 : random_int(362, 375) / 10,
                    'heart_rate' => random_int(60, 115),
                    'respiratory_rate' => random_int(12, 24),
                    'systolic_bp' => $sys,
                    'diastolic_bp' => (int) ($sys * 0.6),
                    'oxygen_saturation' => $o2,
                    'weight' => random_int(5000, 11000) / 100,
                    'height' => $i === 0 ? random_int(15000, 19000) / 100 : null,
                    'glucose' => random_int(7000, 18000) / 100,
                    'has_alert' => $hasAlert,
                    'alert_details' => $hasAlert ? json_encode(['oxygen_saturation' => $o2, 'systolic_bp' => $sys]) : null,
                    'measured_at' => Carbon::now()->subDays($readings - $i)->subHours(random_int(0, 10)),
                ]);
            }
        });
    }

    private function seedLabTests(Tenant $tenant): Collection
    {
        $ids = collect();

        foreach ($this->labTestsCatalog() as $t) {
            DB::table('lab_tests')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'name' => $t['name']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $t['name'],
                    'category' => $t['category'],
                    'unit' => $t['unit'],
                    'reference_min' => $t['reference_min'],
                    'reference_max' => $t['reference_max'],
                    'critical_min' => $t['critical_min'],
                    'critical_max' => $t['critical_max'],
                    'turnaround_min' => random_int(30, 240),
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $row = DB::table('lab_tests')
                ->where('tenant_id', $tenant->id)
                ->where('name', $t['name'])
                ->first();
            if ($row) {
                $ids->push((object) ['id' => $row->id, ...(array) $t]);
            }
        }

        return $ids;
    }

    private function seedLaboratory(
        Collection $patients,
        Collection $soapNotes,
        Collection $labTests,
        array $staff,
        Tenant $tenant,
        string $prefix
    ): void {
        if ($soapNotes->isEmpty() || $labTests->isEmpty()) {
            return;
        }

        $labSeq = 0;
        $barcodeSeq = 0;

        $patients->each(function (Patient $patient, int $pIdx) use ($soapNotes, $labTests, $staff, $tenant, $prefix, &$labSeq, &$barcodeSeq) {
            if (random_int(1, 10) > 7) {
                return;
            }

            $soap = $soapNotes->get($pIdx % $soapNotes->count());
            $mr = $patient->medicalRecord;
            if (! $mr) {
                return;
            }

            $labSeq++;
            $orderCode = sprintf('LAB-%s-%s%04d', $prefix, Carbon::now()->format('Y'), $labSeq);
            $orderedAt = Carbon::now()->subDays(random_int(0, 10));

            $orderId = DB::table('lab_orders')->insertGetId([
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'soap_note_id' => $soap->id,
                'medical_record_id' => $mr->id,
                'ordered_by' => $staff['doctors'][$pIdx % $staff['doctors']->count()]->user_id,
                'code' => $orderCode,
                'priority' => ['rutina', 'urgente', 'STAT'][random_int(0, 2)],
                'status' => 'completada',
                'clinical_info' => 'Control de rutina / seguimiento clínico.',
                'ordered_at' => $orderedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $selectedTests = $labTests->random(min(3, $labTests->count()));
            $barcodeSeq++;
            $sampleId = DB::table('samples')->insertGetId([
                'tenant_id' => $tenant->id,
                'lab_order_id' => $orderId,
                'received_by' => $staff['labTech']->id,
                'barcode' => sprintf('BC-%s-%06d', $prefix, $barcodeSeq),
                'sample_type' => 'sangre',
                'collected_at' => $orderedAt->copy()->addHours(1),
                'received_at' => $orderedAt->copy()->addHours(2),
                'status' => 'procesando',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $notifiedDoctorUserId = $staff['doctors'][$pIdx % $staff['doctors']->count()]->user_id;

            foreach ($selectedTests as $test) {
                $itemId = DB::table('lab_order_items')->insertGetId([
                    'lab_order_id' => $orderId,
                    'lab_test_id' => $test->id,
                    'status' => 'resultado_listo',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $forceCritical = random_int(1, 100) <= 25;
                $refMin = (float) $test->reference_min;
                $refMax = (float) $test->reference_max;
                $critMax = $test->critical_max !== null ? (float) $test->critical_max : $refMax * 1.5;

                if ($forceCritical && $critMax > 0) {
                    $numericValue = $critMax * 1.2;
                    $isCritical = true;
                    $isAbnormal = true;
                } else {
                    $numericValue = $refMin + ($refMax - $refMin) * (random_int(0, 100) / 100);
                    $isCritical = false;
                    $isAbnormal = $numericValue < $refMin || $numericValue > $refMax;
                }

                $resultId = DB::table('lab_results')->insertGetId([
                    'tenant_id' => $tenant->id,
                    'lab_order_item_id' => $itemId,
                    'sample_id' => $sampleId,
                    'entered_by' => $staff['labTech']->id,
                    'validated_by' => $staff['admin']->id,
                    'numeric_value' => round($numericValue, 4),
                    'text_value' => null,
                    'is_critical' => $isCritical,
                    'is_abnormal' => $isAbnormal,
                    'resulted_at' => $orderedAt->copy()->addHours(4),
                    'validated_at' => $orderedAt->copy()->addHours(5),
                    'sent_to_emr' => $isCritical,
                    'sent_to_emr_at' => $isCritical ? $orderedAt->copy()->addHours(5) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($isCritical) {
                    DB::table('critical_alerts')->insert([
                        'tenant_id' => $tenant->id,
                        'lab_result_id' => $resultId,
                        'patient_id' => $patient->id,
                        'notified_user_id' => $notifiedDoctorUserId,
                        'alert_type' => 'valor_critico_lab',
                        'message' => sprintf(
                            'Valor crítico en %s: %s %s (ref. %s–%s)',
                            $test->name,
                            round($numericValue, 2),
                            $test->unit,
                            $test->reference_min,
                            $test->reference_max
                        ),
                        'acknowledged' => random_int(0, 1) === 1,
                        'acknowledged_at' => random_int(0, 1) === 1 ? now() : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    private function seedAppointments(Collection $patients, array $staff, Collection $specialties, Tenant $tenant): void
    {
        $statuses = ['pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio'];

        $patients->random(min(15, $patients->count()))->each(function (Patient $patient, int $idx) use ($staff, $specialties, $tenant, $statuses) {
            $doctor = $staff['doctors'][$idx % $staff['doctors']->count()];

            DB::table('appointments')->insert([
                'tenant_id' => $tenant->id,
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'specialty_id' => $doctor->specialty_id,
                'scheduled_at' => Carbon::now()->addDays(random_int(-7, 14))->setHour(random_int(8, 16)),
                'duration_min' => [30, 45, 60][random_int(0, 2)],
                'status' => $statuses[array_rand($statuses)],
                'reason' => 'Consulta de seguimiento / control.',
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function seedBedTransfers(Collection $admissions, Collection $beds, User $admin, Tenant $tenant, string $prefix): void
    {
        $active = $admissions->where('status', 'activa');
        if ($active->count() < 2 || $beds->count() < 2) {
            return;
        }

        $admission = $active->first();
        $fromBed = $beds->firstWhere('id', $admission->bed_id);
        $toBed = $beds->first(fn (Bed $b) => $b->status === 'disponible' && $b->id !== $fromBed?->id);

        if (! $fromBed || ! $toBed) {
            return;
        }

        DB::table('bed_transfers')->insert([
            'tenant_id' => $tenant->id,
            'admission_id' => $admission->id,
            'from_bed_id' => $fromBed->id,
            'to_bed_id' => $toBed->id,
            'authorized_by' => $admin->id,
            'transferred_at' => Carbon::now()->subDays(2),
            'reason' => 'Traslado por necesidad de monitoreo en UCI.',
            'created_at' => now(),
        ]);
    }

    private function seedAuditAndNotifications(Collection $patients, array $staff, Tenant $tenant): void
    {
        $patient = $patients->first();
        if (! $patient) {
            return;
        }

        DB::table('audit_logs')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $staff['admin']->id,
            'action' => 'viewed',
            'entity' => 'MedicalRecord',
            'entity_id' => $patient->medicalRecord?->id ?? 1,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'DemoSeeder/2026',
            'created_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $tenant->id,
            'type' => 'CriticalLabResult',
            'notifiable_type' => User::class,
            'notifiable_id' => $staff['doctors']->first()->user_id,
            'data' => json_encode(['message' => 'Resultado de laboratorio crítico pendiente de revisión.']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
