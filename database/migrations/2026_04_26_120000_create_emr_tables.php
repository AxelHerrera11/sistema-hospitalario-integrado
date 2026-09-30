<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// tablas del expediente medico electronico (EMR)
// expediente, notas soap, alergias, signos vitales, medicamentos y prescripciones
// joshua garcia - jgarciar73-svg

return new class extends Migration
{
    public function up(): void
    {
        // expediente medico, cada paciente tiene uno solo
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')
                  ->unique()
                  ->constrained('patients')
                  ->cascadeOnDelete();
            $table->string('record_number', 20); // EXP-00001 (único por hospital)
            $table->date('opened_at');
            $table->text('background')->nullable();         // antecedentes personales
            $table->text('family_background')->nullable();  // antecedentes familiares
            $table->text('surgical_history')->nullable();
            $table->text('obstetric_history')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'patient_id'], 'idx_mr_tenant_patient');
            $table->unique(['tenant_id', 'record_number'], 'uq_mr_tenant_number');
        });

        // notas medicas en formato SOAP que escribe el doctor
        Schema::create('soap_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('medical_record_id')
                  ->constrained('medical_records')
                  ->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
            // puede ser null si es consulta externa sin admision
            $table->foreignId('admission_id')
                  ->nullable()
                  ->constrained('admissions')
                  ->nullOnDelete();
            $table->text('subjective');  // S: que dice el paciente
            $table->text('objective');   // O: lo que encuentra el doctor
            $table->text('assessment');  // A: el diagnostico
            $table->text('plan');        // P: el tratamiento
            $table->string('electronic_sign', 255)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();

            // timeline del expediente ordenado por fecha
            $table->index(['tenant_id', 'medical_record_id', 'created_at'], 'idx_soap_record_date');
            $table->index(['tenant_id', 'doctor_id', 'created_at'], 'idx_soap_doctor_date');
        });

        // diagnosticos CIE-10 que van dentro de cada nota SOAP
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('soap_note_id')
                  ->constrained('soap_notes')
                  ->cascadeOnDelete();
            $table->string('cie10_code', 10);    // ej: J18.9
            $table->string('description', 200);
            $table->enum('type', ['principal', 'secundario', 'presuntivo', 'definitivo'])
                  ->default('presuntivo');
            $table->timestamps();

            $table->index(['tenant_id', 'soap_note_id'], 'idx_diag_soap');
            $table->index(['tenant_id', 'cie10_code'], 'idx_diag_cie10');
        });

        // alergias del paciente, muy importante para bloquear prescripciones
        Schema::create('allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->cascadeOnDelete();
            $table->foreignId('registered_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->string('allergen', 150); // ej: Aspirina, Penicilina
            $table->enum('allergen_type', ['medicamento', 'alimento', 'ambiental', 'otro'])
                  ->default('medicamento');
            $table->enum('severity', ['leve', 'moderada', 'grave', 'anafilaxis'])
                  ->default('moderada');
            $table->text('reaction')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            // se consulta cada vez que el medico va a prescribir algo
            $table->index(['tenant_id', 'patient_id', 'active'], 'idx_allergy_patient_active');
            $table->index(['tenant_id', 'allergen', 'active'], 'idx_allergy_allergen');
        });

        // signos vitales que registra la enfermera
        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('medical_record_id')
                  ->constrained('medical_records')
                  ->cascadeOnDelete();
            $table->foreignId('admission_id')
                  ->nullable()
                  ->constrained('admissions')
                  ->nullOnDelete();
            $table->foreignId('registered_by')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->decimal('temperature', 4, 1)->nullable();        // grados C
            $table->unsignedSmallInteger('heart_rate')->nullable();   // lpm
            $table->unsignedSmallInteger('respiratory_rate')->nullable(); // rpm
            $table->unsignedSmallInteger('systolic_bp')->nullable();  // mmHg
            $table->unsignedSmallInteger('diastolic_bp')->nullable(); // mmHg
            $table->decimal('oxygen_saturation', 5, 2)->nullable();   // porcentaje
            $table->decimal('weight', 5, 2)->nullable();              // kg
            $table->decimal('height', 5, 2)->nullable();              // cm
            $table->decimal('glucose', 6, 2)->nullable();             // mg/dL
            $table->boolean('has_alert')->default(false);
            $table->json('alert_details')->nullable(); // que valores dispararon la alerta
            $table->dateTime('measured_at');
            $table->timestamps();

            // para graficar la evolucion del paciente
            $table->index(['tenant_id', 'medical_record_id', 'measured_at'], 'idx_vs_record_time');
            $table->index(['tenant_id', 'has_alert', 'measured_at'], 'idx_vs_alerts');
        });

        // catalogo de medicamentos disponibles
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('generic_name', 100)->nullable();
            $table->string('category', 100)->nullable(); // AINE, Antibiotico...
            $table->string('presentation', 50)->nullable(); // tableta, ampolla...
            $table->string('concentration', 50)->nullable(); // 500mg, 1g/5ml...
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'uq_medications_tenant_name');
            $table->index(['tenant_id', 'name', 'active'], 'idx_med_tenant_name_active');
            $table->index(['tenant_id', 'generic_name'], 'idx_med_generic');
        });

        // prescripciones electronicas del medico
        // si el paciente es alergico al medicamento, blocked = true
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('soap_note_id')
                  ->constrained('soap_notes')
                  ->cascadeOnDelete();
            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->restrictOnDelete();
            $table->foreignId('doctor_id')
                  ->constrained('doctors')
                  ->restrictOnDelete();
            $table->foreignId('medication_id')
                  ->constrained('medications')
                  ->restrictOnDelete();
            $table->string('dose', 100);
            $table->string('frequency', 100); // ej: cada 8h
            $table->unsignedSmallInteger('duration_days');
            $table->string('route', 200)->nullable(); // oral, IV, IM...
            $table->text('indications')->nullable();
            $table->boolean('blocked')->default(false);
            $table->string('blocked_reason', 255)->nullable();
            $table->string('electronic_sign', 255)->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();

            // medicamentos activos del paciente
            $table->index(['tenant_id', 'patient_id', 'blocked', 'created_at'], 'idx_rx_patient_active');
            $table->index(['tenant_id', 'soap_note_id'], 'idx_rx_soap');
            $table->index(['tenant_id', 'patient_id', 'medication_id', 'created_at'], 'idx_rx_patient_med');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medications');
        Schema::dropIfExists('vital_signs');
        Schema::dropIfExists('allergies');
        Schema::dropIfExists('diagnoses');
        Schema::dropIfExists('soap_notes');
        Schema::dropIfExists('medical_records');
    }
};
