# Semana 11 — Prototipo navegable de RBAC (ASII-02)

**Autora:** María de los Ángeles López Fajardo (Mar-03), `mlopezf16@miumg.edu.gt`.<br>
**Issue relacionado:** #4 (`Refs #4`).<br>
**Rama de trabajo:** `feature/asii-02-semana-11-rbac-mar-03`, base `develop`.

## Objetivo y alcance

Prototipo navegable autónomo para escritorio y móvil que recorre (A) asignación de rol, (B) autorización de operación protegida y (C) denegación con trazabilidad; incluye bitácora demostrativa, mapa, escenarios y evidencias.

No modifica Laravel, base de datos, autenticación, API productiva ni ramas anteriores. No hay backend conectado: decisiones, usuarios, eventos y fechas son datos de demostración, no autorización ni auditoría real.

## Cómo abrir y probar

Abrir `prototipo.html` directamente con Chrome, Edge o navegador moderno; funciona mediante `file://`, sin build, servidor o dependencias.

1. **Camino feliz de asignación:** sesión `admin1` → `Asignar rol` → usuario `nuevo1` → rol `enfermeria` → `Asignar…` → `Confirmar`. Resultado simulado: 201 y evento `rol_asignado` en la bitácora.
2. **Autorización permitida:** sesión `med1` → `Operación protegida` → `Crear prescripción` → `Solicitar autorización`. Resultado simulado: permitido.
3. **Denegación:** sesión `recep1` → `Operación protegida` → `Etiquetar muestra` → `Solicitar autorización`. Resultado simulado: 403 `RBAC_DENIED`, mensaje genérico y `trace_id`. Como `recep1` no tiene `rbac.denegaciones.ver`, la interfaz también oculta el detalle técnico y el enlace a bitácora. Solo la vista de soporte autorizada lo muestra en esta simulación.
4. Para asignación no permitida, sesión `med1` → `Asignar rol`: el control se deshabilita y muestra el requisito faltante. `admin1` no puede asignar `superadmin` por la regla anti-escalamiento; se presenta una denegación trazable.
5. **Permisos de rol:** sesión `med1` → `Operación protegida` → `Actualizar permisos de un rol`. El recurso usa `rbac.permisos.asignar`, distinto del permiso de membresía `rbac.roles.asignar`; `med1` recibe una denegación simulada.

## Navegación

| Pantalla | Hash | Resultado |
|---|---|---|
| Inicio | `#inicio` | Entrada a flujos |
| Asignar rol | `#roles` → `#confirmar` → `#roles-exito` | Selección, permisos del rol, confirmación y 201 simulado |
| Operación protegida | `#operacion` → `#permitido` / `#denegado` | Decisión simulada por la matriz |
| Bitácora | `#bitacora` | Eventos de auditoría demostrativos, separados de mensajes UI |

Ver `mapa-navegacion.mmd` y `escenarios.md`.

## Modelo de demostración y fuentes

La matriz conserva cinco roles y el vocabulario `modulo.accion` de `docs/rbac/semana-05/permisos.md` en `feature/asii-02-semana-05-rbac-mar-03`; suma el permiso separado `rbac.permisos.asignar` para un total de 13. Se mantienen los códigos `superadmin`, `admin_hospital`, `medico`, `enfermeria`, `recepcion`; los niveles se contrastan con `micro-his/rbac/src/Domain/AssignerPrivilegeRule.php` de `feature/asii-02-semana-03-rbac-mar-03`.

En la matriz demo, `admin_hospital` tiene ambas capacidades: asignar membresías (`rbac.roles.asignar`, como en Semana 1/UX 8–10) y administrar permisos-rol (`rbac.permisos.asignar`, conservando bajo el token nuevo el grant de la matriz Semana 5).

Rutas: `rbac.roles.asignar` protege la asignación de membresía `POST /api/v1/rbac/users/{id}/roles`; `rbac.permisos.asignar` protege `POST /api/v1/roles/{role}/permissions` para asignar/actualizar permisos de un rol. La consulta de denegaciones es `GET /api/v1/denials` (contrato propuesto de Semana 5); prescripciones/muestras son rutas ilustrativas del prototipo, no endpoints productivos verificados.

