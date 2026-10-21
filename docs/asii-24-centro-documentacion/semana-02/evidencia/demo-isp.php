<?php

declare(strict_types=1);
/*
 * Evidencia Semana 2 (ISP) — ASII-24
 * Ejecutar: php docs/asii-24-centro-documentacion/semana-02/evidencia/demo-isp.php (solo PHP 8.2+)
 */

// ---- ANTES: interfaz "gorda" que obliga a implementar de más (viola ISP) ----
interface RepositorioDocumentosGordo
{
    public function publicar(array $documento): void;
    public function clasificar(string $id, array $roles): void;
    public function listar(string $rol): array;
    public function buscar(string $texto, string $rol): array;
    public function descargar(string $id): string;
    public function versionar(string $id): void;
    public function vigencia(string $id): string;
}
final class CatalogoControllerAntes implements RepositorioDocumentosGordo
{
    public function __construct(private array $docs) {}
    public function listar(string $rol): array
    {
        return array_values(array_filter($this->docs, static fn (array $d): bool => in_array($rol, $d['roles'], true)));
    }
    public function buscar(string $texto, string $rol): array
    {
        return array_values(array_filter($this->listar($rol), static fn (array $d): bool => stripos($d['titulo'], $texto) !== false));
    }
    public function publicar(array $documento): void { throw new LogicException('no soportado'); }
    public function clasificar(string $id, array $roles): void { throw new LogicException('no soportado'); }
    public function descargar(string $id): string { throw new LogicException('no soportado'); }
    public function versionar(string $id): void { throw new LogicException('no soportado'); }
    public function vigencia(string $id): string { throw new LogicException('no soportado'); }
}
// ---- DESPUÉS: interfaces segregadas por consumidor (aplica ISP) ----
interface ConsultaDocumentos
{
    public function listar(string $rol): array;
    public function buscar(string $texto, string $rol): array;
}
interface RegistroDocumentos
{
    public function publicar(array $documento): void;
    public function clasificar(string $id, array $roles): void;
}
final class RepositorioConsulta implements ConsultaDocumentos // solo 2 metodos
{
    public function __construct(private array $docs) {}
    public function listar(string $rol): array
    {
        return array_values(array_filter($this->docs, static fn (array $d): bool => in_array($rol, $d['roles'], true)));
    }
    public function buscar(string $texto, string $rol): array
    {
        return array_values(array_filter($this->listar($rol), static fn (array $d): bool => stripos($d['titulo'], $texto) !== false));
    }
}
final class RepositorioRegistro implements RegistroDocumentos // solo 2 metodos
{
    public array $publicados = [];
    public function publicar(array $documento): void { $this->publicados[] = $documento; }
    public function clasificar(string $id, array $roles): void {}
}
final class CatalogoController // depende solo de ConsultaDocumentos
{
    public function __construct(private ConsultaDocumentos $consulta) {}
    public function listar(string $rol): array { return $this->consulta->listar($rol); }
    public function buscar(string $texto, string $rol): array { return $this->consulta->buscar($texto, $rol); }
}
// ---- Datos ficticios y ejecución ----
$docs = [
    ['id' => 'doc-1', 'titulo' => 'Manual de enfermeria', 'roles' => ['enfermeria']],
    ['id' => 'doc-2', 'titulo' => 'Protocolo de admision', 'roles' => ['recepcion', 'admin']],
];
$antes = new CatalogoControllerAntes($docs);
$metodosGordo = (new ReflectionClass(RepositorioDocumentosGordo::class))->getMethods();
echo 'ANTES CatalogoController declara ', count($metodosGordo), " metodos (5 sin uso)\n";
try { $antes->publicar(['titulo' => 'x']); } catch (LogicException $e) { echo 'ANTES invocar metodo no usado: ', $e->getMessage(), "\n"; }
$ctrl = new CatalogoController(new RepositorioConsulta($docs));
echo 'DESPUÉS CatalogoController declara ', count((new ReflectionClass(ConsultaDocumentos::class))->getMethods()), " metodos (listar|buscar)\n";
echo 'DESPUÉS listar para enfermeria: ', $ctrl->listar('enfermeria')[0]['titulo'], "\n";
echo 'DESPUÉS listar para admin: ', $ctrl->listar('admin')[0]['titulo'], "\n";
echo 'DESPUÉS buscar "protocolo" como admin: ', $ctrl->buscar('protocolo', 'admin')[0]['titulo'], "\n";
$registro = new RepositorioRegistro();
$registro->publicar(['id' => 'doc-3', 'titulo' => 'Nuevo manual', 'roles' => ['medico']]);
echo 'DESPUÉS publicar (otro contrato, 2 metodos): ', count($registro->publicados), " documento\n";
echo 'DESPUÉS ningun cliente implementa metodos ajenos: OK', "\n";
