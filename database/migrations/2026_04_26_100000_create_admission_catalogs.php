<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// migracion para las tablas base de admision
// salas, camas, especialidades, pacientes y medicos
// joshua garcia - jgarciar73-svg

return new class extends Migration
{
    public function up(): void
    {
        // tabla de salas o unidades del hospital (UCI, pediatria, etc)
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('floor', 10)->nullable();
            $table->string('building', 10)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'name'], 'idx_wards_tenant_name');
        });

        // camas del hospital, cada una pertenece a una sala
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('ward_id')->constrained('wards')->cascadeOnDelete();
            $table->string('code', 20); // ej: CAM-A101 (único por hospital)
            $table->enum('status', ['disponible', 'ocupada', 'limpieza', 'mantenimiento'])
                  ->default('disponible');
            $table->text('notes')->nullable();
            $table->timestamps();

            // esto lo uso para el dashboard de camas en tiempo real
            $table->unique(['tenant_id', 'code'], 'uq_beds_tenant_code');
            $table->index(['tenant_id', 'status'], 'idx_beds_tenant_status');
            $table->index(['tenant_id', 'ward_id', 'status'], 'idx_beds_ward_status');
        });

        // especialidades medicas disponibles
        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'name'], 'idx_specialties_tenant_name');
        });

        // tabla principal de pacientes
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('code', 20); // PAC-0001 (único por hospital)
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('birth_date');
            $table->enum('gender', ['M', 'F', 'otro']);
            $table->string('dpi', 20)->nullable();
            $table->string('nit', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('address', 200)->nullable();
            $table->string('insurance_company', 100)->nullable();
            $table->string('insurance_policy', 50)->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->enum('blood_type', ['A+','A-','B+','B-','O+','O-','AB+','AB-'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // busqueda por nombre o dpi en admision
            $table->index(['tenant_id', 'last_name', 'first_name'], 'idx_patients_tenant_name');
            $table->index(['tenant_id', 'dpi'], 'idx_patients_tenant_dpi');
            $table->unique(['tenant_id', 'code'], 'uq_patients_tenant_code');
        });

        // medicos, son usuarios del sistema con datos extra
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained('specialties')->restrictOnDelete();
            $table->string('license_number', 50); // numero de colegiado (único por hospital)
            $table->string('phone', 20)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'license_number'], 'uq_doctors_tenant_license');
            $table->index(['tenant_id', 'specialty_id'], 'idx_doctors_tenant_specialty');
            $table->index(['tenant_id', 'user_id'], 'idx_doctors_tenant_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('specialties');
        Schema::dropIfExists('beds');
        Schema::dropIfExists('wards');
    }
};
