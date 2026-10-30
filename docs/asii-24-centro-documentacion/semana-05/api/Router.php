<?php

declare(strict_types=1);

namespace MicroHIS\Documentacion\Api;

/** Router REST minimo (Semana 5): METODO + ruta -> controlador de la Semana 4, con el sobre comun de ASII-24. */
final class Router
{
    /** @var array<string, array{0: callable, 1: list<string>}> */
    private array $rutas = [];

    /** @param list<string> $requeridos campos obligatorios validados en el borde HTTP */
    public function agregar(string $metodo, string $ruta, callable $manejador, array $requeridos = []): void
    {
        $this->rutas[strtoupper($metodo) . ' ' . $ruta] = [$manejador, $requeridos];
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public function despachar(string $metodo, string $ruta, array $entrada): array
    {
        $ruta = '/' . trim($ruta, '/');
        $clave = strtoupper($metodo) . ' ' . $ruta;
        if (!isset($this->rutas[$clave])) {
            foreach (array_keys($this->rutas) as $registrada) {
                if (str_ends_with($registrada, ' ' . $ruta)) {
                    return self::error(405, 'method_not_allowed', 'Metodo no permitido para el recurso.');
                }
            }
            return self::error(404, 'route_not_found', 'Recurso no encontrado.');
        }
        [$manejador, $requeridos] = $this->rutas[$clave];
        $faltantes = array_values(array_filter($requeridos, static fn (string $c): bool => empty($entrada[$c])));
        if ($faltantes !== []) {
            $error = self::error(422, 'validation_failed', 'Faltan campos obligatorios.');
            $error['body']['errors'] = $faltantes;
            return $error;
        }
        $respuesta = $manejador($entrada);
        if ($respuesta['status'] >= 400) {
            $codigo = (string) ($respuesta['body']['error'] ?? 'error');
            return self::error($respuesta['status'], $codigo, 'Solicitud rechazada por una regla del modulo.');
        }
        return ['status' => $respuesta['status'], 'body' => ['data' => $respuesta['body']]];
    }

    /** @return array{status:int, body:array<string, mixed>} */
    public static function error(int $status, string $codigo, string $mensaje): array
    {
        return ['status' => $status, 'body' => ['code' => $codigo, 'message' => $mensaje]];
    }
}
