# Evidencia de la evaluación — ejecución nueva del 29/10/2026

Entorno: Windows local, PHP 8.2.12 CLI, PDO/pdo_sqlite; base 52097752 y núcleo
de Semana 4 integrado. No se añadieron funcionalidades ni pruebas nuevas.
Todas las demostraciones usan SQLite en memoria y datos ficticios; sin .env,
PostgreSQL hospitalario, credenciales ni migraciones. Duración no medida.

## Comandos exactos desde la raíz

```powershell
php --version
# PHP 8.2.12 (cli), ZTS Visual C++ 2019 x64.
php micro-his/qa-regresion/tests/run.php
# 17 aprobadas, 0 fallidas; exit 0.
'{"purpose":"Evaluar caso QA","steps":"Abrir formulario","expected":"Campos visibles"}' | php micro-his/qa-regresion/demo.php
# status 201, body.data con propósito/pasos/esperado y un id nuevo de 32 caracteres.
'{"purpose":[]}' | php micro-his/qa-regresion/demo.php
# {"status":400,"body":{"error":"INVALID_INPUT"}}
'{"purpose":"   ","steps":"Abrir formulario","expected":"Campos visibles"}' | php micro-his/qa-regresion/demo.php
# {"status":422,"body":{"error":"CASE_INCOMPLETE"}}
'no es JSON' | php micro-his/qa-regresion/demo.php
# {"status":400,"body":{"error":"INVALID_JSON"}}
```

La identidad observada en el alta fue b9fe57f469de69307760db9763f02a88; no es
un dato fijo ni ID recuperable en otro proceso: la demo crea otra DB en cada llamada.

Error de persistencia reproducido sin alterar demo.php: conexión nueva sin esquema,
composición del mismo controlador y caso de uso; script PHP enviado por STDIN:

```powershell
@'
<?php
require 'micro-his/qa-regresion/bootstrap.php';
$repository = new MicroHis\QaRegresion\Persistence\PdoCaseRepository(new PDO('sqlite::memory:'));
$controller = new MicroHis\QaRegresion\Presentation\CaseController(new MicroHis\QaRegresion\Application\DesignCase($repository));
$result = $controller->create(['purpose'=>'Caso ficticio','steps'=>'Abrir formulario','expected'=>'Campos visibles']);
echo json_encode($result).PHP_EOL;
'@ | php
# {"status":503,"body":{"error":"CASE_STORAGE_UNAVAILABLE"}}
```

## Desglose de suite y alcance

| Escenario ejecutado | Cantidad | Resultado |
|---|---|---|
| Camino feliz/lectura/segunda alta en InMemory y PDO | 2 | PASS |
| Tres campos vacíos en ambos adaptadores | 6 | PASS |
| Formato incorrecto/null en ambos | 2 | PASS |
| Identidad inexistente en ambos | 2 | PASS |
| Duplicado sin sobrescribir en ambos | 2 | PASS |
| Fallo save de doble y PDO sin tabla | 2 | PASS |
| Fallo de consulta PDO traducido con causa | 1 | PASS |

La misma batería compartida prueba sustitución de almacenamiento, no todo el
contrato futuro de ejecuciones. No se informa número de aserciones/cobertura porque
el harness no lo reporta. Los 17 PASS son de esta ejecución, no copiados de Semana 5.
No hay respuestas HTTP, screenshots ni despliegue demostrados.

## Comprobaciones de entrega

Git status/branch/log/fetch confirmaron rama Semana 6 limpia al inicio, upstream
inicial origin/develop y base sin divergencia. GitHub: #144/#1367/#1368 fusionados,
#1400 abierto/no fusionado; git ls-tree no encontró Semana 5 en origin/develop.
Se consultó Semana 5 con git show desde su rama, sin incorporar sus commits.
Validación final: ocho diapositivas numeradas y siete separadores, enlaces locales
existentes, archivos propios y git diff --check / git diff --cached --check: PASS.
Diff completo contra origin/develop dentro de 400 líneas; no acredita defensa oral realizada.
