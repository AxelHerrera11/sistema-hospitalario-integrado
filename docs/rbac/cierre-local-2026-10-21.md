# Continuación del cierre RBAC — validación local

## Cambios publicados

- #848: corrige resultado, criticidad y recurso de las denegaciones.
- #858, `6be4848c`: permisos por acción de Pacientes y revalidación de `/auth/me` antes de entrar a cada pantalla protegida. Revocar roles o sesión lleva al login; un fallo de red bloquea el acceso y retira la identidad de la navegación. Cambiar el hospital seleccionado limpia la sesión local anterior.
- #917, `8da19f17`: registra el provider existente de gobernanza después de RBAC/Tenants. Activa `GET /api/v1/audit-events` con JWT, hospital y `auditoria.ver`. No amplía concesiones ni añade escritores.

Estado al 23 de octubre de 2026: #848, #858 y #917 están integrados en el repositorio (merge en develop y promovidos a main). Este texto describe el cierre local del 21 de octubre, cuando seguían en revisión; la historia no se reescribe, el estado se actualiza. Las ramas utilizan `feature/asii-02-…-mar-03`, base develop y menos de 400 líneas por PR.

## Composición y entorno probado

Base `a3925260`, más el diff propio de #848, el código de #858 publicado en `6be4848c` y el cableado/pruebas de gobernanza de `8da19f17`. La copia de prueba se encuentra en `.review-validation/rbac-entrega/app` dentro del workspace Hospital final. No se trata de un commit fusionado. Las modificaciones documentales posteriores no cambian el código probado.

PHP 8.2.12, PHPUnit 11.5.55, Vite 7.3.2. Build: 105 módulos, OK. Pruebas de permisos y revalidación de navegación: OK. Pruebas conjuntas de Tenants, gobernanza HTTP y JWT: **40 pruebas, 325 aserciones, OK**. La prueba independiente de gobernanza: **5 pruebas, 33 aserciones, OK**.

**Suite completa final: 230 pruebas, 5624 aserciones, OK** (`evidencias-cierre/phpunit-cierre-local.txt`). La primera ejecución completa falló en el límite temporal de la prueba existente de dos escritores PostgreSQL; la repetición aislada pasó (1 prueba, 9 aserciones) y la segunda ejecución completa pasó sin modificar producción ni esa prueba. No se determinó con certeza la causa del primer fallo; se conserva como incidencia de reproducibilidad, no se oculta ni se declara corregida una carrera del producto. La composición y huellas del código se conservan en `evidencias-cierre/composicion-cierre-local.json`.

Para navegador/HTTP se sirvió la aplicación en `127.0.0.1:8765` con CENTRAL SQLite persistente y usuarios ficticios, usando el mismo JWT, middleware, policy y writers de la aplicación; no se sustituyeron por dobles. La suite de integridad usa las bases PostgreSQL de pruebas configuradas en phpunit.xml. SQLite local no demuestra el despliegue de la topología federada de hospitales.

## Recorridos completados

| Caso | Resultado comprobado |
|---|---|
| Login lector en hospital A | Menú sin Usuarios/Ocupación; hospital activo visible |
| URL directa `/patients/new` y `/patients/edit` sin permiso de escritura | Redirección a Inicio con aviso, sin formulario |
| Misma operación, catálogo de permisos, con ambos roles | Superadmin: catálogo de la API visible y HTTP 200; lector: ruta bloqueada y API 403 |
| Revocar superadmin mientras el navegador conserva la sesión | Al navegar a Usuarios, `/auth/me` rechaza la sesión y vuelve al login |
| Mismo usuario en hospital B, con rol medico | Hospital Norte visible; permisos de A no se conservan |
| Logout y posterior URL `/rbac` | Vuelve al login; el token anterior recibe 401 por API |
| Denegación real y consulta por usuario autorizado | `GET /audit-events?resultado=denied&correlacion=…`: 200, exactamente el evento denegado con su correlación |
| Hospital ajeno mediante cabecera o query | HTTP 403 |

Evidencia: `evidencias-cierre/http-local.txt`, `jwt-integracion.txt` y las capturas `lector-denegado.png`, `autorizado-catalogo.png`, `rol-revocado.png`, `hospital-b.png`. Los identificadores mostrados pertenecen a fixtures ficticias; no contienen tokens ni contraseñas.

## Cobertura y límites actualizados

La consulta HTTP de gobernanza ya no es una dependencia pendiente de código: #917 está integrado y `GET /api/v1/audit-events` aparece registrado en el provider de gobernanza de develop. Lo que sigue pendiente es la validación contra el entorno real, distinta de la integración en repositorio. #679 está fusionado: su catálogo de valores de resultado y su migración forman parte del árbol de develop; el despliegue en entorno sigue pendiente.

En develop a3925260 también se registran POST `/traslados`, POST `/altas` y GET `/estancias/{estancia}/internacion`. Sus permisos `traslados.registrar`, `altas.registrar` y `traslados.ver` siguen en `config/rbac.php.pending`: no se activaron sin el acuerdo del módulo. La política los deniega aunque las rutas existan. Ver `TrasladosAltasApiTest`; no confundir registro de endpoint con flujo clínico habilitado.

La interfaz de Pacientes sigue siendo prototipo de su módulo; esta entrega protege sus acciones y consulta la identidad real, pero no implementa altas/ediciones clínicas ni acredita su persistencia. Ese trabajo corresponde a la asignación de Pacientes, no a una concesión de RBAC.

Se contrastó la matriz semanal con la tabla **Plan semanal ASII** del README raíz en a3925260. La referencia `docs/weekly-plan.md` mencionada por ese README no existe en esta versión. Las semanas 6/12 exigen defensa/evaluación humana; las semanas 16/17 exigen entorno de entrega. No se sustituyen esas evidencias por pruebas locales.

## Para el entorno de entrega

Integrar los PR revisados está resuelto del lado del repositorio. El pendiente real es el despliegue: aplicar las migraciones CENTRAL/HOSPITAL correspondientes, reconstruir configuración y frontend, reiniciar workers y repetir los casos anteriores con usuarios de prueba del entorno. Registrar SHA desplegado, fecha, versiones, respuestas saneadas y capturas. Comprobar que el login, selección de hospital, refresh/logout y lectura de auditoría usan los servicios y datos del entorno.

Falta URL y acceso al entorno de entrega: la integración en el repositorio no equivale a un despliegue y no se certifica el entorno de entrega ni las 17 semanas. La evidencia de navegador y HTTP local sí está completada.
