<?php

namespace Database\Seeders\Concerns;

use App\Models\Tenant;

trait GeneratesTenantCodes
{
    protected function tenantPrefix(Tenant $tenant): string
    {
        return strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $tenant->slug), 0, 6));
    }

    protected function uniqueCode(string $prefix, string $template, int $sequence): string
    {
        return sprintf($template, $prefix, $sequence);
    }
}
