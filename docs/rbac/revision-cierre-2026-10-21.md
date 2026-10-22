# Revisión de cierre RBAC — 21 de octubre de 2026 (Guatemala)

**Estado: Correcciones implementadas; cierre funcional pendiente de integración, cobertura y validación del entorno.**

## Publicación posterior de esta revisión

Por instrucción de la usuaria se publicó la corrección de permisos de acción en #858, commit `0bd73f06`. Este informe y sus evidencias se publican por separado desde `codex/rbac-evidencia-cierre`, basada en develop `070a255c`, para respetar 400 líneas por PR. Los resultados de pruebas que siguen pertenecen al snapshot `0926ad98` con las correcciones indicadas, no a una nueva ejecución sobre `070a255c`. Se mantienen los PR en borrador y sin merge. Las referencias a cambios «locales» y «sin publicar» describen el momento de la revisión, anterior a esta publicación.

No se realizó merge, push, aprobación ni cambio de estado de los PR. Los cambios locales de Pacientes encontrados al iniciar la revisión se conservaron. Este informe distingue pruebas automatizadas locales de demostraciones con usuarios en navegador y de staging.

## Versión examinada y composición conjunta

- `origin/develop` actualizado: `0926ad987527b3f9faef03ddd02045dbb981ccef`.
- #848: `f568eb903da31065578838e83fa82926be9f4095`, OPEN/Draft, base develop, 201 adiciones + 2 eliminaciones.
- #858: `5a5d69e948a0bce578c269a4d385985b02cca6cd`, OPEN/Draft, base develop, 190 adiciones + 30 eliminaciones.
- Copia aislada: `.review-validation/rbac-conjunta/app`, desde la raíz del workspace Hospital final. No es un commit ni una rama fusionada.
- Sobre develop se aplicó el contenido propio de ambos PR y los cuatro archivos locales modificados de Pacientes/router. `composition.json` registra los SHA. No se sustituyeron archivos ajenos al diff propio de los PR.
- El ancestro común de cada rama es `3afc7823e17b73f9531c066b104075a21d3288dc`; no hay modificaciones adicionales de develop en los archivos propios de esos diffs (`compatibilidad-base.txt`). `archivos-validados.json` conserva las huellas SHA256 de los archivos de seguridad y UI examinados.
- La validación conjunta incluye cambios de Pacientes aún no publicados en #858. Su resultado no debe atribuirse al SHA remoto de #858 sin esa aclaración.

## Evidencia ejecutada en esta revisión

En la copia aislada:

| Comprobación | Resultado | Evidencia conservada en `docs/rbac/evidencias-cierre/` |
|---|---|---|
| `php vendor/bin/phpunit` | 222 pruebas, 5487 aserciones, OK | `phpunit-conjunta.txt` |
| `php vendor/bin/phpunit tests/Feature tests/Integration/Rbac --display-warnings` | 108 pruebas, 4237 aserciones, OK | Resumen de la salida observada en esta tabla; también incluidas en la suite completa |
| `php vendor/bin/phpunit tests/Database/Central/RbacDenialAuditTest.php --display-warnings` | 1 prueba, 14 aserciones, OK, PostgreSQL | Resumen de la salida observada en esta tabla; también incluida en la suite completa |
| `node resources/js/router/permissions.test.mjs` | OK; incluye rechazo de crear/editar para lectura sola | `frontend-permisos.txt` |
| `npm run build` | OK, 105 módulos, 16.31 s | Salida observada: `vite v7.3.2`, `105 modules transformed`, `built in 16.31s`; no se conserva el log completo |
| Cuatro scripts `micro-his/rbac/tests/{run,inmemory,authorization,authorization_errors}.php` | Todos pasan | `microhis.txt` |

PHP 8.2.12 / PHPUnit 11.5.55. El primer lanzamiento mediante Artisan produjo advertencias del entorno de copia; la repetición directa con PHPUnit terminó sin advertencias. Los tests Feature de medicamentos, pacientes y ocupación usan dobles de identidad y/o persistencia; no equivalen a un recorrido clínico con login real y PostgreSQL. `CindyContextTest`, `IdentityContextTest` y `SessionLifecycleTest` sí ejercitan JWT real con identidades sintéticas y CENTRAL SQLite en memoria.

La primera suite completa llegó a 222 casos pero terminó con 29 errores al no estar disponibles `composer.json` y `bootstrap/app.php` de la copia temporal durante la ejecución. No se determinó qué provocó esa indisponibilidad y no se atribuye al producto. Se restauró la misma composición desde el archivo base y los diffs propios de las ramas. La repetición completa finalizó en 4 min 19.821 s: **222 pruebas, 5487 aserciones, sin errores ni advertencias**. El resultado inicial no se usa como aprobación. Node emitió una advertencia de deprecación de `module.register()` durante el build; no impidió compilar.

