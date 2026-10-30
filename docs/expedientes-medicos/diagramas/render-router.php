<?php

// Servidor auxiliar temporal (mismo procedimiento que docs/CI-E2E/diagramas): lista las fuentes .mmd de este módulo
// y guarda el SVG renderizado junto a cada carpeta fuentes/. Solo acepta docs/expedientes-medicos/**/fuentes/*.mmd.
//   php -S 127.0.0.1:8097 docs/expedientes-medicos/diagramas/render-router.php   → abrir http://127.0.0.1:8097/
$repo = getenv('REPO_DIR') ?: dirname(__DIR__, 3);
$base = realpath($repo.'/docs/expedientes-medicos');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$safe = static function (string $rel) use ($repo, $base): ?string {
    $full = realpath($repo.'/'.$rel);

    return $full !== false && str_starts_with($full, $base) && str_ends_with($full, '.mmd') && basename(dirname($full)) === 'fuentes' ? $full : null;
};
if ($path === '/') {
    header('Content-Type: text/html; charset=utf-8');
    readfile(__DIR__.'/render.html');

    return true;
}
if ($path === '/list') {
    $out = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base)) as $f) {
        if (str_ends_with($f->getFilename(), '.mmd')) {
            $out[] = 'docs/expedientes-medicos/'.str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
        }
    }
    sort($out);
    header('Content-Type: application/json');
    echo json_encode($out);

    return true;
}
$full = $safe((string) ($_GET['p'] ?? ''));
if ($full === null) {
    http_response_code(400);
    echo 'ruta no permitida';

    return true;
}
if ($path === '/src') {
    header('Content-Type: text/plain; charset=utf-8');
    readfile($full);

    return true;
}
if ($path === '/save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $svg = (string) file_get_contents('php://input');
    if (! str_starts_with(ltrim($svg), '<svg')) {
        http_response_code(422);
        echo 'no es svg';

        return true;
    }
    $target = dirname($full, 2).'/'.basename($full, '.mmd').'.svg';
    file_put_contents($target, $svg);
    echo json_encode(['saved' => basename($target), 'bytes' => strlen($svg)]);

    return true;
}
http_response_code(404);

return true;
