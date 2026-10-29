# ASII-06 — Semana 14: Confiabilidad y aseguramiento de calidad

Autora: Madelin Jazmín Cerón Molina (`MadelinCeron`). Refs #11.
Entrega académica: 30/10/2026, 23:59, America/Guatemala.

Plan SQA documental del catálogo de salas/wards y camas: siete métricas,
diez riesgos, controles preventivos/detectivos y seguimiento hasta Semana 15.
Los umbrales son metas propuestas; los resultados siguientes son mediciones
locales del 28/10/2026 sobre base `3ea30faa44e6ece877f9141405374cb519279b45`.

- [Plan SQA](./plan-sqa.md): requisitos, arquitectura, aceptación, controles y roles.
- [Métricas](./metricas.md): fórmulas, fuentes, umbrales y acciones.
- [Matriz de riesgos](./matriz-riesgos.md): prioridad, consecuencias y tratamiento.
- [Calendario](./calendario-seguimiento.md): fechas propuestas y evidencias.

## Base y entregas previas

Rama: `feature/asii-06-semana-14-salas-camas-madelinceron`, limpia al inicio.
HEAD inicial y `origin/develop` coinciden en `3ea30faa`; upstream inicial:
`origin/develop`. Se ejecutó fetch; no fue necesario pull, merge ni rebase.
[Semana 11](../semana-11/README.md) entrega Vue de consulta;
[Semana 12](../semana-12/README.md) documenta defensa y límites.
El [PR #1073](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1073)
estaba abierto al medir (corrección `b6cbafe7`); se integró en develop el
29/10/2026. Sus hallazgos se consultaron desde su rama, sin modificarlos.
El [PR #1154](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/1154)
está integrado: limita temporalmente una instancia a un hospital; no resuelve
la selección dinámica de base hospitalaria.

## Validación local nueva

Comandos ejecutados desde la raíz; las suites PHP usan `phpunit.xml`.

| Comando | Resultado observado |
|---|---|
| `npm run build` | PASS; 142 módulos transformados. |
| `node --test tests/Frontend/WardCatalogNavigationTest.mjs` | PASS; 2 tests, 2 aprobados, 0 fallos. |
| `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/WardCatalogEndpointTest.php` | PASS; 13 tests, 54 assertions. |
| `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogHospitalIsolationTest.php` | ERROR; 3 tests, 0 assertions, 3 errores de conexión. |
| `php vendor/phpunit/phpunit/phpunit tests/Feature/SalasCamas/CatalogLaravelIntegrationTest.php` | ERROR; 1 test, 0 assertions, 1 error de conexión. |
| `php micro-his/salas-camas/tests/run.php` | PASS; 4 mensajes PASS: consulta, creación/default, estado inválido y fallo fake. |
| `php artisan route:list --path=api` | PASS; 30 rutas, incluidas las dos GET del catálogo; sin altas HTTP del catálogo. |
| `git diff --check` y comprobación del diff preparado | PASS; sin errores de whitespace. |

PostgreSQL rechaza conexión en `127.0.0.1:55433` (`SQLSTATE[08006]`). Las cuatro
pruebas afectadas fallan en preparación: no prueban aislamiento ni persistencia
en esta ejecución. No se lanzaron migraciones ni se cambiaron credenciales.
El fake no sustituye restricciones PostgreSQL; build y Node no prueban la UI.
No se hizo login visual ni se usan datos clínicos reales. Actions no se declara
validado por estos resultados locales. El PR documenta el bloqueo para revisión.

## Límites y pendientes

La creación existe en `CatalogService` y `CatalogController` del micro-HIS;
no hay formulario Vue ni POST Laravel de alta de wards/camas. Los estados
son dominios permitidos, no una política verificada de transiciones clínicas.
No hay campo de capacidad máxima en `wards`: contar camas no prueba aforo
ni disponibilidad clínica. Esas brechas pasan al seguimiento de Semana 15.
Madelin Cerón mantiene documentación y mediciones; revisión técnica, corrección
técnica y verificación independiente: **Pendiente de asignación**. La aprobación
del PR se registra en GitHub según `.github/BRANCH_PROTECTION.md`.

- [x] Siete métricas y diez riesgos con responsables o roles pendientes.
- [x] Requisitos existentes y controles relacionados con evidencia.
- [x] Validaciones ejecutadas con bloqueos explícitos.
- [ ] Integración PostgreSQL e aislamiento verificados en esta entrega.
- [ ] Recorrido visual autenticado, revisión independiente y aprobación.
