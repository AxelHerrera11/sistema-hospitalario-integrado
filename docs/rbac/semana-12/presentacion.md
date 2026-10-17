# Presentación breve — Semana 12 (RBAC)

*Objetivo:* Defender las decisiones de componentes, UX, accesibilidad y movilidad del flujo «asignación de rol, autorización de una operación y denegación trazable», demostrando cómo trata roles, estados, errores y riesgos.

## Diapositiva 1 — Introducción y objetivo
- **Módulo:** ASII-02 — RBAC: roles, permisos y protección de rutas
- **Semana 12:** Segunda evaluación parcial
- **Enfoque:** Demostrar con prototipo navegable (Semana 11) el flujo A) asignación, B) autorización permitida, C) denegación trazable.
- **Alcance:** Documentación/defensa (no implementación del cambio solicitado).

## Diapositiva 2 — Decisiones de componentes
- **Separación de permisos:** `rbac.roles.asignar` protege **membresías** `POST /api/v1/rbac/users/{id}/roles` (S1, S8–S10). `rbac.permisos.asignar` protege **permisos de rol** `POST /api/v1/roles/{role}/permissions` (S11).
- **Jerarquía y anti-escalamiento:** Niveles `AssignerPrivilegeRule` (7 roles, S3). Motivo `rol_supera_privilegio` si nuevo rol supera nivel del actor.
- **Trazabilidad:** `trace-` + 12 hex (`random_bytes(6)`), S3. Razones `sin_permiso_de_rol` / `rol_supera_privilegio`. Evento `acceso_denegado` con `permiso:razón`.
- **Principio mínimo privilegio:** Mensaje 403 **genérico** (S1); detalle técnico/bitácora solo para actores con `rbac.denegaciones.ver`.

## Diapositiva 3 — UX y movilidad
- **Navegación por hash:** `#inicio,#roles,#confirmar,#roles-exito,#operacion,#permitido,#denegado,#bitacora` (mapa S11).
- **Confirmación explícita:** Pantalla `#confirmar` antes de aplicar cambio.
- **Estados claros:** Botón deshabilitado + `reqHint`; bloqueo duplicado; estados "Permitido/Denegado" con íconos.
- **Responsive:** Breakpoints 320–430/431–768/769–1024/>1024 (S10). Bitácora pasa a tarjetas ≤768. Validado 320/390/430/431/768/769/1440 (S11).
- **Mensajes genéricos al usuario:** Sin revelar permiso faltante a actores no autorizados.

## Diapositiva 4 — Accesibilidad
- **Regiones nombradas:** 8 secciones con `aria-labelledby` → h1.
- **Anuncios:** `sessionInfo` con `aria-live="polite"`.
- **Semántica:** Rol actual en `<dl><dt><dd id="currentRole">`.
- **Región dinámica:** `auditWrap` con `role="region"` + `aria-label`.
- **Navegación teclado:** Selectores etiquetados (`label[for]`), skip link, foco gestionado, Tab validado.
- **Contrastes:** Verificados (S11). No auditado con lector de pantalla (limitado).

## Diapositiva 5 — Seguridad, trazabilidad y protección
- **Separación clara:** `rbac.roles.asignar` (membresías) vs `rbac.permisos.asignar` (permisos rol). `rbac.autorizar` requerido para decidir operación.
- **Ocultamiento por permiso:** `technicalDetail` y `auditLink` ocultos sin `rbac.denegaciones.ver`. `renderAudit()` restringe bitácora.
- **Trazas auditables:** `trace-` 12 hex, motivo + permiso en detalle de auditoría (solo visible a autorizados).
- **Prevención:** Anti-escalamiento, duplicado UI, validación permisos antes de acción.
- **Principio:** Autorización **server-side** obligatoria en producción; prototipo es demo.

## Diapositiva 6 — Demostración del flujo (basado en prototipo real)
Casos validados (S11, 20/20 PASS):
1. **Asignación autorizada** (admin1 → nuevo1 enfermeria): habilitado → confirmar → 201 simulado + `rol_asignado`.
2. **Autorización permitida** (med1 → prescripciones.crear): `#permitido`.
3. **Denegación trazable** (recep1 → etiquetar muestra): 403 genérico, `trace-` 12 hex, detalle oculto a recep1; visible a admin1. Motivo `sin_permiso_de_rol`.
4. **Prevención**: med1 sin `rbac.roles.asignar` → botón deshabilitado; admin1 intenta superadmin → `rol_supera_privilegio`.

## Diapositiva 7 — Conclusiones y límites
- **Funcionalidad real:** Validaciones, estados, ocultamiento, trazabilidad implementados y verificados.
- **Simulaciones explícitas:** Sin backend, memoria, datos ficticios.
- **Producción:** Requiere autorización server-side, persistencia, auditoría real.
- **Lista para defensa:** Flujo completo cubierto por prototipo de S11.
- **S12:** Documentación/defensa; cambio solicitado solo analizado (no implementado).
