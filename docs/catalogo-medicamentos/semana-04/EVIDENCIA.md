# Evidencia de ejecución - Semana 4

Entorno: Windows 11, `PHP 8.2.12 (cli) (ZTS)` con `pdo_sqlite` (SQLite 3.39.2), 20 de octubre de 2026. Códigos de salida medidos directamente, sin tuberías.

| Comando | Tipo | Resultado | Salida |
|---|---|---|---|
| `php micro-his/catalogo-medicamentos/tests/run.php` | Dobles y arquitectura | OK: 47 verificaciones | 0 |
| `php micro-his/catalogo-medicamentos/tests/pdo.php` | Adaptador PDO real, `sqlite::memory:` | OK: 18 verificaciones | 0 |
| `php micro-his/catalogo-medicamentos/tests/repository.php` | Contrato del Repository en InMemory y en PDO | OK: 19 verificaciones | 0 |
| `php micro-his/catalogo-medicamentos/tests/http.php` | Router en InMemory y HTTP real con `php -S` y SQLite temporal | OK: 17 verificaciones | 0 |
| `php -l` sobre todos los PHP del micro-HIS | Sintaxis | Sin errores | 0 |

## Qué demuestra cada prueba

- **Ambos adaptadores:** `tests/repository.php` ejecuta las mismas 9 verificaciones contra InMemory y contra PDO y comprueba que ambos producen los mismos resultados observables. Cubre alta, duplicado, búsqueda literal, orden y límite, vigencia persistida sin borrado, actualización no confirmada y casos de uso.
- **MVC:** `tests/http.php` recorre todas las rutas y sus errores (400, 404, 405, 409 y 422). Además levanta `php -S` en un puerto libre con una base temporal propia, hace alta, búsqueda y cambio de vigencia por HTTP real y elimina la base al final.
- **Fronteras:** `tests/architecture.php` falla si el Router usa Domain, Persistence o SQL, o si un caso de uso o controlador depende del Repository completo.

## Controles por mutación (copias temporales fuera del repositorio)

| Defecto introducido | Resultado |
|---|---|
| InMemory no pasa la búsqueda a minúsculas | FAIL en la búsqueda (exit 1) |
| InMemory no rechaza el duplicado | FAIL en el duplicado (exit 1) |
| Un controlador importa `MedicationRepository` | FAIL "se conserva el ISP" (exit 1) |

## Prueba manual por HTTP

Con `php -S 127.0.0.1:8085 public/index.php` y una base SQLite temporal se obtuvo:
- 201 en el alta, con el acento de "Antibiótico" conservado al enviarlo como archivo UTF-8.
- 409 en el duplicado, 422 con un campo numérico y 400 con un cuerpo que no es JSON.
- 200 en la búsqueda y en el cambio a `inactive`.
- 404 con un id inexistente, 405 con DELETE y 404 en una ruta desconocida.
- 503 sin `MICROHIS_CATALOG_DB`.

Un JSON enviado desde la consola de Windows con caracteres no UTF-8 se rechaza con 400, como corresponde.

## No verificado

- PostgreSQL y la tabla H5, que no existe todavía.
- Autorización por rol: RBAC aún no define permisos de medicamentos.
