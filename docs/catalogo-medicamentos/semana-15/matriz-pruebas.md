# Matriz RF/RNF → prueba, resultados y defectos

Suite: `micro-his/catalogo-medicamentos/tests/casos.php`. Se prueba solo el comportamiento real del catálogo, sin módulos ajenos.

| ID | Tipo | Requisito | Qué verifica | Rama o clase |
|---|---|---|---|---|
| U1 | Unitaria | RF-06 | `Validity::fromInput` normaliza y rechaza estados ajenos | — |
| U2 | Unitaria | RF-01, RF-02 | `Medication::register` normaliza el código, recorta y nace active | — |
| U3 | Unitaria | RF-06 | `changeValidity` conserva los datos y rechaza repetir el estado | — |
| W1a–e | Caja blanca | RNF-05 | `Router::withJson`: `!is_array`, lista no vacía, lista vacía, campo no textual y objeto válido | 5 ramas de la condición compuesta |
| W2a–d | Caja blanca | RF-03, RF-08 | `PdoMedicationCatalog::insert`: 23000 con "unique", 23000 sin "unique", 0 filas y otro PDOException | 4 ramas, forzadas con triggers SQLite reales |
| B1 | Caja negra · equivalencias | RF-04 | Término válido con resultados, válido sin resultados e inválido | 3 clases |
| B2 | Caja negra · límites | RF-02, RF-04 | Término 1/2/60/61, código 1/2/20/21 y nombre 120/121 | Fronteras |
| B3 | Caja negra · permisos | RF-07 | Matriz real de `config/rbac.php`: quién lee, que nadie escribe y que `recepcion` no lee | Ver brecha |
| B4 | Caja negra · errores | RF-03, RF-05, RF-08, RNF-03 | 409, 404, 405, 404 de ruta y 503 sin éxito | — |
| B5 | Caja negra · regresión | RF-04 | AV3: "ácido", "ÁCIDO", "acido" y "ACIDO" encuentran el mismo registro | — |

Las suites anteriores siguen vigentes: `run.php` (47), `pdo.php` (18), `repository.php` (21, ahora con el caso no ASCII) y `http.php` (17).

## Ejecución (20/10/2026, PHP 8.2.12)

| Dónde | Comando | Resultado | Captura |
|---|---|---|---|
| Rama apilada (base `b87bca1`) | `php micro-his/catalogo-medicamentos/tests/casos.php` | 16/17: **B3 falla**, porque esa base no tiene `medicamentos.ver` | `capturas/casos-rama.png` |
| Merge de prueba con `origin/develop` | Mismo comando | **17/17** (exit 0) | `capturas/casos-develop.png` |

B3 falla correctamente donde no existe el permiso del que depende el catálogo. El permiso llegó a `develop` en `7d7c9b0`, que la base apilada no contiene.

## Brechas declaradas

- **Permisos:** B3 verifica la matriz real, no un motor simulado. El micro-HIS **no aplica** permisos ni tenant (AV4); la prueba HTTP de 401, 403 y 200 por rol corresponde a la integración Laravel de las Semanas 16 y 17.
- **PostgreSQL:** no hay base de prueba disponible; las pruebas de persistencia usan SQLite real en memoria.

## Registro de defectos

| ID | Origen | Defecto | Estado | Corrección |
|---|---|---|---|---|
| AV3 | Semana 13 | Búsqueda con mayúsculas no ASCII distinta entre adaptadores | **Corregido** | `279fb7d` y regresiones en `repository.php` y B5 |
| AV1 | Semana 13 | `Medication::normalizeCode` sin usos | **Corregido** | `b055f0c` |
| P1 | Semana 11 | Datos insertados con `innerHTML` en el prototipo | Corregido | Semana 11 |
| P2 | Semana 11 | Estado contradictorio tras una caída | Corregido | `b6ec079` |
| AV2 | Semana 13 | Composición duplicada en `bin/catalogo.php` | Abierto (menor) | — |
| AV4 | Semana 13 | Sin permisos ni tenant en el micro-HIS | Abierto (mayor) | Semanas 16 y 17 |
| AV5 | Semana 13 | Ids enumerables | Abierto (menor) | Al integrar con H5 |
