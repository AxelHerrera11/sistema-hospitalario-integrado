# Evidencia verificable

## Estructuras inspeccionadas

| Fuente del repositorio | Evidencia observable | Qué permite afirmar |
|---|---|---|
| `tests/Unit/ExampleTest.php` | Existe una prueba unitaria de ejemplo que verifica una aserción básica con PHPUnit. | El repositorio cuenta con una ubicación de pruebas unitarias; no demuestra gestión de casos QA manuales o campañas. |
| `tests/Feature/ExampleTest.php` | Existe una prueba de ejemplo que realiza una solicitud a `/` y comprueba una respuesta exitosa. | Hay un ejemplo de prueba funcional de aplicación; no proporciona un modelo de campaña, ejecución QA ni defectos. |
| `tests/` | La estructura incluye suites Unit, Feature y Database, además de pruebas de integridad de migraciones y base de datos. | Son pruebas automatizadas del proyecto, no entidades de gestión de campañas manuales. |
| `phpunit.xml` | Declara suites `Unit`, `Feature` y `Database` apuntando a las rutas correspondientes. | PHPUnit organiza la ejecución de esas suites; la configuración no define un flujo de QA manual. |
| `composer.json` | El script `test` ejecuta `@php artisan test`; se requiere PHPUnit en dependencias de desarrollo. | Es verificable el comando configurado para ejecutar pruebas automatizadas del repositorio. |

## Contraste con el diseño

La búsqueda de estructuras de la aplicación y migraciones no encontró entidades o tablas dedicadas a casos manuales, campañas, ejecuciones QA, evidencias o defectos. En consecuencia, los RF/RNF y los contratos de `solid-lsp.md` son una propuesta académica basada en la tarea, no una descripción de funcionalidad disponible.

## Cómo verificar LSP cuando existan implementaciones

Para cada implementación del contrato de ejecución se puede aplicar la misma batería:

1. Registrar una ejecución válida con caso y campaña relacionados.
2. Comprobar resultado aprobado cuando las observaciones coinciden y fallido cuando difieren.
3. Verificar que observaciones y referencias a evidencia permanecen asociadas a la ejecución.
4. Registrar un defecto para ejecución fallida y recorrer defecto → ejecución → caso → campaña.
5. Confirmar que no se crea defecto desde una ejecución aprobada y que un rechazo respeta la precondición documentada.
6. Repetir los casos con cada implementación sustituible, sin adaptar el consumidor.

Este procedimiento describe evidencia futura de conformidad; no se presenta como prueba ya ejecutada. La evidencia actual es la estructura de pruebas automatizadas y su configuración citadas arriba.

