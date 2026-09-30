<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla automáticamente los datos por hospital (tenant).
 *
 * - Toda consulta del modelo se filtra por el tenant de la petición actual
 *   (el que resolvió TenantMiddleware a partir de X-Tenant-ID).
 * - Al crear un registro sin tenant_id, se le asigna el tenant actual.
 * - Fuera de una petición HTTP (seeders, comandos) no hay tenant actual y el
 *   filtro no se aplica, por eso los seeders deben indicar tenant_id explícito.
 *
 * Para saltarse el filtro a propósito (p. ej. un reporte de super admin):
 *   Patient::withoutGlobalScope('tenant')->get();
 */
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
