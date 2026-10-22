<?php

declare(strict_types=1);
/*
 * Evidencia Semana 2 (SRP) — ASII-26
 * Ejecutar: php docs/asii-26-dashboard-base/semana-02/evidencia/demo-srp.php (solo PHP 8.2+)
 */

// ---- ANTES: una clase con cuatro motivos de cambio (viola SRP) ----
final class DashboardPageAntes
{
    public function render(string $rol, bool $conSesion, array $datos): string
    {
        if (!$conSesion) { return 'login'; }                          // 1) acceso
        $menu = $rol === 'admin' ? ['panel', 'usuarios'] : ['panel']; // 2) menu por rol
        $estado = $datos === [] ? 'vacio' : 'datos';                  // 3) estado UI
        return 'acceso=ok menu=' . implode('|', $menu) . " estado={$estado}";
    }
}
// ---- DESPUÉS: una responsabilidad por clase (aplica SRP) ----
interface ConstructorMenu
{
    public function menuPara(string $rol, array $permisos): array;
}
interface EvaluadorAcceso
{
    public function evaluar(bool $conSesion, string $vista): string;
}
interface CargadorResumen
{
    public function cargar(): array;
}
interface PresentadorEstado
{
    public function presentar(array $datos): string;
}
final class MenuPorRol implements ConstructorMenu
{
    public function menuPara(string $rol, array $permisos): array
    {
        $base = ['panel'];
        return $rol === 'admin' ? [...$base, 'usuarios'] : $base;
    }
}
final class MenuConReportes implements ConstructorMenu // extensión: solo cambia el menú
{
    public function menuPara(string $rol, array $permisos): array
    {
        return [...(new MenuPorRol())->menuPara($rol, $permisos), 'reportes'];
    }
}
final class GuardSesion implements EvaluadorAcceso
{
    public function evaluar(bool $conSesion, string $vista): string
    {
        return $conSesion ? 'ok' : 'login';
    }
}
final class ResumenDesdeApi implements CargadorResumen
{
    public function cargar(): array { return ['citas_hoy' => 3]; }
}
final class ResumenVacio implements CargadorResumen // extensión: solo cambia la carga
{
    public function cargar(): array { return []; }
}
final class EstadoDeResumen implements PresentadorEstado
{
    public function presentar(array $datos): string { return $datos === [] ? 'vacio' : 'datos'; }
}
final class DashboardController
{
    public function __construct(
        private ConstructorMenu $menu,
        private EvaluadorAcceso $acceso,
        private CargadorResumen $loader,
        private PresentadorEstado $presenter
    ) {}
    public function mostrarVista(string $rol, bool $conSesion, array $permisos): string
    {
        $acceso = $this->acceso->evaluar($conSesion, 'dashboard');
        if ($acceso !== 'ok') { return $acceso; }
        $menu = $this->menu->menuPara($rol, $permisos);
        $estado = $this->presenter->presentar($this->loader->cargar());
        return 'acceso=ok menu=' . implode('|', $menu) . " estado={$estado}";
    }
}
// ---- Datos ficticios y ejecución ----
$antes = new DashboardPageAntes();
echo 'ANTES  admin: ', $antes->render('admin', true, ['x' => 1]), "\n";
echo 'ANTES  medico: ', $antes->render('medico', true, ['x' => 1]), "\n";
$factor = static fn (CargadorResumen $l): DashboardController => new DashboardController(
    new MenuPorRol(), new GuardSesion(), $l, new EstadoDeResumen()
);
echo 'DESPUÉS admin: ', $factor(new ResumenDesdeApi())->mostrarVista('admin', true, []), "\n";
$conReportes = new DashboardController(new MenuConReportes(), new GuardSesion(), new ResumenDesdeApi(), new EstadoDeResumen());
echo 'DESPUÉS menu nuevo (controlador intacto): ', $conReportes->mostrarVista('medico', true, []), "\n";
echo 'DESPUÉS resumen vacio (loader nuevo): ', $factor(new ResumenVacio())->mostrarVista('medico', true, []), "\n";
echo 'DESPUÉS sin sesion: ', $factor(new ResumenDesdeApi())->mostrarVista('admin', false, []), "\n";
echo 'DESPUÉS cambiar menu no altera la carga: ', ((new ResumenDesdeApi())->cargar() !== [] ? 'OK' : 'FALLA'), "\n";
