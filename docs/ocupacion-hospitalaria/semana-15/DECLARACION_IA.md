# Declaración de uso de inteligencia artificial

**Herramienta:** OpenCode con modelos de OpenAI, Anthropic (Claude) y MiniMax, con GitHub CLI,
Node.js y la imagen de pruebas `localhost/php85-pgsql-full` sobre PostgreSQL 16.

**Propósito:** construir la entrega de la semana 15 del módulo ASII-09 (Dashboard de ocupación
hospitalaria): pruebas unitarias, de caja blanca y de caja negra trazadas a los RF/RNF, con
comandos y resultados reales, y registro de los defectos encontrados.

**Contenido aceptado o modificado:** la trazabilidad se construyó leyendo las pruebas que ya
existen en `develop` (`tests/Feature/Ocupacion/`, `tests/Database/Hospital/OccupancySnapshotIntegrityTest.php`
y `resources/js/modules/ocupacion/ocupacion.test.mjs`) y ejecutándolas de nuevo. Las cifras de
pruebas y aserciones de este documento son las salidas de esas ejecuciones del 21 de octubre de
2026, no estimaciones. Los dos defectos registrados son reales: el primero fue reportado por la
revisora del equipo y el segundo se detectó al ejecutar la suite completa.

**Lo que no se hizo:** no se declararon como probados los casos sin cobertura. El filtro por sala
(RF-OCC-05) y la medición con 10 000 camas (RNF-OCC-05) figuran explícitamente como pendientes, y
no se ejecutó lector de pantalla ni certificación WCAG.

**Verificación:** `php micro-his/ocupacion-hospitalaria/tests/run.php` →
`OK: 12 verificaciones aprobadas`; `php artisan test tests/Feature/Ocupacion` →
`16 passed (66 assertions)`; `php artisan test tests/Database/Hospital/OccupancySnapshotIntegrityTest.php` →
`6 passed (59 assertions)`; `node --test resources/js/modules/ocupacion/ocupacion.test.mjs` →
`OK: 14 aserciones`.

**Validación humana:** revisada y aprobada por Javier Alexander Fajardo López.