Reglas de demostración: la operación de autorización requiere `rbac.autorizar` y el permiso de la ruta protegida. La pantalla de asignación de membresía valida `rbac.roles.asignar`, conforme a la decisión confirmada.

## Decisión confirmada de permisos de asignación

La responsable confirma dos capacidades distintas:

| Fuente (rama y líneas) | Token/operación especificados |
|---|---|
| `docs/rbac/semana-01/README.md:21`, `fuentes/02-actividad.mmd:17`, `fuentes/03-secuencia.mmd:18,25` en `develop`; UX 8 (`README.md:12,31`) y UX 10 (`breakpoints.md:16`) | `rbac.roles.asignar` protege asignar/revocar la membresía usuario-rol en `POST /api/v1/rbac/users/{id}/roles`. |
| Decisión confirmada para Semana 11 | `rbac.permisos.asignar` protege asignar/actualizar permisos de un rol en `POST /api/v1/roles/{role}/permissions`. Este token separa esa acción de la membresía. |

La decisión confirmada separa el uso que Semana 5 había combinado bajo `rbac.roles.asignar`: el uso de membresías de Semana 1/UX 8–10 queda con `rbac.roles.asignar`; la operación de permisos-rol que Semana 5 vinculó a ese nombre (`permisos.md:10,47`, `contrato-api.md:19`) queda con `rbac.permisos.asignar`.

**Compatibilidad con Semana 3:** la implementación existente de `AssignRole` usa `rbac.asignar` (`micro-his/rbac/src/Application/AssignRole.php:25`, prueba `tests/run.php:19,41`); Semana 4 lo conserva (`responsabilidades.md:9`). Ese literal no se cambia desde Semana 11; la modificación de Semana 3 corresponde exclusivamente a su propia rama.

## Otras diferencias de alcance

- Semana 1/2 en `develop` muestra un catálogo de roles de ejemplo más amplio (incluye `TecnicoLab` y `Bioquimico`); Semana 3 implementa niveles para siete roles; la matriz de Semana 5 y UX 8–10 coinciden en los cinco roles usados aquí. Se conserva esa matriz demo y los niveles comunes de Semana 3.
- Semana 1 define la ruta de membresía usuario-rol; Semana 5 define una ruta diferente para permisos-rol. El prototipo de esta semana trata la primera.
- El ejemplo de Semana 2 documenta IDs secuenciales en su demo; `micro-his/rbac/src/Domain/Authorizer.php` de Semana 3 genera `trace-` más 12 dígitos hexadecimales aleatorios (`random_bytes(6)`). El prototipo sigue esa forma en denegaciones interactivas. El evento inicial `trace-9f8e7d6c5b4a` es un registro de muestra etiquetado como demo.
- Semana 1 pide mensaje 403 sin revelar el permiso faltante. El mensaje principal aquí es genérico. El permiso/motivo y el enlace de bitácora se ocultan a roles sin `rbac.denegaciones.ver`; son controles demostrativos de UI, no seguridad real (un backend debe autorizar el acceso).

## Evidencias

Inventario completo y dimensiones en `evidencias/README.md`. Capturas principales: camino feliz y denegación a 1440 px y 390 px; se agregan formulario y bitácora en desktop.

## Accesibilidad y responsive

HTML semántico, enlaces y controles nativos operables por teclado, etiquetas asociadas, enlace para saltar al contenido, foco visible, estados con icono y texto, región `aria-live`, botones/controles de al menos 44 px. La bitácora pasa a tarjetas hasta 768 px; se verificó desbordamiento horizontal con viewport de 320, 390, 430, 431, 768, 769 y 1440 px. Contrastes se midieron en Chromium; no se auditó con lector de pantalla.

## Resultados comprobados y límites

Los resultados reales de las pruebas y capturas están registrados en `escenarios.md` y `evidencias/README.md`. El prototipo no valida tenant contra datos reales, no autentica, no persiste membresías ni registros y no sustituye la autorización server-side. No se probó con lector de pantalla ni contra un endpoint Laravel.
