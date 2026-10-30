# Registro de avance - ASII-14

| Semana | Requisito | Evidencia | Pruebas | Rama / PR | develop → main | Pendiente |
|---|---|---|---|---|---|---|
| 1 | Casos de uso, actividad y secuencia | `semana-01/` | PlantUML renderizado | #172 | Promovida | Revisión humana |
| 2 | RF/RNF, criterios e ISP | `semana-02/` | `evidencia/isp-segregacion.php` (4/4) | #540, #541 | #552 | Revisión humana |
| 3 | Micro-HIS en 4 capas, PDO y C4 | `semana-03/`, `micro-his/catalogo-medicamentos/` | run, pdo | #542–#545, #547 | #552 | H5 |
| 4 | Repository PDO/InMemory, MVC y repositorio compartido | `semana-04/` | run 47, pdo 18, repository 19, http 17 | #572, #584, #585 | #611, #612, #613 | Revisión humana |
| 5 | Contrato API, cliente-servidor, microservicio y plan | `semana-05/` | `redocly lint` válido; PlantUML reproducible | #586, #587, #588; catálogo RBAC #622 | #657, #661; #625 | H5. Escrituras activas solo para `admin_hospital` desde #627 |
| 6 | Defensa: presentación, trazabilidad, matriz y cambio práctico | `semana-06/` | Marp PDF (8 diapositivas) revisado visualmente; PlantUML | #589 | #662 | Defensa oral real; cambio real del docente |
| 7 | Componentes, contratos y refactor | `semana-07/` | Fan-in medido; PlantUML | #590 | #662 | UI en Laravel |
| 8 | Flujo UX por rol, reglas y 5 wireframes | `semana-08/` | PlantUML y Salt renderizados y revisados | #591 | #663 | Prueba con usuarios (no realizada) |
| 9 | Usabilidad y accesibilidad: checklist, 8 hallazgos y backlog | `semana-09/` | `contraste.php` (exit 0, 6.47:1) | #592 | #663 | Lector de pantalla y usuarios (N/E) |
| 10 | Movilidad: 4 pantallas, breakpoints y 2 escenarios | `semana-10/` | Salt renderizado y revisado | #593 | #664 | — |
| 11 | Prototipo navegable desktop y móvil, mapa y capturas | `semana-11/` | `verificar-prototipo.mjs` 24/24 (exit 0) | #594, #595 | #665, #666 | Datos simulados; sin backend |
| 12 | Defensa con el prototipo, matriz y cambio simulado | `semana-12/` | Marp PDF (6 diapositivas) revisado | #597 | #666 | Defensa oral real |
| 13 | Plan de revisión, checklist de 14 ítems y autoverificación | `semana-13/` | Suites en verde; sondas: 5 hallazgos (AV3 mayor) | #598 | #667 | **No completa:** confirmar roles y realizar la revisión formal |
| 14 | Plan SQA: 7 métricas, riesgos, controles y calendario | `semana-14/` | `metricas.php`: M1 101, M2 0, M3 2 (no cumple) | #599 | #667 | M7 no cumple en local; repetir con volumen real |
| 15 | 17 casos (3 U, 9 W, 5 B), matriz y defectos | `semana-15/`, `tests/casos.php` | 17/17 sobre develop | #600 | #668 | — |
| 16 | Integración en el API v1 de Laravel con autorización real | `semana-16/` | Feature 6/6; humo HTTP con `tenant`, `jwt.auth` y RBAC reales | #602, #603 | #669, #670 | **No completa:** staging u on-premise |
| 17 | Amenazas STRIDE, seguridad, H5, adaptador PostgreSQL, interfaz y evidencia funcional | `semana-17/` | Suite completa 215 tests con PostgreSQL; E2E interfaz → PostgreSQL 11/11 y fallo 4/4 | #604, #683, #766, #774, #776 y esta entrega | #670, #765, #767, #775, #777 | **No completa:** despliegue en staging u on-premise; M6, M10 y M12 abiertos. Revisión humana parcial: alta, búsqueda, detalle, vigencia, validación, duplicado, roles y vista móvil (440/472 px); sin revisión humana: API forzada, aislamiento entre hospitales y caída de base |
| 18 | Evaluación final: guion, matriz y preguntas | `semana-18/` | Recorrido en Chrome 16/16 | Entrega de esta fecha | Promovida | **No completa:** demostración y defensa |

## Actualización del 30/10/2026

- **#1561:** rechazo de miembros de otro hospital. Antes podían leer, crear e inactivar medicamentos.
- **#1563:** búsqueda con tildes independiente del locale, diálogo de confirmación de vigencia, `limit` aplicado y campos desconocidos rechazados.
- **Requisito de despliegue nuevo:** `HOSPITAL_DB_TENANT_ID`.
- **Siguen bloqueados por terceros:** staging u on-premise, elegir la base por hospital, aprobación independiente de PR y GitHub Actions (NO EJECUTADO por facturación).
- **Pendiente de la estudiante:** revisión de lo no cubierto, revisión formal y defensas.

