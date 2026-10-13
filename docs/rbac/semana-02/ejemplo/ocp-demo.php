<?php

declare(strict_types=1);
// Ejemplo ilustrativo de OCP; no es el módulo RBAC. PHP 8.2 vanilla, sin dependencias.
// Authorizer permite solo si TODAS las reglas permiten; sin reglas, niega; nueva regla = nueva clase.
interface AuthorizationRule { public function decide(array $ctx): ?string; }
interface DenialRecorder { public function record(array $denial): void; }
interface PermissionProvider { public function permisosDe(string $rol): array; }
final class RoleGrantsPermission implements AuthorizationRule {
    public function decide(array $ctx): ?string {
        foreach ($ctx['roles'] as $rol) {
            if (in_array($ctx['required'], $ctx['permissions'][$rol] ?? [], true)) { return null; }
        }
        return 'sin_permiso_de_rol';
    }
}
/** Regla NUEVA: solo restringe (nunca concede): niega si no es el hospital de la asignación del rol. */
final class HospitalScopeRule implements AuthorizationRule {
    public function decide(array $ctx): ?string {
        return $ctx['hospital_id'] === $ctx['membership_hospital_id'] ? null : 'fuera_de_hospital';
    }
}
final class InMemoryPermissions implements PermissionProvider {
    public function __construct(private array $mapa) {}
    public function permisosDe(string $rol): array { return $this->mapa[$rol] ?? []; }
}
final class ConsoleDenialRecorder implements DenialRecorder {
    public function record(array $d): void { echo '    [denegación registrada] usuario=' . $d['usuario'] . ' permiso=' . $d['permiso']
        . ' operacion=' . $d['operacion'] . ' hospital=' . $d['hospital'] . ' motivo=' . $d['motivo'] . ' trace_id=' . $d['trace_id'] . PHP_EOL; }
}
function mostrar(string $s, bool $ok): void { printf('    %-44s => %s%s', $s, $ok ? 'PERMITIDO' : 'DENEGADO', PHP_EOL); }
final class Authorizer {
    private array $rules = [];
    private int $traza = 0;
    public function __construct(private PermissionProvider $permissions) {}
    public function addRule(AuthorizationRule $rule): void { $this->rules[] = $rule; }
    public function authorize(array $ctx, DenialRecorder $denials): bool {
        foreach ($ctx['roles'] as $rol) { $ctx['permissions'][$rol] = $this->permissions->permisosDe($rol); }
        if ($this->rules === []) { $this->rechazar($ctx, $denials, 'sin_reglas'); return false; }
        foreach ($this->rules as $rule) {
            $motivo = $rule->decide($ctx);
            if ($motivo !== null) { $this->rechazar($ctx, $denials, $motivo); return false; }
        }
        return true;
    }
    private function rechazar(array $ctx, DenialRecorder $denials, string $motivo): void {
        $denials->record(['usuario' => $ctx['usuario'], 'permiso' => $ctx['required'], 'operacion' => $ctx['operacion'],
            'hospital' => $ctx['hospital_id'], 'motivo' => $motivo, 'trace_id' => sprintf('trace-%04d', ++$this->traza)]);
    }
}
$permisos = ['Medico' => ['prescripciones.crear'], 'TecnicoLab' => ['muestras.recibir']];
$denials = new ConsoleDenialRecorder();
$az = new Authorizer(new InMemoryPermissions($permisos));
$ctxM = ['usuario' => 'med', 'roles' => ['Medico'], 'required' => 'prescripciones.crear', 'operacion' => 'prescribir', 'hospital_id' => 'H2', 'membership_hospital_id' => 'H2'];
$ctxB = ['usuario' => 'tec', 'roles' => ['TecnicoLab'], 'required' => 'muestras.recibir', 'operacion' => 'recibir_muestra', 'hospital_id' => 'H2', 'membership_hospital_id' => 'H2'];
$ctxX = $ctxB; $ctxX['hospital_id'] = 'H1';
echo "1) Sin reglas -> denegación por defecto:\n";
mostrar('Medico -> prescripciones.crear', $az->authorize($ctxM, $denials));
mostrar('TecnicoLab H2 -> muestras.recibir', $az->authorize($ctxB, $denials));
mostrar('TecnicoLab H1 -> muestras.recibir', $az->authorize($ctxX, $denials));
echo "2) Regla RoleGrantsPermission (permite si algún rol del usuario otorga el permiso):\n";
$az->addRule(new RoleGrantsPermission());
mostrar('Medico -> prescripciones.crear', $az->authorize($ctxM, $denials));
mostrar('TecnicoLab H2 -> muestras.recibir', $az->authorize($ctxB, $denials));
mostrar('TecnicoLab H1 -> muestras.recibir [hueco: falta restringir]', $az->authorize($ctxX, $denials));
echo "3) Regla NUEVA HospitalScopeRule (solo restringe; Authorizer sin cambios):\n";
$az->addRule(new HospitalScopeRule());
mostrar('TecnicoLab H1 -> muestras.recibir', $az->authorize($ctxX, $denials));
mostrar('TecnicoLab H2 -> muestras.recibir', $az->authorize($ctxB, $denials));
mostrar('Medico -> prescripciones.crear (sin regresión)', $az->authorize($ctxM, $denials));