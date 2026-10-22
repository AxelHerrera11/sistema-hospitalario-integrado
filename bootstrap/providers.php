<?php

use App\Providers\AppServiceProvider;
use App\Providers\MedicationCatalogServiceProvider;
use App\Providers\OcupacionServiceProvider;
use App\Providers\PatientsServiceProvider;
use App\Providers\RbacServiceProvider;
use App\Providers\TenantsServiceProvider;
use App\Providers\TrasladosAltasServiceProvider;

return [
    AppServiceProvider::class,
    RbacServiceProvider::class,
    // Tenants después de RBAC: enlaza DenialRecorder a AuditDenials (persistencia de denegaciones).
    TenantsServiceProvider::class,
    MedicationCatalogServiceProvider::class,
    PatientsServiceProvider::class,
    // Ocupación al final: solo cablea el adaptador PDO del Micro-HIS, no altera el binding de RBAC.
    OcupacionServiceProvider::class,
    TrasladosAltasServiceProvider::class,
];
