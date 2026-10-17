# Recorrido navegable — Semana 12

Basado exclusivamente en el prototipo funcional existente de **Semana 11**: `docs/rbac/semana-11/prototipo.html` + `app.js`. No se asumen funcionalidades no implementadas.

## Requisitos del recorrido
Demostrar: (1) asignación autorizada, (2) autorización permitida, (3) denegación no autorizada, (4) registro/evidencia trazable, (5) tratamiento de errores y protección de información sensible.

## Preparación
- Abrir `docs/rbac/semana-11/prototipo.html` directamente con navegador moderno (Chrome/Edge). Funciona `file://`.
- Verificar sesiones disponibles: `admin1` (admin_hospital), `med1` (medico), `recep1` (recepcion), `nuevo1` (sin rol).

## Paso 1: Asignación autorizada de rol
- **Pantalla:** `#inicio` → navegar a `#roles`
- **Sesión:** `admin1` (posee `rbac.roles.asignar`)
- **Acción:** Seleccionar `targetUser = nuevo1`, `newRole = enfermeria` (nivel 40 < 80). Observar `currentRole`, `currentPerms`, `newPerms`.
- **Resultado esperado:** `reqHint` vacío; `btnAssign` **habilitado**.
- **Continuar:** Pulsar `Asignar...` → `#confirmar`. Revisar resumen (Usuario, De→A, Hospital, Actor). Pulsar `Confirmar`.
- **Resultado esperado:** `#roles-exito` (201 simulado). Texto "Se asignó el rol Enfermeria a Usuario nuevo...". Rol de `nuevo1` pasa a `enfermeria` en memoria.
- **Verificar trazabilidad:** Ir a `#bitacora`. Debe aparecer evento `rol_asignado` con actor, detalle "Usuario nuevo (sin rol): Sin rol → Enfermeria".
- **Evidencia:** capturas `#roles`, `#confirmar`, `#roles-exito`, `#bitacora`.

## Paso 2: Autorización de operación permitida
- **Pantalla:** `#operacion`
- **Sesión:** `med1` (medico). Tiene `rbac.autorizar` + `prescripciones.crear`.
- **Acción:** Seleccionar recurso `Crear prescripción` (`POST /api/v1/prescripciones`, perm `prescripciones.crear`).
- **Pulsar:** `Solicitar autorización`.
- **Resultado esperado:** `#permitido`. Muestra "Permitido", usuario/rol/ruta/permiso.
- **Evidencia:** captura `#permitido`.

## Paso 3: Denegación de operación no autorizada (trazable)
- **Pantalla:** `#operacion`
- **Sesión:** Cambiar a `recep1` (recepcion). Carece `muestras.etiquetar` y carece `rbac.denegaciones.ver`.
- **Acción:** Seleccionar recurso `Etiquetar muestra` (`POST /api/v1/muestras/{id}/etiquetas`, perm `muestras.etiquetar`).
- **Pulsar:** `Solicitar autorización`.
- **Resultado esperado:** `#denegado`. **Mensaje genérico** "No tiene autorización para realizar esta acción." (no revela `muestras.etiquetar`). `dPath`, `dRole`, `dPerm = muestras.etiquetar`, `dReason = sin_permiso_de_rol`, `dTrace = trace-[0-9a-f]{12}`. **`technicalDetail` oculto**, **`auditLink` oculto** (porque `recep1` carece `rbac.denegaciones.ver`).
- **Verificar restricción bitácora:** Navegar a `#bitacora`. Debe mostrar "No tiene autorización para consultar esta bitácora." (protegido).
- **Evidencia:** captura `#denegado` con sesión `recep1` (detalle técnico oculto).

## Paso 4: Verificación de bitácora con rol autorizado
- **Pantalla:** `#bitacora`
- **Sesión:** Cambiar a `admin1` (admin_hospital) — tiene `rbac.denegaciones.ver`.
- **Resultado esperado:** Bitácora muestra evento `acceso_denegado` con mismo `trace_id` del paso 3, detalle `muestras.etiquetar:sin_permiso_de_rol`, actor `Recepcionista Demo`, hospital `H1`. También puede incluir `rol_asignado` del paso 1.
- **Evidencia:** captura `#bitacora` con `admin1` mostrando traza.

## Paso 5: Errores, prevención y protección
- **A) Sin permiso para asignar (UI):** Sesión `med1` → `#roles`. Intentar asignar: `btnAssign` **deshabilitado** + `reqHint` indica "falta el permiso rbac.roles.asignar..." (validación real).
- **B) Anti-escalamiento:** Sesión `admin1` (nivel 80) intenta asignar `superadmin` (nivel 100). Tras confirmar, debe resultar `#denegado` con `dReason = rol_supera_privilegio`, `trace-` 12 hex. Motivo generado por `AssignerPrivilegeRule`.
- **C) Duplicado:** `nuevo1` ya tiene `enfermeria` (tras paso 1). En `#roles`, seleccionar `nuevo1` + `enfermeria` → `reqHint` indica rol ya vigente; botón deshabilitado (evita navegar a confirmar). Si se fuerza flujo, lógica impide cambio (409 conceptual).
- **D) Separación de permisos:** En `#operacion` con `med1`, seleccionar `Actualizar permisos de un rol` (usa `rbac.permisos.asignar`). Autorizar → `#denegado` con `dPerm = rbac.permisos.asignar` (distinto de `rbac.roles.asignar`). Verificado en S11.

**Notas:** Todo lo anterior existe y fue verificado (20/20 PASS). Simulaciones explícitas: sin HTTP/backend, memoria. Protección: ocultamiento técnico/bitácora según `rbac.denegaciones.ver` (real). Errores prevenidos en UI.
