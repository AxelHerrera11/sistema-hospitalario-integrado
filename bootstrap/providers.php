<?php

use App\Providers\AppServiceProvider;
use App\Providers\RbacServiceProvider;
use App\Providers\TenantsServiceProvider;

return [
    AppServiceProvider::class,
    RbacServiceProvider::class,
    // Tenants después de RBAC: enlaza DenialRecorder a AuditDenials (persistencia de denegaciones).
    TenantsServiceProvider::class,
];
