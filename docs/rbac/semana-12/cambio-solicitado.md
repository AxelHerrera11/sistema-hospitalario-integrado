# Cambio solicitado — Análisis (Semana 12)

**Cambio propuesto:** Exigir confirmación adicional antes de asignar un rol **privilegiado**.

**Alcance de este entregable:** **Solo análisis**. En Semana 12 NO se implementa, NO se modifica el prototipo ni HTML/CSS/JS.

## Solicitud (formulación)
Al seleccionar un nuevo rol con **nivel ≥ 80** (ej. `admin_hospital` nivel 80, `superadmin` nivel 100), exigir una **segunda confirmación** adicional a la pantalla `#confirmar` existente, antes de aplicar la asignación de rol.

## Impacto funcional
- **Flujo actual:** `#roles` → validaciones → `#confirmar` (resumen) → `#roles-exito` o `#denegado` (app.js 257–288).
- **Propuesta analizada (no implementada):** Detectar si `ROLES[newRole].level >= 80`. Si sí, añadir paso intermedio `#confirmar-privilegiado` o ampliar `#confirmar` con advertencia de rol privilegiado + checkbox obligatorio "Confirmo asignación de rol privilegiado" antes de habilitar `Confirmar`.
- **Compatibilidad hacia atrás:** Camino feliz con nivel < 80 (enfermeria/medico/recepcion) **no cambia**.
- **Validaciones existentes:** Anti-escalamiento (`rol_supera_privilegio`, 268–274) y permiso `rbac.roles.asignar` (261–267) se mantienen.

## Seguridad
- **Fortaleza de control:** Doble confirmación para operaciones sensibles (principio de precaución). Reduce riesgo de error humano/escalamiento accidental.
- **No sustituye autorización:** No reemplaza validación server-side. Debe mantenerse validación de niveles/permiso también en backend.
- **Trazabilidad:** Si se implementara, debería registrarse evento/auditoría con contexto (segunda confirmación) — fuera de alcance ahora.

## UX
- **Claridad:** Banner de advertencia ("Rol con privilegios elevados") visible, texto claro.
- **Carga cognitiva mínima:** Solo se activa cuando aplica (nivel ≥ 80).
- **Prevención errores:** Checkbox obligatorio + deshabilitar botón hasta confirmar explícitamente.

## Accesibilidad
- **Mensajes anunciados:** Usar `aria-live` para advertencia si aparece dinámicamente.
- **Asociación correcta:** Checkbox con `<label>`, `aria-describedby` explicando riesgo.
- **Foco:** Gestionar foco al entrar en pantalla intermedia; navegación teclado operable.
- **Semántica:** Mantener estructura existente, no romper regiones nombradas.

## Pruebas de regresión (análisis)
- **Camino feliz (nivel < 80):** Sin cambios de flujo.
- **Rol privilegiado (nivel ≥80):** Nuevo paso, requiere confirmación adicional.
- **Anti-escalamiento:** Sigue vigente (admin1 nivel 80 no puede asignar superadmin 100).
- **Duplicado:** No afectado.
- **Denegaciones trazables:** No afectado (razones existentes).
- **Responsive/accesibilidad:** Verificar en breakpoints/movilidad si se añade pantalla.

## Decisión final
**Analizar únicamente (Semana 12).** Cambio viable, justificado, bajo riesgo, compatible con arquitectura demo. Requiere cambios mínimos en HTML/JS si se implementara (fuera de alcance). **No se implementa ni modifica el prototipo en esta semana**. Distinción clara: análisis vs implementación.

## Limitaciones
Análisis basado en prototipo existente (S11). No se generan capturas ni se ejecutan pruebas nuevas. No se modifica código.
