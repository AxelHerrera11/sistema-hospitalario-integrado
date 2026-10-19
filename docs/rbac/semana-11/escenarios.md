# Escenarios — Prototipo RBAC, Semana 11 (ASII-02)

Los flujos son simulaciones locales (`file://`), sin backend. Usuarios/hospitales/eventos iniciales son ficticios. **Resultado esperado** describe el comportamiento diseñado; **observado** registra las pruebas ejecutadas en este cierre.

## A. Camino feliz — asignar rol

**Precondiciones:** sesión `admin1` (`admin_hospital`, H1) posee `rbac.roles.asignar`, permiso que protege asignar una membresía usuario-rol; `nuevo1` no tiene rol; se asigna `enfermeria` (nivel 40, inferior a 80).

**Pasos:** `#inicio` → `#roles`; escoger `nuevo1` y `enfermeria`; revisar rol/permisos; `Asignar…` → `#confirmar`; `Confirmar` → `#roles-exito`; visitar `#bitacora`.

**Esperado:** resultado 201 simulado y evento `rol_asignado` en bitácora. `rbac.roles.asignar` protege la ruta conceptual de membresía `POST /api/v1/rbac/users/{id}/roles`; no se realiza petición HTTP.

**Observado:** PASS en Chromium headless mediante interacción DOM: navegación, confirmación, vista 201, cambio en memoria y evento nuevo de asignación en bitácora. No hubo petición HTTP.

## B. Error crítico — denegar operación protegida

**Precondiciones:** sesión `recep1` (`recepcion`, H1); permiso `muestras.etiquetar` ausente de la matriz demo.

**Pasos:** elegir `recep1`; ir a `#operacion`; elegir `Etiquetar muestra` (ruta ilustrativa, no verificada como endpoint productivo); pulsar `Solicitar autorización`; revisar `#denegado`; verificar que no se muestra el detalle técnico ni el enlace a bitácora. Intentar `#bitacora` con este rol y comprobar que no se revelan eventos.

**Esperado:** 403 `RBAC_DENIED`; mensaje principal genérico sin permiso faltante; `trace_id` con forma `trace-` + 12 hex. Como `recepcion` no tiene `rbac.denegaciones.ver`, el detalle técnico y la bitácora no se muestran a ese actor. `admin_hospital` sí puede ver el detalle demostrativo y la muestra de bitácora.

**Observado:** PASS en Chromium headless: mensaje genérico sin `muestras.etiquetar`, detalle y enlace ocultos a `recep1`, `trace_id` con 12 hex, evento de auditoría con la misma traza visible al cambiar a `admin1`, y bitácora inaccesible a `recep1`. Sin errores de página capturados.

## C. Permitir operación

**Precondiciones:** sesión `med1` (`medico`) y recurso `Crear prescripción`, permiso `prescripciones.crear` presente en matriz demo.

**Pasos:** seleccionar la sesión y recurso indicados; pulsar `Solicitar autorización`.

**Esperado:** `#permitido` muestra permitido y recuerda revalidar en servidor. No se realiza acceso al backend.

**Observado:** PASS en Chromium headless: `med1` recibió la pantalla permitido para `prescripciones.crear`.

### C1. Permisos de rol — operación diferente

**Precondiciones:** sesión `med1` puede solicitar autorización, pero no posee `rbac.permisos.asignar`.

**Pasos:** en `#operacion`, elegir `Actualizar permisos de un rol` (`POST /api/v1/roles/{role}/permissions`) y solicitar autorización.

**Esperado:** 403 genérico; el detalle de soporte identifica `rbac.permisos.asignar`, no `rbac.roles.asignar`.

**Observado:** PASS en Chromium headless: `med1` fue denegado en el recurso `POST /api/v1/roles/{role}/permissions`; el detalle técnico identificó `rbac.permisos.asignar`, distinto de `rbac.roles.asignar`.

## D. Prevenir escalamiento y asignación no permitida

**Precondiciones:** `admin1` nivel 80 intenta asignar `superadmin` nivel 100. Como control adicional, `med1` intenta usar la pantalla de asignación sin `rbac.roles.asignar`.

**Pasos/resultados esperados:** tras confirmar escalamiento, 403 con motivo `rol_supera_privilegio` (código de `AssignerPrivilegeRule` de Semana 3) y traza; para `med1`, botón deshabilitado y requisito mostrado junto al control. Un rol duplicado se previene en la UI; el 409 es respuesta contractual del flujo diseñado (semanas 1/8), no un resultado HTTP comprobado aquí.

**Observado:** PASS en Chromium headless: `admin1` fue denegado al intentar nivel 100; `med1` tuvo botón deshabilitado; duplicado se bloqueó en UI. No se probó respuesta HTTP 409 contra backend.

## E. Responsive y teclado

**Precondiciones:** prototipo abierto en navegador Chromium.

**Verificación esperada:** flujo accesible mediante enlaces/controles nativos, foco visible, saltar al contenido; anchos 320–1440 sin scroll horizontal global; bitácora en tarjetas hasta 768.

**Observado:** PASS con Chrome DevTools Protocol: Tab recorrió `targetUser` → `newRole` → `btnAssign` → `Cancelar`. No se ejecutó lector de pantalla. Responsive probado con CDP en 320, 390, 430, 431, 768, 769 y 1440 px: `scrollWidth == innerWidth` en todos; bitácora cambia a tarjetas hasta 768 px. Contrastes medidos: texto general 17.06:1, estado denegado 9.16:1, permitido 8.65:1, banner 8.75:1, navegación activa 8.41:1.

## F. Aislamiento entre hospitales (simulado)

**Precondiciones:** dos hospitales simulados `H1` y `H2`; cada usuario de `USERS` pertenece a un hospital y `state.hospital` sigue a la sesión simulada.

**Pasos:** en `#roles` con sesión `admin1` (H1), revisar que `targetUser` solo lista usuarios de H1; cambiar la sesión a `admin2` y confirmar que `targetUser` solo lista usuarios de H2; intentar asignar de H1 a un usuario de H2; en `#bitacora`, comprobar que cada hospital muestra solo sus eventos.

**Esperado:** el selector de destino se filtra por el hospital de la sesión; una asignación de H1 a un usuario de H2 se rechaza con motivo `usuario_de_otro_hospital` sin modificar el rol; la bitácora solo muestra eventos del hospital de la sesión, conservando `trace_id`; permisos y anti-escalamiento no cambian. No es seguridad real de producción: son datos simulados.

**Observado:** PASS con 7 comprobaciones ejecutables (`pruebas-aislamiento.mjs`, Node + `vm`) que ejercitan las funciones reales de `app.js`: visibilidad H1/H2, filtrado del selector, asignación válida, rechazo H1→H2, rol intacto, auditoría aislada y conservación de permisos/anti-escalamiento (7/7, 0 fallos). Las pruebas se ejecutan sobre el prototipo integrado de #149; el archivo de pruebas vive en #151 y no duplica `app.js`.
