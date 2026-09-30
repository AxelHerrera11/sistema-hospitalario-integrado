<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// auditoria y notificaciones del sistema
// la auditoria guarda quien accedio a que expediente (requerimiento RNF-009)
// joshua garcia - jgarciar73-svg

return new class extends Migration
{
    public function up(): void
    {
        // log de auditoria, registra todo lo que hacen los usuarios
        // nunca se borra ni se edita, solo se insertan registros
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('action', 100); // viewed, created, updated, deleted
            $table->string('entity', 100); // MedicalRecord, Prescription, LabResult...
            $table->unsignedBigInteger('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            // buscar quien vio el expediente X
            $table->index(['tenant_id', 'entity', 'entity_id', 'created_at'], 'idx_audit_entity');
            // ver actividad de un usuario
            $table->index(['tenant_id', 'user_id', 'created_at'], 'idx_audit_user_date');
            $table->index(['tenant_id', 'action', 'created_at'], 'idx_audit_action_date');
        });

        // notificaciones in-app para el sistema de websockets
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // nullable: el canal 'database' de Laravel no llena tenant_id por sí solo
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
            $table->string('type'); // CriticalLabResult, AllergyAlert, BedStatusChanged
            $table->morphs('notifiable');
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // notificaciones no leidas del usuario
            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'idx_notif_notifiable_unread');
            $table->index(['tenant_id', 'type', 'created_at'], 'idx_notif_tenant_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
    }
};
