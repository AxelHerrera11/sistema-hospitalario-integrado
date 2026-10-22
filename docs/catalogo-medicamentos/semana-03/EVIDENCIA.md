# Evidencia de ejecución - Semana 3

Entorno: Windows 11, `PHP 8.2.12 (cli) (ZTS Visual C++ 2019 x64)` con `pdo_sqlite` (SQLite 3.39.2) y `mbstring`, 20 de octubre de 2026. Comandos ejecutados desde la raíz del repositorio sobre el código integrado en `develop` (#542 a #545).

## Pruebas con dobles y de arquitectura (sin base de datos)

`php micro-his/catalogo-medicamentos/tests/run.php` terminó con **exit 0**:

```text
== Dominio ==
PASS  alta válida: código normalizado, sin id y vigencia active
PASS  rechaza el alta sin principio activo
PASS  rechaza el alta sin grupo farmacológico
PASS  rechaza un código con espacios internos
PASS  rechaza un nombre de más de 120 caracteres
PASS  la vigencia acepta " ACTIVE " normalizado
PASS  la vigencia rechaza estados ajenos como "expired" (no es vencimiento de lote)
PASS  inactivar conserva identificador y datos y solo cambia la vigencia
PASS  repetir la vigencia actual es una violación de dominio

== Application: alta ==
PASS  camino feliz: devuelve el registro confirmado con id y vigencia active
PASS  duplicado del código normalizado: DuplicateMedicationCode sin escribir
PASS  regla de dominio: datos inválidos no llegan a la persistencia
PASS  persistencia: un fallo al insertar se propaga y no hay resultado
PASS  persistencia: un fallo al comprobar el código se propaga

== Application: búsqueda ==
PASS  camino feliz: encuentra por principio activo sin distinguir mayúsculas
PASS  sin coincidencias devuelve lista vacía
PASS  regla: término menor de 2 caracteres se rechaza
PASS  persistencia: un fallo de búsqueda se propaga (no lista vacía)
PASS  findById de inexistente devuelve null

== Application: vigencia ==
PASS  camino feliz: inactiva y el registro persiste con sus datos
PASS  inactivo sigue apareciendo en la búsqueda (no se borra)
PASS  inexistente: MedicationNotFound sin escribir
PASS  regla: repetir inactive no escribe
PASS  persistencia: fallo al actualizar se propaga y el estado no cambia

== Presentation: alta ==
PASS  201 "Medicamento registrado" con código normalizado y active
PASS  422 MEDICATION_INVALID indica el campo
PASS  409 MEDICATION_CODE_DUPLICATED ante el mismo código
PASS  503 CATALOG_UNAVAILABLE sin mensaje de éxito si falla la escritura

== Presentation: búsqueda ==
PASS  200 con resultados y su vigencia
PASS  200 con lista vacía si no hay coincidencias
PASS  422 si el término no es válido
PASS  503 sin "results" si falla la consulta (no se confunde con vacío)

== Presentation: vigencia ==
PASS  consulta de vigencia: 200 active
PASS  consulta de inexistente: 404 MEDICATION_NOT_FOUND
PASS  200 "Vigencia actualizada" y conserva los demás datos
PASS  422 si el estado no es active/inactive
PASS  404 al cambiar la vigencia de un inexistente
PASS  503 sin "Vigencia actualizada" si falla la escritura

== Presentation: dependencias ISP ==
PASS  cada controlador depende solo de su contrato segregado

== Arquitectura ==
PASS  Domain no depende de ninguna otra capa ni de PDO
PASS  Application depende solo de Domain (sin PDO, Presentation ni Persistence)
PASS  Presentation no conoce Persistence, PDO ni SQL
PASS  Persistence usa prepare y nunca query/exec
PASS  ninguna capa implementa borrado de medicamentos

OK: 44 verificaciones aprobadas
```

## Pruebas reales del adaptador PDO (base aislada)

`php micro-his/catalogo-medicamentos/tests/pdo.php` terminó con **exit 0**. Usa `sqlite::memory:`, que crea y descarta cada proceso, y no toca ninguna base existente.

```text
Base de prueba: sqlite en memoria, SQLite 3.39.2

== PDO: alta y lectura ==
PASS  insert confirma el registro con el id generado por la base
PASS  findByCode encuentra el código normalizado y devuelve null si no existe
PASS  la restricción UNIQUE se traduce a DuplicateMedicationCode
PASS  findById de un identificador no numérico devuelve null

== PDO: búsqueda con sentencias preparadas ==
PASS  busca sin distinguir mayúsculas por nombre, código o principio activo
PASS  LIMIT se respeta
PASS  "%" se trata como literal y no como comodín
PASS  un intento de inyección (' OR 1=1 --) no devuelve filas ni altera la tabla

== PDO: vigencia ==
PASS  updateValidity persiste inactive sin borrar ni cambiar los datos
PASS  updateValidity sin fila afectada no se confirma (PersistenceFailure)
PASS  el CHECK de la base rechaza estados fuera de active/inactive

== PDO: error de persistencia real (base sin esquema) ==
PASS  insert sin tabla lanza PersistenceFailure
PASS  findByCode sin tabla lanza PersistenceFailure
PASS  findById sin tabla lanza PersistenceFailure
PASS  search sin tabla lanza PersistenceFailure
PASS  updateValidity sin tabla lanza PersistenceFailure
PASS  el flujo completo responde 503 y no informa éxito

== PDO: flujo completo con controladores ==
PASS  alta 201, cambio 200 y la consulta posterior lee inactive desde la base

OK: 18 verificaciones aprobadas
```

## Flujo por consola

Con `bin/catalogo.php` sobre un archivo SQLite nuevo en un directorio temporal fuera del repositorio, eliminado al terminar. Se resume el estado y el mensaje de cada respuesta JSON:

```text
init                                                           -> 200 Esquema aplicado (exit 0)
alta amx-500 Amoxicilina 500 mg Amoxicilina Antibiótico       -> 201 Medicamento registrado (exit 0)
alta AMX-500 Otro Otro Otro                                    -> 409 MEDICATION_CODE_DUPLICATED (exit 1)
buscar amox                                                    -> 200 count=1 (exit 0)
buscar zzz                                                     -> 200 count=0 (exit 0)
cambiar 1 inactive                                             -> 200 Vigencia actualizada (exit 0)
vigencia 1                                                     -> 200 validity=inactive (exit 0)
cambiar 1 expired                                              -> 422 MEDICATION_INVALID (exit 1)
vigencia 99                                                    -> 404 MEDICATION_NOT_FOUND (exit 1)
```

Con un archivo existente que no es una base SQLite, `alta` respondió `503 CATALOG_UNAVAILABLE` (exit 1), sin "Medicamento registrado".

## Validaciones adicionales

| Comando | Resultado | Salida |
|---|---|---|
| `php -l` sobre los 30 archivos PHP de `micro-his/catalogo-medicamentos` | Sin errores de sintaxis | 0 |
| Control por mutación en copias temporales (PR #542 a #545) | Cada defecto introducido (sin regla de estado repetido, sin comprobar duplicado, tragar el fallo de escritura, éxito ante `PersistenceFailure`, sin escapar comodines, sin comprobar `rowCount`, sin traducir UNIQUE) hizo fallar al menos una prueba | 1 o 255 |
| `java -jar plantuml.jar -tsvg -charset UTF-8 -failfast2 fuentes/*.puml` (PlantUML 1.2026.9, biblioteca C4 incluida) | Ambas vistas renderizan; los SVG regenerados son idénticos a los versionados | 0 |
| Carga de cada SVG con `DOMDocument` y revisión de enlaces relativos de los `.md` (scripts temporales) | SVG válidos; 0 enlaces rotos | 0 |
| `git diff --check origin/develop...HEAD` | Sin errores de espacios | 0 |
