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

## Funcionalidad comprobada hasta hoy

- Concurrencia probada con dos procesos y conexiones independientes (Semana 15): como máximo un éxito por estancia y por cama.
- Traslado y alta por consola contra PostgreSQL con datos ficticios: el traslado cierra el origen y abre el destino contiguo en el mismo episodio; el alta cierra estancia y episodio con el mismo T. Los rechazos no escriben y un error real después de la primera escritura se revierte por completo.
- La SPA de Semana 16 recorre JWT, tenant, RBAC, API Laravel y PostgreSQL. La configuración integrada deniega de forma segura hasta que ASII-02 active los permisos; el camino de éxito se comprobó con activación temporal declarada como simulación.

## Revisión humana

- 21/10/2026: la autora confirmó la revisión personal de las semanas 3 a 10 (PR #790–#869); ver el [registro](./revision-humana.md). No es una aprobación independiente: ningún PR la tiene.
- Semanas 1 y 2: siguen pendientes de confirmación.

## Cierre provisional (23/10/2026)

No es un cierre al 100 %: quedan requisitos obligatorios sin comprobar. Estado al pausar la sesión.

### Comprobado

- **Verificación técnica por consultas de lectura** (23/10/2026) contra `his_hospital_traslados_revision_test` (PostgreSQL desechable 55433): el traslado de `00000000-0000-4000-8000-00000000e801` hacia la cama `00000000-0000-4000-8000-00000000b802` cerró la estancia origen (22/10 23:30:18, motivo «Requiere aislamiento») y abrió la estancia `843f602b-580c-4bdd-bd08-06907086566d` en el episodio `00000000-0000-4000-8000-00000000c801` (aún abierto); el alta de `00000000-0000-4000-8000-00000000e802` cerró su estancia (23/10 00:14:44) y el episodio `00000000-0000-4000-8000-00000000c802` queda `closed`, con la cama `00000000-0000-4000-8000-00000000b803` liberada. Registro técnico: se separa de la revisión visual y no la sustituye.
- **Revisión humana confirmada por la autora (22/10/2026)** en el entorno local 8090 con datos ficticios: consulta de la estancia original; traslado con motivo «Requiere aislamiento» y aparición de la estancia nueva `843f602b-…`; consulta de e801 («estancia vigente no», cama actual b802); alta de `00000000-0000-4000-8000-00000000e802` como médico con el mensaje «Alta confirmada. La cama quedó liberada».
- **Defecto D8 corregido y pendiente de integración**: PR #1108 (entrada «Traslados y altas» en el menú de la SPA, 8+/0−, 3 commits, base `develop`).
- **Defecto de logout con token vencido (aviso falso persistente) corregido y pendiente de integración**: PR #1113 (72+/3−, 3 commits, apilado sobre #1108; retarget a `develop` cuando #1108 se integre): `SESSION_REVOKED` tratado como sesión ya cerrada, bloqueo de doble clic reutilizando `createSubmitGuard`, y limpieza del aviso tras un login exitoso; pruebas en `logoutPolicy.test.mjs` y `vite build` verificados localmente. **La corrección todavía NO está activa en 8090** porque el servidor sirve la rama de #1108.
- Login enfermería/médico/recepción verificado contra `his_central_smoke` (55431) y su referencia local activa; la activación temporal de permisos propuestos RBAC vive solo como cambio local sin commit en `config/rbac.php` y no constituye aprobación productiva.

### Pendiente de prueba humana

- Consulta posterior al alta de e802 mostrando episodio cerrado y estancia no vigente.
- Rechazo de traslado de `00000000-0000-4000-8000-00000000e803` hacia la cama `00000000-0000-4000-8000-00000000b805` (ocupada por la estancia e804) sin cambios.
- Recepción sin acceso al módulo (403).
- Validación de motivo vacío (422) y vista móvil a 375 px.
- Persistencia de la sesión y de los cambios tras salir y volver por el menú.

### Pendientes externos (no propios de ASII-08)

1. Activación oficial de consulta/traslado/alta en RBAC (ASII-02), sobre la propuesta publicada en Semana 16.
2. Asociación de identidad CENTRAL con `local_user_refs` (sembrado manual actual).
3. Selección de base HOSPITAL por tenant (la conexión fija `hospital` del proveedor es temporal).
4. Rate limiting de la API para traslados y altas.
5. Staging/on-premise autorizado para el despliegue final.
6. Revisión independiente de ningún PR, según reglas del repositorio.

### Bloqueo de GitHub Actions

Vigente por facturación/límite de gasto: los jobs arrancan y fallan en ~3 s (mismo patrón en PR de otros módulos), por lo que los checks de #1108/#1113 siguen sin ejecutarse. No relanzar jobs hasta que se resuelva la facturación; entonces verificar los checks de los SHA actuales y retomar el ciclo normal de integración (merge de #1108, retarget e integración de #1113, y promoción completa a `main` antes de integrar otra parte).

### Para retomar

1. Entorno local: servidor en `http://127.0.0.1:8090` sirve el clon en la rama de #1108 con una compilación que ya incluye la entrada de menú; `.env` apunta a las bases de revisión (`his_central_smoke` en 55431 y `his_hospital_traslados_revision_test` en 55433). Datos de la revisión intactos; no recrear ni resetear.
2. Ejecutar primero las pruebas humanas pendientes de la lista superior y confirmarlas en `revision-humana.md`.
3. Con Actions desbloqueado: integrar #1108, retarget #1113 a `develop`, integrar #1113 y promover a `main`.
4. No declarar el módulo completo mientras queden los pendientes externos sin resolver.
