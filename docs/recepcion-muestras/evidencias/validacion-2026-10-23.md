# Validación ejecutada el 23 de octubre de 2026

- PHPUnit: 46 pruebas y 264 aserciones, sin errores ni fallos.
- Incluye dominio, API, contrato, flujo, integridad de muestras y cinco pruebas de rollback de las migraciones hospitalarias afectadas.
- Identidad CENTRAL de las pruebas HTTP: SQLite. Base clínica: PostgreSQL real en contenedores locales de prueba.
- Navegador Edge/Playwright: 12 comprobaciones aprobadas; cuatro tamaños de pantalla, cero violaciones automáticas axe, búsqueda, corte de red, replay con la misma clave, impresión, foco, consulta y rol lector.
- Vite: compilación de producción aprobada, 136 módulos transformados.

Comando PHPUnit: vendor/bin/phpunit tests/Unit/Reception tests/Feature/Reception tests/Database/Hospital --filter 'Reception|rollback'.
Comando UI: node tests/Browser/Reception/run.cjs con Vite local; Playwright y axe instalados en el entorno QA.

Estas ejecuciones validan el código local; el resultado de GitHub Actions se consulta en cada PR. No se afirma una validación de staging ni la integración con un servicio externo de órdenes.
