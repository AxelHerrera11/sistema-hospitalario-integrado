# Estrategia de pruebas y regresión — ASII-17

| Nivel | Qué | Cuándo | Herramienta |
|---|---|---|---|
| Unitario y contrato | `tests/run.php` (PDO e InMemory) y `tests/api.php` | Cada commit | PHP CLI |
| Contrato HTTP | `redocly lint` y la prueba de rutas | Cada cambio de API | `npx @redocly/cli@1.34.5` |
| UI | Recorrido del prototipo en Chrome headless | Cada cambio de pantalla | puppeteer |
| Integración | Órdenes y Resultados consumiendo el catálogo | Semana 16 | Regresión manual (Madelin) y E2E (Cindy) |

**Regla de regresión:** todo defecto corregido agrega una prueba que fallaba antes; la batería completa corre antes de cada push.
