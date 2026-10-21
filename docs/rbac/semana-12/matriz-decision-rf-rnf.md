# Matriz decisión → RF/RNF → evidencia — Semana 12

Basada en auditoría S1–S11. No se inventan IDs RF/RNF; se citan referencias existentes o se marcan "Pendiente de formalizar". Rutas/líneas verificables cuando aplica.

## Matriz

| Decisión de diseño | Justificación técnica | RF/RNF (referencia) | Riesgo mitigado | Evidencia verificable | Estado |
|---|---|---|---|---|---|
| **Separación `rbac.roles.asignar` vs `rbac.permisos.asignar`** | Distinción dominio: membresías vs permisos de rol. | S1 / S5 (`permisos.md:10,47`, `contrato-api.md:19`) / S11. **Pendiente formalizar**. | Mezcla alcances/privilegios. | `app.js:18,19,52,53`; README S11:38–41; `escenarios.md` S11 C1. | CUMPLE |
| **Anti-escalamiento por nivel (AssignerPrivilegeRule)** | Nivel nuevo ≤ nivel actor; motivo `rol_supera_privilegio`. | S3: `AssignerPrivilegeRule.php`. **Pendiente formalizar**. | Escalación vertical. | `app.js:268–274`; `AssignerPrivilegeRule.php:11,19`; Escenario S11 D. | CUMPLE |
| **Trazas `trace-` + 12 hex** | Basado en `random_bytes(6)` (S3). | S3. **Pendiente formalizar**. | Falta correlación auditoría. | `app.js:75–80,194–197,170–175,206–227`. | CUMPLE |
| **Motivos `sin_permiso_de_rol` / `rol_supera_privilegio`** | Códigos consistentes con dominio. | S3/S1/S11. **Pendiente formalizar**. | Ambigüedad auditoría. | `app.js:263,269,294,306`; `AssignerPrivilegeRule.php:19`. | CUMPLE |
| **Mensaje 403 genérico (sin revelar permiso)** | Principio mínimo revelación (S1). | S1:16. **Pendiente formalizar**. | Enumeración permisos. | HTML `#denegado`; `app.js:308`; S11 B. | CUMPLE |
| **Ocultamiento según `rbac.denegaciones.ver`** | Detalle técnico/enlace y bitácora restringidos. | S1/S5/S11. **Pendiente formalizar**. | Exposición indebida. | `app.js:176–179,207–210`; README S11:62; S11 B. | CUMPLE |
| **Requiere `rbac.autorizar`** | Separación solicitud decisión vs permiso recurso. | S5/S11. **Pendiente formalizar**. | Bypass autorización. | `app.js:293–299`. | CUMPLE |
| **Validación UI (deshabilitar + hint)** | Prevención temprana; no sustituye backend. | UX S8–S10. **Pendiente formalizar**. | Intentos innecesarios. | `app.js:127–136`; S11 A,D. | CUMPLE |
| **Anti-duplicado UI** | Evita reasignación mismo rol (409 conceptual). | S1/S8. **Pendiente formalizar**. | Redundancia/confusión. | `app.js:275–281`; S11 A,D. | CUMPLE |
| **Accesibilidad/semántica** | aria-labelledby, aria-live, dl/dt/dd, role=region, labels, skip link, foco. | RNF S8–S11. **Pendiente formalizar**. | Barreras uso. | HTML S11; validación S11. | CUMPLE |
| **Movilidad/responsive** | Breakpoints, tarjetas ≤768, sin scroll horizontal. | S10/S11. **Pendiente formalizar**. | Mala UX móvil. | `styles.css`; validación CDP S11. | CUMPLE |

**Nota:** Ítems marcados "Pendiente de formalizar" ID RF/RNF. Usar referencias citadas para formalización. No se afirma ejecución de pruebas nuevas.
