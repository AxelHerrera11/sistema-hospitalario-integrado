# Wireframe 03: Admin - Alta Usuario (Modal)
## Estado: VACÍO (Modal Abierto)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ➕ Nuevo Usuario                                    [×] Cerrar  (ESC)      │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Email *                                                         │   │
│  │  [____________________________________________________________]   │   │
│  │  💡 Use su email @hospital.com                                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Nombre Completo *                                               │   │
│  │  [____________________________________________________________]   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Contraseña *                                                    │   │
│  │  [____________________________________________________________] 👁️ │   │
│  │  Fortaleza: ⚪⚪⚪⚪⚪  (Mín 12, mayús, minús, num, símbolo)         │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Roles * (Mín 1, Máx 5)                               ▼ [☑] MEDICO │   │
│  │  ─────────────────────────────────────────────────────────────────  │   │
│  │  [☐] ENFERMERO      [☐] TENANT_ADMIN   [☐] AUDITOR                │   │
│  │  [☐] PACIENTE                                                      │   │
│  │                                                                     │   │
│  │  ℹ️ TENANT_ADMIN y AUDITOR requieren MFA obligatorio              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  [☑] Forzar MFA al primer login (recomendado)                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]                              [ CREAR USUARIO ]  ← .btn-primary│
└─────────────────────────────────────────────────────────────────────────────┘
Validación Tiempo Real (onBlur / onChange):
- Email: Formato + dominio @hospital.com + unicidad (debounced 300ms)
- Nombre: 2-100 chars, solo letras/espacios
- Contraseña: Medidor fortaleza tiempo real (5 checks)
- Roles: Min 1, máx 5, checkboxes con validación MFA
- Botón: Disabled hasta campos requeridos válidos
```

## Estado: VALIDANDO TIEMPO REAL (Debounced)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ➕ Nuevo Usuario                                    [×] Cerrar              │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Email *                                                         │   │
│  │  [dra.nueva@hospital.com____________________________________] ⏳  │   │  .validating
│  │  ✅ Formato válido + Dominio permitido                           │   │  .success-hint
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Nombre Completo *                                               │   │
│  │  [Dra. Ana López____________________________________________]    │   │
│  │  ✅ 2-100 chars, solo letras                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Contraseña *                                                    │   │
│  │  [SecurePass123!____________________________________________] 👁️ │   │
│  │  Fortaleza: 🟢🟢🟢🟢⚪  FUERTE                                     │   │
│  │  ✅ 12+ chars  ✅ Mayúscula  ✅ Minúscula  ✅ Número  ✅ Símbolo   │   │
│  │  ✅ No en HaveIBeenPwned  ✅ No en historial                       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Roles * (Mín 1, Máx 5)                               ▼ [☑] MEDICO │   │
│  │  ─────────────────────────────────────────────────────────────────  │   │
│  │  [☐] ENFERMERO      [☐] TENANT_ADMIN   [☐] AUDITOR                │   │
│  │  [☐] PACIENTE                                                      │   │
│  │                                                                     │   │
│  │  ℹ️ TENANT_ADMIN y AUDITOR requieren MFA obligatorio              │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  [☑] Forzar MFA al primer login (recomendado)                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]                              [ CREAR USUARIO ]  ← .btn-primary│
└─────────────────────────────────────────────────────────────────────────────┘
```
## Estado: ERROR CAMPO (Validación Inline)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ➕ Nuevo Usuario                                    [×] Cerrar              │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Email *                                                         │   │
│  │  [dra.nueva@otrodominio.com__________________________________] ❌  │   │  .error
│  │  ❌ Use email institucional @hospital.com                        │   │  .error-msg
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Contraseña *                                                    │   │
│  │  [weak________________________________________________________] ❌  │   │  .error
│  │  ❌ Contraseña muy débil                                          │   │
│  │  ❌ Mín 12 caracteres (actual: 4)                                │   │
│  │  ❌ Falta mayúscula                                              │   │
│  │  ❌ Falta número                                                 │   │
│  │  ❌ Falta símbolo                                                │   │
│  │  Fortaleza: 🔴🔴🔴🔴🔴  MUY DÉBIL                                │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Roles * (Mín 1, Máx 5)                               ▼ [☐]        │   │
│  │  ─────────────────────────────────────────────────────────────────  │   │
│  │  [☐] MEDICO    [☐] ENFERMERO    [☐] TENANT_ADMIN    [☐] AUDITOR   │   │
│  │  [☐] PACIENTE                                                      │   │
│  │  ❌ Seleccione al menos un rol                                   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]                              [ CREAR USUARIO ]  (disabled)   │
└─────────────────────────────────────────────────────────────────────────────┘
Accesibilidad:
- aria-invalid="true" en inputs con error
- aria-describedby="email-error password-error roles-error"
- role="alert" en mensajes error
- Focus: Primer campo con error al intentar submit
```

## Estado: ERROR SERVIDOR (Email Duplicado / Cupo / Tenant Inactivo)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ➕ Nuevo Usuario                                    [×] Cerrar              │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Email *                                                         │   │
│  │  [dra.nueva@hospital.com____________________________________] ❌  │   │
│  │  ❌ Este email ya está registrado en su organización            │   │  .error-msg
│  │     ¿Es usted? [ Iniciar sesión ]                               │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Nombre Completo *                                               │   │
│  │  [Dra. Ana López____________________________________________]    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Contraseña *                                                    │   │
│  │  [SecurePass123!____________________________________________] 👁️ │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  Roles * (Mín 1, Máx 5)                               ▼ [☑] MEDICO │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]                              [ CREAR USUARIO ]               │
│  🔴 Toast Error: Email ya registrado en este tenant. ¿Es usted? [ Login ]  │
└─────────────────────────────────────────────────────────────────────────────┘
Variantes Error Servidor:
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔴 Cupo excedido: "Cupo de usuarios agotado (50/50). Contacte a Platform Admin" │
│  🔴 Tenant inactivo: "No puede crear usuarios: organización suspendida"       │
│  🔴 Rol no permitido: "El rol AUDITOR no está disponible en su plan"         │
│  🔴 Sin permisos: "Requiere rol TENANT_ADMIN o PLATFORM_ADMIN"              │
└─────────────────────────────────────────────────────────────────────────────┘
```
## Estado: ÉXITO (Usuario Creado)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ➕ Nuevo Usuario                                    [×] Cerrar              │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ✅ Toast Éxito: Usuario creado. Credenciales temporales enviadas por email │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  ✅ Usuario creado exitosamente                                    │   │
│  │  📧 Email: dra.nueva@hospital.com                                 │   │
│  │  👤 Nombre: Dra. Ana López                                        │   │
│  │  🏷️ Roles: MEDICO                                                 │   │
│  │  🏥 Tenant: Hospital Central (HCH-001)                            │   │
│  │  🔐 MFA: Requerido (configurado en primer login)                  │   │
│  │  🔐 Contraseña temporal: Xk9#mP2$vL5                            │   │
│  │  [ Copiar ]  [ Reenviar email ]                                   │   │
│  │  [ Continuar ]  [ Crear otro usuario ]                            │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
Auto-cierre: Modal se cierra tras 3s o click "Continuar"
Lista usuarios: Auto-actualiza con nuevo usuario (optimistic update)
```

## Validación Contraseña - Medidor Fortaleza (Detalle)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Contraseña *                                                [SecureP@ss123] 👁️ │
│  ─────────────────────────────────────────────────────────────────────────  │
│  Fortaleza: 🟢🟢🟢🟢🟢  FUERTE                                              │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  ✅ 12+ caracteres                                    14/12  ✅      │   │
│  │  ✅ Mayúscula (A-Z)                                   A/a       ✅      │   │
│  │  ✅ Minúscula (a-z)                                   a/A       ✅      │   │
│  │  ✅ Número (0-9)                                      1/1       ✅      │   │
│  │  ✅ Símbolo (!@#$%^&*)                                !/@/#     ✅      │   │
│  │  ✅ No en HaveIBeenPwned                              (check)   ✅      │   │
│  │  ✅ No en últimos 5 passwords                         (check)   ✅      │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│  💡 Consejo: Use frase memorable: "MiHospital2024!Seguro"                 │
└─────────────────────────────────────────────────────────────────────────────┘
Estados Visuales Fortaleza:
⚪⚪⚪⚪⚪  VACÍA / MUY DÉBIL     (rojo #C62828)
🔴⚪⚪⚪⚪  DÉBIL                 (rojo #C62828)
🟠🟠⚪⚪⚪  MEDIA                 (naranja #ED6C02)
🟢🟢🟢⚪⚪  BUENA                 (verde #2E7D32)
🟢🟢🟢🟢🟢  FUERTE               (verde #2E7D32)
```
## Responsive: Mobile (Modal Full-Screen)
```
┌─────────────────────────────────────┐
│  ➕ Nuevo Usuario            [×]    │  ← Fixed header
│  ─────────────────────────────────  │
│  Email *                            │
│  [______________________________]  │
│  Nombre *                           │
│  [______________________________]  │
│  Contraseña *              [👁️]    │
│  [______________________________]  │
│  Fortaleza: 🟢🟢🟢🟢⚪ FUERTE        │
│  Roles * (Mín 1, Máx 5)            │
│  [☑] MEDICO                        │
│  [☐] ENFERMERO                     │
│  [☐] TENANT_ADMIN  ⚠️ MFA oblig.  │
│  [☐] AUDITOR       ⚠️ MFA oblig.  │
│  [☐] PACIENTE                      │
│  [☑] Forzar MFA primer login       │
│  ─────────────────────────────────  │
│  [ Cancelar ]    [ CREAR USUARIO ]  │  ← Fixed footer, 100% width
└─────────────────────────────────────┘
Cambios Mobile:
- Modal: Full-screen, position: fixed, top: 0, bottom: 0
- Header/Footer: Fixed, z-index superior
- Body: Scroll interno, padding-bottom: 80px (footer height)
- Inputs: 100% width, font-size 16px
- Checkboxes: 44x44px touch targets
- Buttons: 100% width, min-height 48px
```
## Mermaid para draw.io/Figma
```mermaid
stateDiagram-v2
    [*] --> MODAL_OPEN: Click "Nuevo Usuario"
    MODAL_OPEN --> VALIDATING: onChange/onBlur (debounced)
    MODAL_OPEN --> FIELD_ERROR: Submit con errores
    MODAL_OPEN --> SERVER_ERROR: Submit éxito validación, error servidor
    MODAL_OPEN --> SUCCESS: Submit exitoso
    VALIDATING --> MODAL_OPEN: Validación completa
    VALIDATING --> FIELD_ERROR: Error validación cliente
    FIELD_ERROR --> MODAL_OPEN: Usuario corrige campo
    SERVER_ERROR --> MODAL_OPEN: Usuario corrige / reintenta
    SERVER_ERROR --> [*]: Cerrar modal
    SUCCESS --> [*]: Auto-cierre 3s / Click "Continuar"
    SUCCESS --> MODAL_OPEN: "Crear otro usuario"
    state VALIDATING {
        [*] --> EMAIL_CHECK: onBlur email
        EMAIL_CHECK --> FORMAT_CHECK: Formato válido
        FORMAT_CHECK --> DOMAIN_CHECK: Dominio @hospital.com
        DOMAIN_CHECK --> UNIQUENESS_CHECK: API unicidad (debounced)
        UNIQUENESS_CHECK --> [*]: Resultado
        [*] --> PASSWORD_STRENGTH: onInput password
        PASSWORD_STRENGTH --> LENGTH_CHECK
        LENGTH_CHECK --> COMPLEXITY_CHECK
        COMPLEXITY_CHECK --> HIBP_CHECK: API HIBP (fail-open)
        HIBP_CHECK --> HISTORY_CHECK: Últimos 5 hashes
        HISTORY_CHECK --> [*]: Actualizar medidor
        [*] --> ROLES_VALIDATION: onChange checkboxes
        ROLES_VALIDATION --> MIN_MAX_CHECK: 1-5 roles
        MIN_MAX_CHECK --> MFA_REQUIRED_CHECK: TENANT_ADMIN/AUDITOR
        MFA_REQUIRED_CHECK --> [*]: Actualizar UI MFA checkbox
    end
```
