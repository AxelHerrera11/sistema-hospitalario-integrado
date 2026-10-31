<?php

declare(strict_types=1);

/**
 * Busca secretos y datos personales en la carpeta de evidencias antes de conservarla como artefacto (Semana 17).
 *
 *   php micro-his/qa-calidad-versiones/bin/escanear-secretos.php <carpeta-evidencias>
 *
 * Sale con 1 si encuentra algo. Informa archivo, línea y tipo, nunca el valor completo. La clave de pruebas pública
 * de phpunit.xml y del workflow (base64 de ceros) se ignora: no es un secreto.
 */
$root = $argv[1] ?? '';
if (!is_dir($root)) {
    fwrite(STDERR, "Uso: php escanear-secretos.php <carpeta-evidencias>\n");
    exit(2);
}
$rules = [
    'jwt' => '/\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}/',
    'clave_privada' => '/-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----/',
    'app_key' => '/base64:(?!A{43}=)[A-Za-z0-9+\/]{40,}={0,2}/',
    'bearer' => '/\bBearer\s+[A-Za-z0-9._~+\/-]{20,}/i',
    'password_asignado' => '/(?:password|contraseña|secret)["\']?\s*[:=]\s*["\']?(?!\*{3}|null|""|\'\')[^\s"\',]{6,}/i',
    'url_con_credenciales' => '/\b[a-z]+:\/\/[^\/\s:@]+:[^\/\s@]+@/i',
    'correo_no_ficticio' => '/\b[A-Za-z0-9._%+-]+@(?!(?:[a-z0-9-]+\.)*(?:test|example|invalid|localhost)\b)[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/',
];
$found = 0;
$files = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
    if (!$file->isFile() || $file->getSize() > 5_000_000 || in_array($file->getExtension(), ['sqlite', 'png', 'jpg'], true)) {
        continue;
    }
    $files++;
    foreach (file($file->getPathname()) ?: [] as $n => $line) {
        foreach ($rules as $type => $pattern) {
            if (preg_match($pattern, $line, $m) === 1) {
                $found++;
                printf("%s:%d  %s  %s…\n", substr($file->getPathname(), strlen($root) + 1), $n + 1, $type, substr($m[0], 0, 6));
            }
        }
    }
}
printf("%d archivos revisados, %d hallazgos\n", $files, $found);
exit($found === 0 ? 0 : 1);
