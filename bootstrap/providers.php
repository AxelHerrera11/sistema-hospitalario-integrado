<?php

use App\Providers\AppServiceProvider;
use App\GobernanzaAuditoria\GobernanzaAuditoriaServiceProvider;
use App\Providers\MedicationCatalogServiceProvider;
use App\Providers\OcupacionServiceProvider;
use App\Providers\PatientsServiceProvider;
use App\Providers\RbacServiceProvider;
use App\Providers\SalasCamasServiceProvider;
use App\Providers\TenantsServiceProvider;
use App\Providers\TrasladosAltasServiceProvider;

return [
    AppServiceProvider::class,
    RbacServiceProvider::class,
    // Tenants después de RBAC: enlaza DenialRecorder a AuditDenials (persistencia de denegaciones).
    TenantsServiceProvider::class,
    GobernanzaAuditoriaServiceProvider::class,
    MedicationCatalogServiceProvider::class,
    PatientsServiceProvider::class,
    // Admisión (ASII-07): reutiliza el ResolutorActorLocal de Traslados, que se resuelve al usarse.
    \App\Providers\AdmisionServiceProvider::class,
    SalasCamasServiceProvider::class,
    // Ocupación al final: solo cablea el adaptador PDO del Micro-HIS, no altera el binding de RBAC.
    OcupacionServiceProvider::class,
    TrasladosAltasServiceProvider::class,
];
