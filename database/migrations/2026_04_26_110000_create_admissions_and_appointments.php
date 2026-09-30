<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// citas medicas, admisiones hospitalarias y traslados de cama
// joshua garcia - jgarciar73-svg

return new class extends Migration
{
    public function up(): void
    {
        // citas que agenda el paciente con un medico
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('specialty_id')->constrained('specialties')->restrictOnDelete();
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration_min')->default(30);
            $table->enum('status', [
                'pendiente', 'confirmada', 'completada', 'cancelada', 'no_asistio'
            ])->default('pendiente');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // para ver la agenda del medico por dia
            $table->index(['tenant_id', 'doctor_id', 'scheduled_at'], 'idx_appt_doctor_date');
            // lista de citas del dia en recepcion
            $table->index(['tenant_id', 'scheduled_at', 'status'], 'idx_appt_date_status');
            // historial del paciente
            $table->index(['tenant_id', 'patient_id', 'scheduled_at'], 'idx_appt_patient_date');
        });

        // cuando un paciente es admitido y se le asigna una cama
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('admitted_by')->constrained('users')->restrictOnDelete();
            $table->string('code', 20); // ADM-20260001 (único por hospital)
            $table->dateTime('admitted_at');
            $table->dateTime('discharged_at')->nullable();
            $table->enum('status', ['activa', 'alta', 'traslado'])->default('activa');
            $table->enum('discharge_type', [
                'voluntaria', 'medica', 'fallecimiento', 'traslado'
            ])->nullable();
            $table->text('discharge_notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'uq_adm_tenant_code');
            // admisiones activas para el dashboard
            $table->index(['tenant_id', 'status', 'admitted_at'], 'idx_adm_tenant_status');
            // verificar si el paciente ya esta admitido
            $table->index(['tenant_id', 'patient_id', 'status'], 'idx_adm_patient_status');
            // saber en que estado esta la cama
            $table->index(['tenant_id', 'bed_id', 'status'], 'idx_adm_bed_status');
        });

        // cuando trasladan a un paciente de una cama a otra
        Schema::create('bed_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->foreignId('from_bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignId('to_bed_id')->constrained('beds')->restrictOnDelete();
            $table->foreignId('authorized_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('transferred_at');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'admission_id'], 'idx_transfers_admission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_transfers');
        Schema::dropIfExists('admissions');
        Schema::dropIfExists('appointments');
    }
};
