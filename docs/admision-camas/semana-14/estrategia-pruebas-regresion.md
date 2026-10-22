# Estrategia de pruebas y regresión — ASII-07

| Nivel | Qué | Cuándo | Herramienta |
|---|---|---|---|
| Unitario y contrato | `tests/run.php` (PDO e InMemory) y `tests/api.php` | Cada commit | PHP CLI |
| Contrato HTTP | `redocly lint` y la prueba de rutas | Cada cambio de API | `npx @redocly/cli@1.34.5` |
| UI | Recorrido del prototipo: 409, sin respuesta y sin camas | Cada cambio de pantalla | puppeteer |
| Integración | Traslados (ASII-08) y Dashboard (ASII-09) | Semana 16 | Regresión manual y E2E |

**Regla de regresión:** todo defecto corregido agrega una prueba que fallaba antes; la batería completa corre antes de cada push.
