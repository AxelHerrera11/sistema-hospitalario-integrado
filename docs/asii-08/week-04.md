# Documento del Área 8: Alertas Críticas, Notificaciones, Dashboard y Reportes

*Responsable:* Cindy Ruano
*Módulos originales:* 9, 21, 23 y 27
*Entrega:* Semana 4 — Diseño por Capas, Persistencia y Responsabilidades
*Sistema:* Sistema Hospitalario Integrado (HIS)

## 1. Diseño de Capas

| Capa             | Responsabilidad en Área 8                                                                                                                                                                |
|------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| UI Vue 3         | Mostrar dashboard, lista paginada, filtros, detalle de contexto autorizado, estados pendiente/acuse y reportes. Envía JWT y X-Tenant-ID; no decide severidad, tenant ni actor del acuse. |
| API / Routing    | Exponer rutas bajo /api/v1; aplicar tenant, auth.jwt y permission:<modulo>.<accion>; validar JSON y delegar reglas a servicios.                                                          |
| Domain / Service | Ejecutar idempotencia, validar referencias/tenant, registrar acuse, agregar KPIs y coordinar auditoría/publicación post-commit. El origen clínico sigue validado por Áreas 5, 6 y 7.     |
| Persistence      | Eloquent y PostgreSQL persisten alertas, notificaciones y auditoría. CriticalAlert usa BelongsToTenant; índices respaldan filtros y agregaciones.                                        |

*Flujo de lectura o acuse:* Vue adjunta token y X-Tenant-ID; middleware resuelve el tenant y valida su coincidencia con JWT; Spatie verifica permiso; Form Request valida parámetros; el controller delega al servicio; Eloquent consulta bajo el scope de tenant; Laravel responde según el [contrato API común](../contrato-api.md).

*Flujo de evento:* El módulo de origen valida el evento; AlertIngestionService verifica referencias en el tenant activo y aplica idempotencia; alerta y auditoría se guardan en el límite transaccional definido; la notificación se publica después del commit. Para sistemas externos se utiliza outbox y reintentos, no una llamada de red dentro de la transacción.

BelongsToTenant no sustituye la autenticación. No se confía en tenant_id enviado por el cliente ni se omite el scope para resolver IDs externos.

## 2. Modelo de Datos y Objeto de Persistencia

### Esquema objetivo propuesto: critical_alerts

| Campo                    | Tipo objetivo           | Regla                                                                                                                         |
|--------------------------|-------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| id                       | UUID, PK                | Identificador externo. La tabla actual usa bigint; la migración a UUID requiere transición compatible con FKs y consumidores. |
| tenant_id                | UUID, FK tenants        | Obligatorio; asignado desde currentTenant, no desde el payload.                                                               |
| patient_id               | FK patients             | Obligatorio; paciente del mismo tenant.                                                                                       |
| lab_result_id            | FK nullable lab_results | Nullable para alertas no originadas en laboratorio; validar tenant si está informado.                                         |
| notified_user_id         | FK users                | Destinatario autorizado perteneciente al tenant.                                                                              |
| alert_type               | Enum/string validado    | valor_critico_lab, alergia_prescripcion, signo_vital_anormal.                                                                 |
| severity                 | Enum/string validado    | critical, warning, info; mapeo clínico pendiente de acuerdo.                                                                  |
| message                  | Text                    | Resumen clínico mínimo, sin duplicar datos sensibles.                                                                         |
| payload                  | JSON/JSONB nullable     | Metadatos y referencias mínimas de origen; no copiar expediente ni secretos.                                                  |
| source_event_id          | UUID/string             | Clave estable; restricción única recomendada (tenant_id, source_event_id) para idempotencia.                                  |
| acknowledged             | Boolean, default false  | Estado de recepción; solo lo cambia el servicio de acuse.                                                                     |
| acknowledged_at          | Timestamp nullable      | Fecha/hora del servidor al primer acuse.                                                                                      |
| acknowledged_by          | FK nullable users       | Actor autenticado; no lo envía el cliente y debe ser del mismo tenant.                                                        |
| created_at, updated_at   | Timestamps              | Tiempos técnicos; auditoría de negocio permanece en audit_logs.                                                               |

*Índices propuestos:* UNIQUE (tenant_id, source_event_id); (tenant_id, notified_user_id, acknowledged, created_at) para bandeja; (tenant_id, severity, created_at) para KPI; (tenant_id, patient_id, created_at) para historial. Agregaciones KPI deben usar consultas agregadas y ventanas temporales definidas.

El modelo CriticalAlert debe usar el trait BelongsToTenant. Las relaciones tampoco deben exponer referencias cruzadas entre tenants. Campos de tenant y acuse son asignados por servidor, no por mass assignment desde el cliente.

### Trait BelongsToTenant

Implementación vigente en app/Models/Concerns/BelongsToTenant.php. El middleware tenant debe resolver currentTenant antes de las consultas. Fuera de una petición HTTP, seeders y comandos deben indicar tenant_id explícitamente.

```php
<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenant = static::currentTenant();

            if ($tenant !== null) {
                $builder->where($builder->getModel()->qualifyColumn('tenant_id'), $tenant->getKey());
            }
        });

        static::creating(function ($model): void {
            $tenant = static::currentTenant();

            if ($tenant !== null && empty($model->tenant_id)) {
                $model->tenant_id = $tenant->getKey();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    protected static function currentTenant(): ?Tenant
    {
        return app()->bound('currentTenant') ? app('currentTenant') : null;
    }
}
```

Uso en el modelo:

```php
use App\Models\Concerns\BelongsToTenant;

class CriticalAlert extends Model
{
    use BelongsToTenant;
}
```

El global scope no reemplaza el control JWT↔X-Tenant-ID. No debe quitarse para resolver IDs del cliente.

### Compatibilidad de migración

La migración vigente usa PK bigint y no tiene severity, payload, acknowledged_by ni clave idempotente. Añadir campos en una migración aditiva/reversible, con backfill y limpieza de duplicados antes de crear la restricción única. La decisión de migrar PK a UUID debe incluir FKs, seeders, rutas y consumidores. Además, audit_logs.entity_id es actualmente unsignedBigInteger; si la alerta pasa a UUID, coordinar con Área 1 una referencia de auditoría compatible en vez de truncar o ignorar el ID.