Las 222 pruebas/5487 aserciones de la evidencia anterior pertenecen a la rama de #848; las 33 pruebas/3678 aserciones y el build anterior corresponden a #858. Son evidencia histórica, no ejecuciones independientes repetidas en esta revisión. La prueba PostgreSQL actual demuestra escritor y lector, pero no un endpoint HTTP de gobernanza con usuario autorizado.

## Matriz de cobertura real

Todos los endpoints siguientes se entienden bajo `/api/v1`. El hospital se obtiene del contexto validado; el JWT debe corresponder al hospital indicado. Los roles citados son ejemplos de la política actual, no una lista exhaustiva.

| Módulo / endpoint | Pantalla o acción | Permiso | Caso permitido / denegado | Hospital y evidencia | Límite |
|---|---|---|---|---|---|
| GET `/permissions` | `/rbac`, catálogo | `rbac.permisos.ver` | admin_hospital / medico | A autorizado; rol solo en B no concede en A; `IntegrationTest`, `CindyContextTest` | JWT real probado en SQLite; navegador pendiente |
| GET `/admin/users` | `/admin/users`, listar | `usuarios.ver` | admin_hospital / medico | Listado aislado; `HospitalUserAdministrationTest` | Navegador pendiente |
| POST y DELETE `/admin/users/{user}/roles…` | Asignar/revocar rol | `rbac.asignar` | admin_hospital con límites / medico; tampoco autoescalamiento | `AdminRoleManagementTest`, revocación de tokens | No confundir con editar el catálogo de permisos de un rol |
| POST `/admin/users/{user}/deactivate` | Desactivar | `usuarios.desactivar` | admin_hospital / medico | Hospital objetivo; `HospitalUserAdministrationTest` | Demostración manual pendiente |
| DELETE `/admin/sessions/{session}` | Revocar sesión ajena | `sesiones.revocar_ajenas` | admin_hospital / medico; sesión propia rechazada | Mismo hospital; `HospitalUserAdministrationTest` | No consta pantalla específica para toda operación |
| POST `/admin/tenants` | Crear hospital | `tenants.crear` | superadmin / admin_hospital | `AdminRoleManagementTest` | Sin pantalla dedicada acreditada |
| GET `/occupancy` | `/ocupacion` | `ocupacion.ver` | roles con permiso / rol sin permiso | `OccupancyEndpointTest`, `OccupancySecurityTest` | HTTP con dobles; recorrido de admisión/cama real pendiente |
| GET `/medications` y `/{medication}` | `/medicamentos`, consultar | `medicamentos.ver` | medico / desconocido | `MedicationCatalogApiTest`, `MedicationCatalogSecurityTest` | Identidad simulada; E2E pendiente |
| POST `/medications` | Registrar medicamento | `medicamentos.registrar` | admin_hospital / medico | Membresía de otro hospital no concede; `MedicationCatalogApiTest` | No atribuir concesión a superadmin por su nombre |
| PATCH `/medications/{medication}/validity` | Cambiar vigencia | `medicamentos.cambiar_vigencia` | admin_hospital / medico | `MedicationCatalogApiTest` | Pruebas de auditoría y fallo incluidas; navegador pendiente |
| GET `/patients/{code}` | API de consulta | `pacientes.ver` | medico / admin_hospital según matriz | `PatientApiTest` con PDO SQLite e identidad simulada | La pantalla `/patients` no consume esta API |
| Sin POST/PATCH de Pacientes registrados | `/patients/new`, `/patients/edit` | `pacientes.registrar`, `pacientes.editar` | recepcion / medico de solo lectura | `permissions.test.mjs`; `PatientPermissionsTest` prueba middleware aislado | Prototipos ficticios; no hay alta/edición persistida acreditada |
| Login/me/refresh/logout | `/login`, cierre y hospital activo | Autenticación/membresía; no permiso clínico adicional | Membresía vigente / inactiva, token válido / revocado | `IdentityContextTest`, `SessionLifecycleTest`, `CindyContextTest` | No hay selector de cambio de hospital en sesión acreditado; probar salida y nuevo login |
| Gobernanza: consulta/exportación | Sin ruta Laravel acreditada en esta copia | Según contrato de gobernanza | Pendiente prueba HTTP con autorizado/no autorizado | `RbacDenialAuditTest` valida lector PDO y filtro denied | Provider de gobernanza no aparece en `bootstrap/providers.php` |

