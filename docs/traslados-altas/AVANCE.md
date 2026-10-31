# Registro de avance - ASII-08

Estudiante: María Yamilet Lindo Pablo (`Yamilet1235`) · Issue `Refs #9` · Micro-HIS: [`micro-his/traslados-altas`](../../micro-his/traslados-altas/README.md)

| Semana | Requisito | Evidencia | Pruebas | Rama / PR | develop → main | Pendiente |
|---|---|---|---|---|---|---|
| 1 | Casos de uso, actividad y secuencia | `semana-01/` | Diagramas renderizados | #55 | Promovida | Revisión humana |
| 2 | LSP, RF/RNF, criterios y evidencia verificable | `semana-02/` | Demo LSP en memoria (no es un servicio productivo) | #274, #291, #347 | Promovida | Revisión humana |
| 3 | Micro-HIS PHP vanilla en 4 capas, PDO preparado, pruebas y C4/UML | `semana-03/` | `tests/run.php` 63/63; `tests/postgres.php` 12/12 (PostgreSQL desechable); recorrido de consola con traslado y alta persistidos | #790, #792, #794, #796, #798 y esta entrega | #791, #793, #795, #797, #799 | Concurrencia con conexiones independientes; R-TA-01 y `beds.status` con ASII-06 |
| 4 | MVC, contrato Repository, adaptadores PDO e InMemory y repositorio compartido | `semana-04/` | `run.php` 72/72 (contrato InMemory 8/8); `postgres.php` 20/20 (contrato PDO 8/8) | #802 y esta entrega | #803 | Acuerdos con ASII-06 y ASII-07 |
| 5 | Cliente-servidor: contrato API, propiedades, frontera de microservicio y plan académico | `semana-05/` | `run.php` 88/88; recorrido HTTP con `php -S` sobre PostgreSQL (401, 403, 409, 201, 200, 409) | #849 y esta entrega | #850 | Autenticación real, tenant, RBAC y observabilidad (S16–S17) |
| 6 | Primer parcial: 7 diapositivas, diagrama trazable, matriz de 15 decisiones y cambio práctico (simulado) | `semana-06/` | `run.php` 88/88; 503 real con `Retry-After` | Esta entrega | — | Sin defensa oral declarada |
| 7 | Componentes backend/frontend con contratos y refactor `EjecutorOperaciones` | `semana-07/` | `run.php` 95/95; defecto `motivo` como arreglo: 201 antes y 422 después | Esta entrega | — | Frontend solo en diseño |
| 8 | Flujo por roles, 6 wireframes anotados y prototipo de interfaz contra la API real | `semana-08/` | `ui-mensajes` 4/4; recorrido en Chrome 9/9 contra PostgreSQL | #859 y esta entrega | #860 | Roles propuestos sin RBAC; sin pruebas con usuarios |
| 9 | Checklist de 14 criterios (heurísticas y WCAG), 7 hallazgos reales y backlog | `semana-09/` | 10 sondas automáticas en Chrome: 3 cumplen y 7 no | Esta entrega | — | Sin pruebas con usuarios; corrección en la S10 |
| 10 | Movilidad de 320 a 430 px, breakpoints, 5 pantallas, 2 escenarios con conexión limitada y corrección de H1–H4, H6 y H7 | `semana-10/` | Recorrido 9/9 en 3 anchos; móviles 4/4; sondas 9/10; `ui-mensajes` 7/7 | #866 y esta entrega | #868 | H5 (lista de camas) en la integración; sin dispositivo físico |
| 11 | Prototipo navegable en escritorio y móvil: mapa, camino feliz, error crítico y simulaciones | `semana-11/` | Recorrido 9/9 a 1024 y 375 px; error crítico 2/2 | Esta entrega (capturas en #873) | — | Revisión humana de la S11 sin confirmar |
| 12 | Segundo parcial: 5 diapositivas, recorrido, matriz de 14 decisiones de UX y cambio práctico (Escape y foco, simulado) | `semana-12/` | `teclado.mjs` 1/4 → 4/4; recorrido 9/9; `ui-mensajes` 7/7 | Esta entrega | — | Sin defensa oral declarada; revisión humana de la S12 sin confirmar |
| 13 | Plan de revisión formal, checklist de 15 ítems con autoverificación, roles propuestos y formato de hallazgos | `semana-13/` | Criterios de entrada: `run.php` 95/95, `postgres.php` 20/20, `ui-mensajes` 7/7 | Esta entrega | — | **No completa:** confirmar roles y realizar la revisión; C7 y C12 sin evidencia |
| 14 | Plan SQA: 7 métricas, 5 riesgos, 8 controles y calendario | `semana-14/` | `metricas.php`: M1 95/95, M2 máx. 393, M3 10/10, M4 0; M5 9/10; M6 20/20 | Esta entrega | — | M7 concurrencia sin cumplir (S15); permisos con ASII-02 |
| 15 | Matriz RF/RNF → prueba (4 U, 3 W, 6 B), concurrencia real con conexiones independientes y 7 defectos reales | `semana-15/` | `run.php` 101/101; `postgres.php` 20/20; concurrencia 20/20 (8/10 antes de corregir D5); `ui-mensajes` 7/7 | #895 y esta entrega | #896 | Corte real durante el COMMIT sin probar |
| 16 | Integración funcional: SPA Vue → API Laravel → JWT/tenant/RBAC → micro-HIS → PostgreSQL | `semana-16/` | API Feature 3/3; PostgreSQL 3/3; HTTP 11/11; SPA 6/6; `run.php` 101/101 | #908, #913, #915, #919 | #909, #914, #916, #920 | Activación RBAC, `local_user_refs` y conexión HOSPITAL por tenant |
| 17 | Seguridad y preparación de despliegue: 17 amenazas, datos sensibles, reversión y bloqueos | `semana-17/` | API Feature 5 pruebas/44 aserciones; `run.php` 101/101; SPA 3/3; build 109 módulos | Esta entrega | Pendiente | Despliegue final bloqueado por RBAC, identidad, conexión por tenant y entorno autorizado |
| 18 | Evaluación final: guion, matriz y preguntas | `semana-18/` | Sin ejecutar: preparada | Entrega de esta fecha | Promovida | Demostración, defensa y activación RBAC |

## Funcionalidad comprobada hasta hoy

- Concurrencia probada con dos procesos y conexiones independientes (Semana 15): como máximo un éxito por estancia y por cama.
- Traslado y alta por consola contra PostgreSQL con datos ficticios: el traslado cierra el origen y abre el destino contiguo en el mismo episodio; el alta cierra estancia y episodio con el mismo T. Los rechazos no escriben y un error real después de la primera escritura se revierte por completo.
- La SPA de Semana 16 recorre JWT, tenant, RBAC, API Laravel y PostgreSQL. La configuración integrada deniega de forma segura hasta que ASII-02 active los permisos; el camino de éxito se comprobó con activación temporal declarada como simulación.

## Revisión humana

- 21/10/2026: la autora confirmó la revisión personal de las semanas 3 a 10 (PR #790–#869); ver el [registro](./revision-humana.md). No es una aprobación independiente: ningún PR la tiene.
- 30/10/2026: la autora probó a mano el traslado, el alta y la consulta posterior en la versión integrada, con el alcance de su fila en el registro.
- Semanas 1 y 2: siguen pendientes de confirmación.

## Estado al 30/10/2026

### Integrado

- #1108 (menú) y #1113 (cierre de sesión), que aquí constaban como pendientes.
- #1561: rechazo de miembros de otro hospital.
- Diagramas de la Semana 5, PDF de las Semanas 6 y 12, capturas de la Semana 15 y guion de la Semana 18.

### Pendiente de la estudiante

Sigue vigente lo que falta confirmar en [`revision-humana.md`](./revision-humana.md):

- Rechazo por cama ocupada, recepción sin acceso, motivo vacío, 375 px y volver por el menú.
- Revisión de las Semanas 1, 2 y 11 a 18.
- Defensas de las Semanas 6, 12 y 18.

### Permisos (30/10/2026)

#1577 activa la matriz final decidida por RBAC. El alta corresponde a cualquier médico con permiso vigente del hospital. **No se restringe al médico tratante:** no existe control de la relación médico-paciente.

### Bloqueado por terceros

| Bloqueo | Depende de | Qué falta |
|---|---|---|
| Crear `local_user_refs` | Tenants e identidad | Sincronización de membresías CENTRAL |
| Elegir la base HOSPITAL por hospital | Plataforma y Tenants | Uso de `clinical_endpoint` |
| Límite de tasa de `/api/v1` | API Gateway | Política definida y probada |
| Staging u on-premise | Coordinación | Entorno autorizado |
| Aprobación independiente de PR | Equipo | Revisión según `BRANCH_PROTECTION.md` |
| GitHub Actions | Administración del repositorio | Facturación resuelta; hoy NO EJECUTADO |

**No se declara el módulo completo:** en `develop` sigue respondiendo 403 hasta la activación.

