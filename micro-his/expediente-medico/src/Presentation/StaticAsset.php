<?php

declare(strict_types=1);

namespace MicroHis\ExpedienteMedico\Presentation;

/**
 * Archivos de la interfaz (public/ui) para el servidor local `php -S` (DEF-EXP-10). Ese servidor no envía ETag ni
 * Last-Modified y descarta las cabeceras del router cuando este devuelve false, así que el navegador seguía usando
 * módulos JS viejos. Aquí se sirven con Cache-Control: no-cache para que el navegador revalide en cada carga.
 * Solo entrega archivos que estén realmente dentro de la carpeta raíz (sin «..» ni enlaces que salgan de ella).
 */
final class StaticAsset
{
    private const TYPES = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
    ];

    public function __construct(private readonly string $root)
    {
    }

    /** @return array{status: int, headers: array<string, string>, body: string} */
    public function render(string $relativePath): array
    {
        $root = realpath($this->root);
        $relative = str_ends_with($relativePath, '/') || $relativePath === '' ? $relativePath . 'index.html' : $relativePath;
        $file = $root === false ? false : realpath($root . DIRECTORY_SEPARATOR . ltrim($relative, '/'));
        $type = self::TYPES[strtolower(pathinfo((string) $file, PATHINFO_EXTENSION))] ?? null;
        if ($file === false || $type === null || !is_file($file) || !str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            return ['status' => 404, 'headers' => ['Content-Type' => 'text/plain; charset=utf-8'], 'body' => 'No encontrado'];
        }

        return [
            'status' => 200,
            'headers' => [
                'Content-Type' => $type,
                'Cache-Control' => 'no-cache',
                'X-Content-Type-Options' => 'nosniff',
            ],
            'body' => (string) file_get_contents($file),
        ];
    }
}