Médicos, citas, laboratorio/órdenes/resultados, SOAP, signos vitales, admisión/camas, alertas, alergias, prescripciones, muestras y reportes tienen contratos/permisos o código micro-HIS; esto no acredita endpoints Laravel y pantallas integradas para todos ellos. El listado real debe regenerarse al incorporar cada módulo. Las pruebas que construyen `Request` y llaman middleware con una ruta ficticia no prueban que ese endpoint esté registrado.

## Corrección de pacientes

Los cambios locales encontrados asignan `pacientes.registrar` a `patients-new` y `pacientes.editar` a `patients-edit`, y ocultan enlaces/botones sin permiso. Se añadieron comprobaciones de comportamiento que impiden a un lector acceder por decisión de ruta a ambas acciones, verifican cada permiso por separado y rechazan un usuario ausente. El backend de consulta conserva `pacientes.ver`. No se añadieron endpoints clínicos de escritura ni se convirtió el prototipo en un módulo funcional.

## Acuerdo #679: confirmado, integración pendiente

Mar-03 eligió la opción a): `NULL`, `success`, `failure`, `denied`, `already_revoked`, `unknown`. Cualquier valor nuevo exige migración coordinada con prueba antes de habilitar el escritor.

Fuente: https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/pull/679#issuecomment-6329752822

La respuesta corresponde al 21 de octubre a las 18:34 de Guatemala (22 de octubre 00:34 UTC). #679 sigue OPEN en la consulta realizada. El acuerdo no equivale a merge, aprobación formal ni aplicación de la migración. RBAC usa `denied`, que ya está comprendido en el catálogo aceptado.

## Gobernanza y entorno de entrega

La activación de gobernanza es una dependencia compartida: no registrar un provider por conjetura ni duplicar un escritor para hacer pasar la demostración. Tras integrar su provider real y contrato de autenticación, ejecutar:

1. Iniciar sesión con usuario sin permiso para `/permissions` y producir 403 con `trace_id`.
2. Iniciar sesión con usuario autorizado para consultar auditoría, en el mismo hospital.
3. Consultar el endpoint real publicado por gobernanza con filtro `denied`; cotejar actor, hospital, recurso permission, criticidad security y trace_id. No inventar la URL del endpoint.
4. Repetir sin permiso de auditoría y desde otro hospital; no deben exponerse registros ajenos. Probar exportación si la asignación la exige.

No se recibió URL ni acceso de staging y no se realizó un recorrido de navegador en esta revisión. No se declara cumplimiento del punto 3 de la solicitud ni del despliegue. Para cerrarlos:

1. Registrar SHA desplegado que incluya ambas correcciones y los permisos de acción, versión PHP/Node/PostgreSQL, migraciones aplicadas, configuración de tenant/JWT y comando de build; no incluir secretos.
2. Preparar usuarios de prueba con roles opuestos en hospitales A/B y datos ficticios. No usar datos clínicos reales en capturas.
3. Login real en navegador; comprobar menú y URL directa para `/rbac`, `/ocupacion`, `/medicamentos`, `/admin/users` y `/patients`. Capturar permitido/denegado y respuesta API equivalente.
4. Un lector de pacientes no accede a `new`/`edit`; un rol habilitado puede abrir el prototipo, que sigue marcado como demo.
5. Cerrar sesión y entrar en B; comprobar hospital mostrado, permisos nuevos y ausencia de datos de A. Manipular solo `X-Tenant-ID` con token A debe ser rechazado.
6. Revocar un rol desde otra sesión: el backend debe rechazar el token anterior. Renovar o iniciar sesión y verificar que la navegación usa los permisos nuevos. La UI actual no consulta `/auth/me` en cada navegación: no prometer actualización visual instantánea sin renovación.
7. Logout: volver con historial/URL directa e intentar la API con el token anterior; exigir denegación. Registrar errores y resultados, sin tokens ni contraseñas.
8. Adjuntar capturas, respuestas saneadas, fecha/hora y resultado por caso; mantener pendiente todo lo no ejecutado.

## Trazabilidad semanal

Los README de semanas 1–17 contienen asignaciones históricas. No se aportó la planificación oficial completa como fuente independiente; su presencia no certifica aceptación académica. `trazabilidad-semanas.md` relaciona cada semana con el documento local, código/pruebas candidatos y lo que falta acreditar. Ninguna fila se certifica únicamente por existir una carpeta o por pasar la suite actual.
