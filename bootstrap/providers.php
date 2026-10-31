<?php

use App\Providers\AlergiasServiceProvider;
use App\ExpedienteMedico\ExpedienteMedicoServiceProvider;
use App\Providers\AppServiceProvider;
use App\GobernanzaAuditoria\GobernanzaAuditoriaServiceProvider;
use App\Providers\MedicationCatalogServiceProvider;
use App\Providers\NotasSoapServiceProvider;
use App\Providers\MedicosServiceProvider;
use App\Providers\OcupacionServiceProvider;
use App\Providers\PatientsServiceProvider;
use App\Providers\RbacServiceProvider;
use App\Providers\ReportesServiceProvider;
use App\Providers\SalasCamasServiceProvider;
use App\Providers\TenantsServiceProvider;
use App\Providers\TrasladosAltasServiceProvider;
use App\Providers\LabModulesServiceProvider;

return [
    \App\Providers\PrescriptionsServiceProvider::class,
    \App\Providers\ReceptionSecurityServiceProvider::class,
    App\Providers\ReceptionServiceProvider::class,
    AppServiceProvider::class,
    RbacServiceProvider::class,
    // Tenants después de RBAC: enlaza DenialRecorder a AuditDenials (persistencia de denegaciones).
    TenantsServiceProvider::class,
    GobernanzaAuditoriaServiceProvider::class,
    // Alertas críticas (ASII-21): reutiliza el ResolutorActorLocal de Traslados, que se resuelve al usarse.
    \App\Providers\AlertasCriticasServiceProvider::class,
    MedicationCatalogServiceProvider::class,
    NotasSoapServiceProvider::class,
    // Catálogo de pruebas de laboratorio (ASII-17): reutiliza el ResolutorActorLocal de Traslados, que se resuelve al usarse.
    \App\Providers\CatalogoLabServiceProvider::class,
    PatientsServiceProvider::class,
    // Admisión (ASII-07): reutiliza el ResolutorActorLocal de Traslados, que se resuelve al usarse.
    \App\Providers\AdmisionServiceProvider::class,
    SalasCamasServiceProvider::class,
    // Ocupación al final: solo cablea el adaptador PDO del Micro-HIS, no altera el binding de RBAC.
    OcupacionServiceProvider::class,
    ReportesServiceProvider::class,
    TrasladosAltasServiceProvider::class,
    LabModulesServiceProvider::class,
    // Alergias después de Traslados: reutiliza su ResolutorActorLocal (local_user_refs).
    AlergiasServiceProvider::class,
    MedicosServiceProvider::class,
    // Expediente después de Traslados: reutiliza su ResolutorActorLocal (bindIf no lo redefine).
    ExpedienteMedicoServiceProvider::class,
];
