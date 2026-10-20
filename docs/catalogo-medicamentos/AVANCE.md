# Registro de avance - ASII-14

| Semana | Requisito | Evidencia | Pruebas | Rama / PR | develop → main | Pendiente |
|---|---|---|---|---|---|---|
| 1 | Casos de uso, actividad y secuencia | `semana-01/` | PlantUML renderizado | #172 | Promovida | Revisión humana |
| 2 | RF/RNF, criterios e ISP | `semana-02/` | `evidencia/isp-segregacion.php` (4/4) | #540, #541 | #552 | Revisión humana |
| 3 | Micro-HIS en 4 capas, PDO y C4 | `semana-03/`, `micro-his/catalogo-medicamentos/` | run, pdo | #542–#545, #547 | #552 | RBAC, H5 |
| 4 | Repository PDO/InMemory, MVC y repositorio compartido | `semana-04/` | run 47, pdo 18, repository 19, http 17 | #572 (A); ramas `…semana-04-mvc…` (B) y `…semana-04-capas-docs…` (C) | Pendiente | Aprobación requerida en develop (@rortizs) |
| 5 | Contrato API, cliente-servidor, microservicio y plan | `semana-05/` | `redocly lint` válido; PlantUML reproducible | Ramas `…semana-05-contrato-api…`, `…semana-05-cliente-servidor…` y `…semana-05-plan-integracion…` | Pendiente | Aprobación de 4A; acuerdos con RBAC y H5 |
| 6 | Defensa: presentación, trazabilidad, matriz y cambio práctico | `semana-06/` | Marp PDF (8 diapositivas) revisado visualmente; PlantUML | Rama `…semana-06-presentacion…` | Pendiente | Defensa oral real; cambio real del docente |
| 7 | Componentes, contratos y refactor | `semana-07/` | Fan-in medido; PlantUML | Rama `…semana-07-componentes…` | Pendiente | UI y controlador Laravel (Semanas 11 y 16) |
| 8 | Flujo UX por rol, reglas y 5 wireframes | `semana-08/` | PlantUML y Salt renderizados y revisados | Rama `…semana-08-flujo-ux…` | Pendiente | Prueba con usuarios (no realizada) |
| 9 | Usabilidad y accesibilidad: checklist, 8 hallazgos y backlog | `semana-09/` | `contraste.php` (exit 0, 6.47:1) | Rama `…semana-09-usabilidad…` | Pendiente | Lector de pantalla y usuarios (N/E) |
| 10 | Movilidad: 4 pantallas, breakpoints y 2 escenarios | `semana-10/` | Salt renderizado y revisado | Rama `…semana-10-movilidad…` | Pendiente | Verificación a 320 px en el prototipo (Semana 11) |
| 11 | Prototipo navegable desktop y móvil, mapa y capturas | `semana-11/` | `verificar-prototipo.mjs` 24/24 (exit 0) | Rama `…semana-11-prototipo…` | Pendiente | Datos simulados; sin backend |
| 12 | Defensa con el prototipo, matriz y cambio simulado | `semana-12/` | Marp PDF (6 diapositivas) revisado | Rama `…semana-12-evaluacion…` | Pendiente | Defensa oral real |
| 13 | Plan de revisión, checklist de 14 ítems y autoverificación | `semana-13/` | Suites en verde; sondas: 5 hallazgos (AV3 mayor) | Rama `…semana-13-revision-tecnica…` | Pendiente | Confirmar roles y realizar la revisión |
| 14 | Plan SQA: 7 métricas, riesgos, controles y calendario | `semana-14/` | `metricas.php`: M1 101, M2 0, M3 2 (no cumple) | Rama `…semana-14-sqa…` | Pendiente | M3 en Semana 15; M6–M7 sin medir |
| 15 | 17 casos (3 U, 9 W, 5 B), matriz y defectos | `semana-15/`, `tests/casos.php` | 17/17 sobre develop; 16/17 en la base apilada (B3) | Rama `…semana-15-pruebas…` | Pendiente | Permisos HTTP reales (Semana 16) |
| 16 | Integración en el API v1 de Laravel con autorización real | `semana-16/`, PR #602 | Feature 6/6 sobre develop; mutación detectada | PR #602 y rama `…semana-16-evidencia…` | Pendiente | **Bloqueado:** staging, H5, modelos User/Tenant, permisos RBAC |
| 17 | Amenazas STRIDE, pruebas de seguridad y bitácora local | `semana-17/` | Feature 12/12 sobre develop | Rama `…semana-17-seguridad…` | Pendiente | **Bloqueado:** despliegue final; M6, M10 y M12 abiertos |
