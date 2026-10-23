# Trazabilidad provisional RBAC, semanas 1–17

Fuente de requisitos: los README históricos de `docs/rbac/semana-01` a `semana-17`, contrastados con la tabla Plan semanal ASII del README raíz en develop a3925260. El archivo docs/weekly-plan.md que ese README enlaza no existe en esa versión. Los archivos de código y prueba son candidatos de evidencia actual, no prueba de entrega o aceptación en la fecha de cada semana. Ver cierre-local-2026-10-21.md para la continuación de navegador/HTTP y gobernanza.

| Semana | Requisito según documento local | Código o artefacto a contrastar | Prueba/evidencia disponible o pendiente | Estado |
|---|---|---|---|---|
| 1 | OOP/UML: asignar rol, autorizar y denegación trazable | `semana-01/README.md` y diagramas; `micro-his/rbac` | Revisión de coherencia de tres diagramas y requisitos, no ejecutada aquí | No certificado |
| 2 | Diseño OCP | `semana-02/README.md`, contratos `micro-his/rbac` | Contrastar extensión y contratos con rúbrica | No certificado |
| 3 | Micro-HIS PHP: capas, PDO preparado, asignación, autorización y auditoría | `semana-03/README.md`, `micro-his/rbac` | `micro-his/rbac/tests/run.php`; revisar además arquitectura y criterios oficiales | Parcial |
| 4 | MVC, interfaz Repository, PDO/InMemory y repositorio compartido | `semana-04/README.md`, adaptadores RBAC | `micro-his/rbac/tests/inmemory.php`, `authorization.php`, `authorization_errors.php`; revisar diagramas y responsabilidades | Parcial |
| 5 | Cliente-servidor y contrato REST | `semana-05/README.md`, `permisos.md`, `app/Providers/RbacServiceProvider.php` | `PublishedContractsTest`, listado de rutas; aclarar endpoints propuestos frente a registrados | Parcial: pruebas actuales |
| 6 | Primera evaluación parcial | `semana-06/README.md` | Falta evaluación/aceptación contra rúbrica | No certificado |
| 7 | Componentes backend/frontend, DIP | `semana-07/README.md`, `app/Rbac`, contratos de dominio | `IntegrationTest`, `SecurityTest`; revisar entrega exigida | Parcial: pruebas actuales |
| 8 | UX de roles, permisos y rutas | `semana-08/README.md`, `resources/js/modules/rbac` | Recorrido real por rol y estados visuales pendiente | No certificado |
| 9 | Usabilidad/accesibilidad | `semana-09/README.md` | Teclado, foco, contraste y evaluación Nielsen/WCAG no realizados en esta revisión | No certificado |
| 10 | Diseño responsive | `semana-10/README.md`, estilos de componentes | Faltan capturas y recorrido móvil/tableta/escritorio | No certificado |
| 11 | Prototipo navegable | `semana-11/README.md`, router, catálogo y layout | Build y pruebas de permisos pasan; no se ejecutó navegador | Parcial |
| 12 | Segunda evaluación parcial | `semana-12/README.md` | Falta rúbrica y aceptación de evaluación | No certificado |
| 13 | Plan formal de revisión, checklist, responsables y cierre de hallazgos | `semana-13/README.md`, checklist e informe de esa carpeta | Falta revisar responsables, evidencia de revisión humana y cierre contra el código vigente | No certificado |
| 14 | Plan SQA | `semana-14/README.md`, pruebas de seguridad | Suites ejecutadas no acreditan por sí solas todos los compromisos del plan | Parcial |
| 15 | Pruebas unitarias, caja blanca y negra | `semana-15/README.md`, `tests/Feature/Rbac`, `tests/Integration/Rbac`, `tests/Database` | Logs conjuntos en `.review-validation/rbac-conjunta`; contrastar cobertura exigida | Parcial |
| 16 | Integración funcional y cobertura de módulos | `semana-16/README.md`, `AuditDenials`, providers y matriz | Writer/reader PostgreSQL pasa; cobertura detallada en revisión de cierre; faltan E2E y gobernanza HTTP | Parcial |
| 17 | Seguridad y entrega/despliegue | `semana-17/README.md`, guard, permisos por acción, login/logout | JWT/API automatizados, router y build pasan; faltan navegador y staging | Parcial |

Ver [revisión de cierre](revision-cierre-2026-10-21.md) para versiones exactas, límites de las pruebas y pasos de validación. No se afirma que las 17 semanas estén aprobadas.
