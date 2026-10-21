# Estrategia de pruebas y regresión — ASII-21

| Nivel | Qué | Cuándo | Herramienta |
|---|---|---|---|
| Unitario y contrato | `tests/run.php` (PDO e InMemory) y `tests/api.php` | Cada commit | PHP CLI |
| Contrato HTTP | `redocly lint` y la prueba de rutas | Cada cambio de API | `npx @redocly/cli@1.34.5` |
| UI | Recorrido del prototipo: lectura incorrecta, 403, 409 y sin red | Cada cambio de pantalla | puppeteer |
| Integración | Evento real de ASII-20 por la cola | Semana 16 | Regresión manual y E2E |

**Regla de regresión:** todo defecto corregido agrega una prueba que fallaba antes; la batería completa corre antes de cada push.
