# Ejecución real — 30/10/2026

Autora/ejecutora: Madelin Jazmín Cerón Molina. Windows, PHP CLI 8.2.12, pdo_sqlite;
código bajo prueba 08796d859d330d3a76656318ff73599e05633af7 y runner nuevo de este PR.
Sin internet en las pruebas, datos clínicos, credenciales, PostgreSQL o servidor HTTP.
UT/WB usan objetos/dobles; BB-01–03 lanzan PHP_BINARY/demo.php con proc_open y
argumentos estructurados, envían STDIN y verifican código 0, stderr vacío y JSON.
Cada proceso crea SQLite::memory y termina sin persistencia compartida.

```text
php -l micro-his/qa-regresion/tests/semana15.php
No syntax errors detected in micro-his/qa-regresion/tests/semana15.php
php micro-his/qa-regresion/tests/semana15.php
PASS UT-01: 4 definiciones incompletas rechazadas por DomainException
PASS UT-02: textos normalizados, saltos internos conservados y entidad readonly
PASS UT-03: Application guarda una vez la misma entidad, sin lecturas
PASS WB-01: guardia de formato: 3 rechazos sin save y camino válido con save
PASS WB-02: catch DomainException sin save y catch StorageFailure sin éxito ni detalle privado
PASS BB-01: CLI: 201, cuatro campos, textos esperados e ID hexadecimal de 32 caracteres
PASS BB-02: CLI: 400 INVALID_JSON para JSON truncado
PASS BB-03: CLI: 422 CASE_INCOMPLETE para esperado solo con espacios
PASS BB-04: interfaz pública Controller: 503 seguro; al restaurar dependencia, nueva llamada 201
9 aprobadas, 0 fallidas, 0 bloqueadas
```

Salida del runner nuevo: 0. Cada PASS ocurre después de aserciones estrictas que
lanzan RuntimeException si discrepan; cualquier Throwable produce FAIL y salida 1.
No se usa assert dependiente de configuración PHP. Sin orden compartido entre casos.

## Regresión existente separada

Comando `php micro-his/qa-regresion/tests/run.php`, salida 0: **17 aprobadas, 0 fallidas**.
PASS para InMemory/PDO: camino feliz/lectura (2), rechazo de cada texto vacío (6),
formato inválido (2), inexistente (2), duplicado sin sobrescritura (2); además doble
fallo seguro, PDO sin tabla y fallo de consulta traducido (3).
Runner anterior sin cambios. No se suman 9+17 como suite de 26 escenarios exclusivos.
HTML no ejecutado aquí; sus 17 PASS de Semana 14 se mantienen como antecedente.
Sintaxis de los doce PHP actuales: sin errores, mediante:

```powershell
Get-ChildItem micro-his/qa-regresion -Recurse -Filter *.php | ForEach-Object {php -l $_.FullName}
```

## Control negativo de las aserciones: copia temporal

Se copió exclusivamente micro-his/qa-regresion a carpeta TEMP única. Solo allí se
reemplazó la guarda `if ($id === ... )` de ManualCase por `if (false)`, conservando
el resto del módulo y runner. Se ejecutó `php <TEMP>/qa-regresion/tests/semana15.php`.
Resultado esperado y observado: salida 1; **6 aprobadas, 3 fallidas, 0 bloqueadas**:

- UT-01: esperaba cuatro rechazos y observó cero.
- WB-02: esperaba 422 y observó 201 con steps vacío.
- BB-03: esperaba 422 y observó 201 con expected vacío.

Este fallo inducido valida sensibilidad, no un defecto encontrado en la base original.
Reproducir solo en copia temporal, nunca editar src del repositorio para este control.
`git hash-object micro-his/qa-regresion/src/Domain/ManualCase.php` coincide con
`git rev-parse HEAD:micro-his/qa-regresion/src/Domain/ManualCase.php`:
`241891e7a4377559ea883f81d7e7d9f88cab48bd`; diff de src vacío.
Las fallas del mutante no se mezclan con M01 ni con el registro de defectos reales.
