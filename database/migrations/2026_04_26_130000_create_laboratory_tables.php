<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// tablas del modulo de laboratorio clinico
// pruebas, ordenes, muestras, resultados y alertas criticas
// joshua garcia - jgarciar73-svg

return new class extends Migration
{
    public function up(): void
    {
        // catalogo de pruebas de laboratorio con sus rangos normales
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name', 100); // Hemograma, Troponina, Glucosa...
            $table->string('category', 100)->nullable(); // Hematologia, Quimica...
            $table->string('unit', 50)->nullable(); // mg/dL, U/L, ng/mL
            $table->decimal('reference_min', 10, 4)->nullable(); // rango normal minimo
            $table->decimal('reference_max', 10, 4)->nullable(); // rango normal maximo
            $table->decimal('critical_min', 10, 4)->nullable();  // valor panico minimo
            $table->decimal('critical_max', 10, 4)->nullable();  // valor panico maximo
            $table->unsignedSmallInteger('turnaround_min')->nullable(); // tiempo estimado
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'name'], 'uq_lab_tests_tenant_name');
            $table->index(['tenant_id', 'category', 'active'], 'idx_lab_tests_category');
        });

        // ordenes de laboratorio que crea el medico desde el EMR
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('soap_note_id')->constrained('soap_notes')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->constrained('medical_records')->restrictOnDelete();
            $table->foreignId('ordered_by')->constrained('users')->restrictOnDelete();
            $table->string('code', 20); // LAB-20260001 (único por hospital)
            $table->enum('priority', ['rutina', 'urgente', 'STAT'])->default('rutina');
            $table->enum('status', [
                'pendiente', 'en_proceso', 'completada', 'cancelada'
            ])->default('pendiente');
            $table->text('clinical_info')->nullable();
            $table->dateTime('ordered_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'uq_lab_orders_tenant_code');
            // lista de trabajo del tecnico de lab
            $table->index(['tenant_id', 'status', 'priority', 'ordered_at'], 'idx_lab_orders_worklist');
            $table->index(['tenant_id', 'patient_id', 'ordered_at'], 'idx_lab_orders_patient');
        });

        // cada orden puede tener varias pruebas
        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained('lab_tests')->restrictOnDelete();
            $table->enum('status', [
                'pendiente', 'muestra_recibida', 'en_proceso', 'resultado_listo'
            ])->default('pendiente');
            $table->timestamps();

            $table->index(['lab_order_id', 'status'], 'idx_order_items_status');
            // no se puede repetir la misma prueba en la misma orden
            $table->unique(['lab_order_id', 'lab_test_id'], 'uq_order_test');
        });

        // muestras que toma el tecnico, cada una con su codigo de barras
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lab_order_id')->constrained('lab_orders')->cascadeOnDelete();
            $table->foreignId('received_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('barcode', 50); // único por hospital
            $table->enum('sample_type', [
                'sangre', 'orina', 'heces', 'LCR', 'esputo', 'cultivo', 'otro'
            ])->default('sangre');
            $table->dateTime('collected_at')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->enum('status', ['pendiente', 'recibida', 'procesando', 'descartada'])
                  ->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'barcode'], 'uq_samples_tenant_barcode');
            $table->index(['tenant_id', 'lab_order_id'], 'idx_samples_order');
            $table->index(['tenant_id', 'status'], 'idx_samples_status');
        });

        // resultados ingresados por el tecnico y validados por el bioquimico
        // si is_critical = true se manda alerta al medico por websocket
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lab_order_item_id')
                  ->constrained('lab_order_items')
                  ->cascadeOnDelete();
            $table->foreignId('sample_id')->constrained('samples')->restrictOnDelete();
            $table->foreignId('entered_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('validated_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->decimal('numeric_value', 12, 4)->nullable(); // si el resultado es numero
            $table->string('text_value', 500)->nullable();       // si el resultado es texto
            $table->boolean('is_critical')->default(false); // fuera del rango panico
            $table->boolean('is_abnormal')->default(false); // fuera del rango normal
            $table->dateTime('resulted_at');
            $table->dateTime('validated_at')->nullable();
            $table->boolean('sent_to_emr')->default(false);
            $table->dateTime('sent_to_emr_at')->nullable();
            $table->timestamps();

            // para el job que envia alertas criticas
            $table->index(['tenant_id', 'is_critical', 'sent_to_emr'], 'idx_results_critical_unsent');
            $table->index(['tenant_id', 'lab_order_item_id', 'resulted_at'], 'idx_results_item_date');
        });

        // alertas criticas que se notifican al medico
        // puede ser por lab, alergia al prescribir o signo vital anormal
        Schema::create('critical_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('lab_result_id')
                  ->nullable()
                  ->constrained('lab_results')
                  ->nullOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('notified_user_id')->constrained('users')->restrictOnDelete();
            $table->enum('alert_type', [
                'valor_critico_lab',
                'alergia_prescripcion',
                'signo_vital_anormal'
            ]);
            $table->text('message');
            $table->boolean('acknowledged')->default(false); // si el medico ya vio la alerta
            $table->dateTime('acknowledged_at')->nullable();
            $table->timestamps();

            // alertas pendientes por medico
            $table->index(['tenant_id', 'notified_user_id', 'acknowledged'], 'idx_alerts_user_pending');
            $table->index(['tenant_id', 'patient_id', 'created_at'], 'idx_alerts_patient_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('critical_alerts');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('samples');
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_tests');
    }
};
