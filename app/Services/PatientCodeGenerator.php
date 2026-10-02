<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Tenant;

/**
 * Genera el código correlativo de un paciente dentro de su hospital.
 *
 * Formato: PAC-{PREFIJO}-{0001}   p. ej. PAC-SANMAR-0001
 *
 * El prefijo sale del slug del hospital (mismo criterio que el seeder demo
 * DemoDataSeeder2026), así los códigos de dos hospitales nunca se parecen.
 * El largo máximo es 15 caracteres, dentro del string(20) de la columna.
 *
 * Solo se usa cuando el cliente NO envía 'code'. Si lo envía, manda el suyo
 * (validado por la regla unique con el tenant de la petición).
 */
class PatientCodeGenerator
{
    private const TEMPLATE = 'PAC-%s-%04d';

    public function generate(Tenant $tenant): string
    {
        $prefix = $this->prefixFor($tenant);
        $sequence = $this->lastSequence($prefix);

        do {
            $sequence++;
            $code = sprintf(self::TEMPLATE, $prefix, $sequence);
        } while ($this->alreadyTaken($code));

        return $code;
    }

    /**
     * Altas 6 del slug sin guiones ni acentos, en mayúsculas.
     */
    private function prefixFor(Tenant $tenant): string
    {
        return strtoupper(substr((string) preg_replace('/[^a-z0-9]/i', '', $tenant->slug), 0, 6));
    }

    /**
     * Último correlativo ya usado con este prefijo.
     *
     * withTrashed() es obligatorio: patients.code tiene la restricción dura
     * uq_patients_tenant_code y un paciente borrado lógicamente sigue
     * ocupando su código. Sin esto, el siguiente correlativo podría repetirse
     * y la inserción fallaría por violación de unicidad.
     *
     * No se filtra tenant_id a mano: Patient usa el trait BelongsToTenant, que
     * ya aplica el global scope con el hospital de la petición.
     */
    private function lastSequence(string $prefix): int
    {
        return Patient::withTrashed()
            ->where('code', 'like', sprintf('PAC-%s-%%', $prefix))
            ->pluck('code')
            ->map(fn (string $code) => $this->trailingNumber($code))
            ->max() ?? 0;
    }

    private function alreadyTaken(string $code): bool
    {
        return Patient::withTrashed()->where('code', $code)->exists();
    }

    /**
     * Último grupo de dígitos del código. Con preg_match y no substr porque al
     * pasar de 9999 pacientes el correlativo crece a 5 dígitos.
     */
    private function trailingNumber(string $code): int
    {
        return preg_match('/(\d+)$/', $code, $matches) === 1 ? (int) $matches[1] : 0;
    }
}
