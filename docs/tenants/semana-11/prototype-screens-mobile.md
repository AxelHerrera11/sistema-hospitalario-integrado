# Semana 11 — Pantallas Móvil (Capturas/Descripciones)
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## 1. Login Form (`/auth/login`) - Mobile 375px

### Estado: Vacío (Initial)
```
┌─────────────────────────────────────┐
│  🏥 SISTEMA HOSPITALARIO            │
├─────────────────────────────────────┤
│                                     │
│   ┌───────────────────────────────┐ │
│   │  🔐 Iniciar Sesión            │ │
│   │                               │ │
│   │  Tenant: Hospital Central ▼  │ │
│   │  [_________________________]  │ │  ← 100% width
│   │                               │ │
│   │  Email institucional     👤   │ │
│   │  [_________________________]  │ │
│   │                               │ │
│   │  Contraseña             👁️    │ │
│   │  [_________________________]  │ │
│   │                               │ │
│   │  [☐ Recordarme 30 días]       │ │
│   │                               │ │
│   │  [ INICIAR SESIÓN ]           │ │  ← 100% width, 48px height
│   │                               │ │
│   │  ¿Olvidó? | ¿Problemas MFA?   │ │
│   │                               │ │
│   │  ℹ️ Tenant: HCH-001           │ │
│   └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

### Estado: Cargando
```
┌─────────────────────────────────────┐
│  🏥 SISTEMA HOSPITALARIO            │
├─────────────────────────────────────┤
│                                     │
│   ┌───────────────────────────────┐ │
│   │  🔐 Iniciar Sesión            │ │
│   │                               │ │
│   │  Tenant: Hospital Central ▼  │ │
│   │  [dra.gonzalez@hospital.com__] │ │  (disabled)
│   │                               │ │
│   │  Contraseña             👁️    │ │
│   │  [●●●●●●●●●●●●●•••••]         │ │  (disabled)
│   │                               │ │
│   │  [☑ Recordarme...]  (disabled)│ │
│   │                               │ │
│   │  [ ⏳ Validando credenciales ] │ │  ← spinner + disabled
│   │                               │ │
│   │  ℹ️ Tenant: HCH-001           │ │
│   └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

### Estado: Error Credenciales
```
┌─────────────────────────────────────┐
│  ...                                │
│  ┌───────────────────────────────┐ │
│  │  🔐 Iniciar Sesión            │ │
│  │                               │ │
│  │  Email institucional     👤   │ │
│  │  [dra.gonzalez@hospital.com_] │ │
│   │                               │ │
│   │  Contraseña             👁️    │ │
│  │  [●●●●●●●●●●●●••••••] .error  │ │  ← border rojo
│   │  ⚠️ Credenciales inválidas. Intento 2/5 │  ← .error-msg
│   │  [☐ Recordarme...]                        │
│   │                                         │ │
│   │  [ INICIAR SESIÓN ]                     │ │
│   │                                         │ │
│   │  ¿Olvidó? | ¿Problemas MFA?             │ │
│   │                                         │ │
│   │  🔴 Toast: Credenciales inválidas. Intento 2/5 │
│   │     [ Reintentar ] [ ¿Olvidó? ] [ Cambiar Tenant ] │
│   │  ℹ️ Tenant: HCH-001                       │ │
│   └───────────────────────────────┘           │
└─────────────────────────────────────┘
```

### Estado: Cuenta Bloqueada
```
┌─────────────────────────────────────┐
│  ...                                │
│  ┌───────────────────────────────┐ │
│  │  🔐 Iniciar Sesión            │ │
│  │                               │ │
│  │  Email institucional 👤       │ │  (disabled)
│  │  [dra.gonzalez@hospital.com_] │ │
│  │                               │ │
│  │  Contraseña            👁️     │ │  (disabled)
│  │  [●●●●●●●●●●●••••••••]        │ │
│  │                               │ │
│  │  [ INICIAR SESIÓN ] (disabled)│ │
│  │                               │ │
│  │  ⚠️ Cuenta bloqueada 15 min   │ │
│  │     Intento 5 de 5            │ │
│  │     ⏱️ Desbloqueo en: 14:52   │ │
│   │                               │ │
│   │  [ Contactar Administrador ]  │ │
│   │                               │ │
│   │  🔴 Modal: Cuenta bloqueada   │ │
│   │     ⏱️ 14:51 restantes        │ │
│   │     [ Esperar ] [ Contactar ] │ │
│   │  ℹ️ Tenant: HCH-001           │ │
│   └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

### Estado: Éxito (Redirect)
```
┌─────────────────────────────────────┐
│  ...                                │
│  ┌───────────────────────────────┐ │
│  │  🔐 Iniciar Sesión            │ │
│  │                               │ │
│  │  ✅ Toast: Bienvenida, Dra. G │ │
│  │     Redirigiendo...           │ │
│  │     [ ████████████████████ ] 100%│ │
│   │                               │ │
│   │  ℹ️ Tenant: HCH-001           │ │
│   └───────────────────────────────┘ │
└─────────────────────────────────────┘
```

---

## 2. MFA Challenge (Mobile 375px)

### Estado: Desafío TOTP
```
┌─────────────────────────────────────┐
│  🔐 Autenticación 2FA              │
├─────────────────────────────────────┤
│                                     │
│  📱 App Autenticadora (TOTP)       │
│                                     │
│  1. Abra su app                    │
│  2. Ingrese código 6 dígitos       │
│                                     │
│  Código: [1][2][3][4][5][6]        │
│  ← 6 inputs 100% width c/u         │
│                                     │
│  ⏱️ Expira en: 25s ████████░░░░░░  │
│                                     │
│  [ VERIFICAR CÓDIGO ]              │
│  ← 100% width                      │
│                                     │
│  ─────────────────────────────────  │
│                                     │
│  🔑 ¿Prefiere Passkey?             │
│  [ Usar FaceID / TouchID ]         │
│                                     │
│  ❓ ¿Problemas?                    │
│  [ No tengo acceso a la app ]      │
└─────────────────────────────────────┘
```

### Estado: Verificando
```
┌─────────────────────────────────────┐
│  🔐 Autenticación 2FA              │
├─────────────────────────────────────┤
|                                     |
|  📱 App Autenticadora (TOTP)       |
|                                     |
|  ⏳ Verificando código...          |
|    [ ████████░░░░░░░░░░░░░░░░ ]    |
|                                     |
|  [ ⏳ Verificando... ]  (disabled) |
|                                     |
|  ⏱️ Restante: 18s ████████░░░░░░░░░░ |
|                                     |
└─────────────────────────────────────┘
```

### Estado: Error Código
```
┌─────────────────────────────────────┐
│  🔐 Autenticación 2FA              │
├─────────────────────────────────────┤
|                                     |
|  📱 App Autenticadora (TOTP)       |
|                                     |
|  ❌ Código inválido. Intento 2/3   |
|    Código: [1][2][3][4][5][6] ❌   │
|                                     |
|  ⏱️ Expira en: 12s ██████░░░░░░░░░░  |
|                                     |
|  [ VERIFICAR CÓDIGO ]              |
|                                     |
|  [ Reenviar código ]               |
|                                     |
|  ─────────────────────────────────  |
|                                     |
|  🔑 ¿Prefiere Passkey?             |
|  [ Usar FaceID / TouchID ]         |
|                                     |
|  ❓ ¿Problemas? [ No tengo app ]   │
└─────────────────────────────────────┘
```

### Estado: Bloqueo MFA (3 Fallos)
```
┌─────────────────────────────────────┐
│  🔐 Autenticación 2FA          [×] │
├─────────────────────────────────────┤
|                                     |
|  🔴 MFA Bloqueado: Demasiados      |
|     intentos fallidos              |
|     Intento 3 de 3                 |
|     ⏱️ Desbloqueo en: 29:45       |
|                                     |
|     Código: [ ][ ][ ][ ][ ][ ]     |
|     (disabled)                     |
|                                     |
|     [ Verificar Código ] (disabled) |
|                                     |
|     ⚠️ Demasiados intentos.        |
|        Intente en 30 minutos.      |
|        Notificado a admin.         |
|                                     |
|     [ Contactar Administrador ]    |
|     [ Esperar desbloqueo ]         |
|                                     |
└─────────────────────────────────────┘
```

### Estado: WebAuthn (Passkey)
```
┌─────────────────────────────────────┐
│  🔑 Autenticación con Passkey      │
├─────────────────────────────────────┤
|                                     |
|  🔐 Toque sensor huella / FaceID   |
|                                     |
|       [ 👆 Autenticar con          |
|        Dispositivo ]               |
|                                     |
|  Dispositivos registrados:         |
| ┌───────────────────────────────┐  |
| │ 📱 iPhone 15 Pro (FaceID)    │  |
│ │  Hoy 08:30              [⋮]  │  |
| ├────────────────────────────────┤  |
| │ 💻 MacBook Pro (TouchID)      │  |
| │ Ayer 14:22             [⋮]   │  |
| ├────────────────────────────────┤  |
| │ 🔐 YubiKey 5C NFC            │  |
│ │ Semanal               [⋮]     │  |
| └───────────────────────────────┘  |
|                                     |
| [ + Registrar nuevo Passkey ]      |
|                                     |
| ─────────────────────────────────  |
|                                     |
| 📱 ¿Prefiere código TOTP?          |
| [ Usar código de 6 dígitos ]       |
└─────────────────────────────────────┘
```

---

## 3. Admin - Alta Usuario (Mobile - Modal Full-Screen)

### Estado: Vacío
```
┌─────────────────────────────────────┐
│  ➕ Nuevo Usuario            [×]    │  ← Fixed header
├─────────────────────────────────────┤
|                                     |
|  Email *                            |
|  [______________________________]  |
|                                     |
|  Nombre *                           |
|  [______________________________]  |
|                                     |
|  Contraseña *               [👁️]   |
|  [______________________________]  |
|  Fortaleza: 🟢🟢🟢🟢⚪  FUERTE       |
|                                     |
|  Roles * (Mín 1, Máx 5)            |
|  [☑] MEDICO                        |
|  [☐] ENFERMERO                     |
|  [☐] TENANT_ADMIN  ⚠️ MFA oblig.  |
|  [☐] AUDITOR       ⚠️ MFA oblig.  |
|  [☐] PACIENTE                      |
|                                     |
|  [☑] Forzar MFA primer login       |
|                                     |
|  ─────────────────────────────────  |
|  [ Cancelar ]    [ CREAR USUARIO ]  │  ← Fixed footer
|                                     |
└─────────────────────────────────────┘
```

### Estado: Validando Tiempo Real
```
┌─────────────────────────────────────┐
|  ...                                |
|  Email *                            |
|  [dra.nueva@hospital.com_______] ⏳  │  ← .validating
|  ✅ Formato válido + Dominio OK     │  ← .success-hint
|                                     |
|  Contraseña *              [👁️]    |
|  [SecurePass123!________________]  |
|  Fortaleza: 🟢🟢🟢🟢⚪  FUERTE       │
|  ✅ 12+ chars  ✅ Mayús  ✅ Minús  ✅ Num  ✅ Símbolo |
|  ✅ No HIBP  ✅ No historial        │
|                                     |
|  Roles * (Mín 1, Máx 5)            |
|  [☑] MEDICO                        |
|  [☐] ENFERMERO                     |
|  [☐] TENANT_ADMIN  ⚠️ MFA oblig.  |
|  [☐] AUDITOR       ⚠️ MFA oblig.  |
|                                     |
|  [ Cancelar ]    [ CREAR USUARIO ]  │
|                                     |
└─────────────────────────────────────┘
```

### Estado: Error Campo
```
┌─────────────────────────────────────┐
|  ...                                |
|  Email *                            |
|  [dra.nueva@otrodominio.com_____] ❌ │  ← .error
|  ❌ Use email @hospital.com        │  ← .error-msg
|                                     |
|  Contraseña *              [👁️]    |
|  [weak________________________] ❌  │  ← .error
|  ❌ Contraseña muy débil           │
|  ❌ Mín 12 caracteres (actual: 4)  │
|  ❌ Falta mayúscula                │
|  ❌ Falta número                   │
|  ❌ Falta símbolo                  │
|  Fortaleza: 🔴🔴🔴🔴🔴 MUY DÉBIL   │
|                                     |
|  Roles * (Mín 1, Máx 5)            |
|  [☐] MEDICO    [☐] ENFERMERO       │
|  [☐] TENANT_ADMIN [☐] AUDITOR      |
|  [☐] PACIENTE                      │
|  ❌ Seleccione al menos un rol     │
|                                     |
|  [ Cancelar ]    [ CREAR ] (disabled)│
└─────────────────────────────────────┘
```

### Estado: Error Servidor (Email Duplicado)
```
┌─────────────────────────────────────┐
|  ...                                |
|  Email *                            |
|  [dra.nueva@hospital.com_______] ❌  |
|  ❌ Este email ya está registrado   │
|     en su organización             │
|     ¿Es usted? [ Iniciar sesión ]  │
|                                     |
|  [ Cancelar ]    [ CREAR USUARIO ]  │
|                                     |
|  🔴 Toast: Email ya registrado en  │
|     este tenant. ¿Es usted? [Login] │
└─────────────────────────────────────┘
```

### Estado: Éxito
```
┌─────────────────────────────────────┐
|  ...                                |
|  ✅ Toast Éxito: Usuario creado.    |
|  Credenciales temporales enviadas   │
|  por email                          |
|  ┌───────────────────────────────┐  |
|  │  ✅ Usuario creado            │  |
|  │                               │  |
|  │  📧 Email: dra.nueva@hospital.com│ |
|  │  👤 Nombre: Dra. Ana López    │  |
|  │  🏷️ Roles: MEDICO            │  |
|  │  🏥 Tenant: HCH-001           │  |
|  │  🔐 MFA: Requerido (1er login)│  |
|  │                               │  |
|  │  🔐 Password: Xk9#mP2$vL5    │  |
|  │  [ Copiar ] [ Reenviar email ]│  |
|  │                               │  |
|  │  [ Continuar ] [ Crear otro ] │  |
|  └───────────────────────────────┘  |
└─────────────────────────────────────┘
```

---

## 5. Admin - Lista Usuarios + Force-Revoke (Mobile)

### Lista - Cards Apiladas
```
┌─────────────────────────────────────┐
│  👥 Usuarios HCH        [+] [☰]     │
├─────────────────────────────────────┤
|  🔍 [___________________] [▼]       |
├─────────────────────────────────────┤
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 👤 Dra. González              │  |
|  │ 📧 dra.g@hch.com              │  |
|  │ 🏷️ MEDICO        🟢 Activo    │  |
|  │ ─────────────────────────     │  |
|  │ [⋮] Acciones                  │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 👤 Lic. Ruiz                  │  |
|  │ 📧 lic.r@hch.com              │  |
|  │ 🏷️ ENFERMERO       🟢 Activo  │  |
|  │ ─────────────────────────     │  |
|  │ [⋮] Acciones                  │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 👤 Dr. Silva                  │  |
|  │ 📧 dr.s@hch.com               │  |
|  │ 🏷️ AUDITOR        🟡 MFA      │  |
|  │ ─────────────────────────     │  |
|  │ [⋮] Acciones                  │  |
|  └───────────────────────────────┘  |
|                                     |
|  ◄ 1 [2] [3] [4] [5] ►   20/pág    |
└─────────────────────────────────────┘
```

### Menú Acciones [⋮] - Touch Friendly
```
┌─────────────────────────────────────┐
|  [⋮] Acciones - Dra. Torres       [×] │
├─────────────────────────────────────┤
|                                     |
|  ✏️  Editar usuario                |
|  ─────────────────────────────────  |
|  🔑  Forzar MFA                    |
|  ─────────────────────────────────  |
|  🔒  Bloquear usuario              |
|  ─────────────────────────────────  |
|  🔴  Revocar TODAS las sesiones    │  ← .btn-danger
|                                    |
└─────────────────────────────────────┘
```

### Modal Force-Revoke (Mobile Full-Screen)
```
┌─────────────────────────────────────┐
|  🔴 Revocar Sesiones - Dra. Torres [×] │
├─────────────────────────────────────┤
|                                     |
|  ⚠️ Revocar TODAS las sesiones de   |
|  Dra. Torres                        |
|                                     |
|  Se cerrarán sesión en TODOS los    |
|  dispositivos (3 activos):          |
|  📱 iPhone 15 Pro - Chrome 119 - Hoy│
|  💻 MacBook Pro - Safari 17 - Ayer  |
|  🔐 YubiKey 5C NFC - Hace 2 horas   |
|                                     |
|  El usuario deberá iniciar sesión   |
|  nuevamente en todos los dispositivos.|
|                                     |
|  Motivo *:                          |
|  ▼ [ Seguridad ▼ ] [ Cambio rol ]   |
|  [ Sospecha compromiso ] [ Otro... ] |
|                                     |
|  Comentario (opcional):             |
|  [________________________________] |
|                                     |
|  ⚠️ IRREVERSIBLE. Se auditará.      |
|                                     |
|  [ Cancelar ]      [ REVOCAR ]      │  ← 100% width, 48px height
|                                     |
└─────────────────────────────────────┘
```

### Toast Éxito Mobile
```
┌─────────────────────────────────────┐
|  ✅ 3 sesiones revocadas para       │
|  Dra. Torres                        |
|  Motivo: Seguridad                  |
|  [ Ver en Auditoría ] [ Deshacer ]  │
└─────────────────────────────────────┘
← Bottom fixed, 100% width, safe-area
```

---

## 7. Auditoría Mobile (Cards)

```
┌─────────────────────────────────────┐
|  📋 Auditoría - HCH          [⚙️]   │
├─────────────────────────────────────┤
|  🔍 [Filtros ▼]  [Exportar]         |
├─────────────────────────────────────┤
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 🔴 REFRESH_REUSE_DETECTED     │  |
|  │ ─────────────────────────────  │  |
|  │ 🕐 2026-10-18 10:45:00        │  |
|  │ 👤 sys (service-account)      │  |
|  │ 🏷️ CRÍTICO                     │  |
|  │ 🔍 Ver detalle ▶              │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 🟢 LOGIN_SUCCESS              │  |
|  │ ─────────────────────────────  │  |
|  │ 🕐 2026-10-18 11:20:05        │  |
|  │ 👤 dra.gonzalez               │  |
|  │ 🏷️ INFO                        │  |
|  │ 🔍 Ver detalle ▶              │  |
|  └───────────────────────────────┘  |
|                                     |
|  ◄ 1 [2] [3] [4] [5] ►   20/pág    |
└─────────────────────────────────────┘

Touch: Tap card → Expande detalle (slide-up)
Swipe left → Acciones rápidas [Ver en SIEM] [Exportar]
Long press → Selección múltiple
Pull to refresh → Actualiza lista
```

---

## 8. Paciente Portal Mobile

### Dashboard Apilado
```
┌─────────────────────────────────────┐
│  🏥 Mi Portal - María Paz    👤 [≡] │
├─────────────────────────────────────┤
|  Hola, María              [🔔 3]    |
├─────────────────────────────────────┤
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 📅 PRÓXIMAS CITAS             │  |
|  │ ─────────────────────────────  │  |
|  │ 📅 2026-10-20 10:00           │  |
|  │ 🏥 Cardiología - Dr. González │  |
|  │ [ Ver ] [ 🗓️ ]               │  |
|  ├─────────────────────────────┤  |
|  │ 📅 2026-10-25 09:30          │  |
|  │ 🏥 Endocrinología - Dra. Torres│ │
|  │ [ Ver ] [ 🗓️ ]               │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 💊 MEDICACIÓN ACTUAL          │  |
|  │ ─────────────────────────────  │  |
|  │ 💊 Losartán 50mg              │  |
|  │ 1 comp/día - Desayuno         │  |
|  ├─────────────────────────────┤  |
|  │ 💊 Metformina 850mg           │  |
|  │ 2 comp/día - Comida/Cena      │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 📋 RESULTADOS RECIENTES       │  |
|  │ ─────────────────────────────  │  |
|  │ 📄 Hemograma 2026-10-15      │  |
|  │ [ Ver ]                       │  |
|  ├─────────────────────────────┤  |
|  │ 📄 Radiografía 2026-10-10   │  |
|  │ [ Ver ]                      │  |
|  └───────────────────────────────┘  |
|                                     |
|  ─────────────────────────────────  |
|  📄 Expediente | 📅 Citas | 💊 Meds │
|  ─────────────────────────────────  |
|  🔒 Último: 17/10 19:30             |
|  🔐 MFA: No configurado [Configurar]│
└─────────────────────────────────────┘

Bottom Nav (Fixed):
┌─────────────────────────────────────┐
| 📄 Expediente  📅 Citas  💊 Meds  ⚙️ │
└─────────────────────────────────────┘
```

---

## 9. Touch Targets & Accesibilidad Mobile

| Elemento            | Tamaño Mínimo | Implementación                            |
|---------------------|---------------|-------------------------------------------|
| Botones Primarios   | 48×48px       | `min-height: 48px; min-width: 48px;`      |
| Botones Secundarios | 44×44px       | `min-height: 44px; min-width: 44px;`      |
| Inputs              | 48px height   | `height: 48px; font-size: 16px;`          |
| Checkboxes/Radios   | 24×24px       | `width: 24px; height: 24px; accent-color` |
| Toggle Switches     | 52×28px       | Custom CSS                                |
| Dropdown Items      | 48px height   | `padding: 12px 16px; min-height: 48px`    |
| Modal Buttons       | 48px height   | `min-height: 48px; width: 100%`           |
| Toast Actions       | 44px height   | `padding: 12px 16px; min-height: 44px`    |

### Safe Area Support
```css
/* iPhone notch / Dynamic Island */
.bottom-nav {
  padding-bottom: env(safe-area-inset-bottom, 0);
}

.modal-content {
  padding-bottom: env(safe-area-inset-bottom, 16px);
}

.fixed-footer {
  padding-bottom: env(safe-area-inset-bottom, 0);
}
```

### Haptic Feedback (iOS)
```javascript
// En botones de acción crítica
button.addEventListener('click', () => {
  if (navigator.vibrate) {
    navigator.vibrate(10); // Light tap
  }
});

// En error crítico
button.addEventListener('click', () => {
  if (navigator.vibrate) {
    navigator.vibrate([50, 50, 50]); // Triple pulse
  }
});
```

---

## 10. Testing Matrix Mobile

| Test                  | Dispositivo                             | Navegador               | Checklist                 |
|-----------------------|-----------------------------------------|-------------------------|---------------------------|
| **Visual Regression** | iPhone SE, 13, 14, 15, iPad, Galaxy S23 | Chrome, Safari, Firefox | Percy/Chromatic           |
| **Accessibility**     | NVDA, VoiceOver, TalkBack               | Chrome, Safari          | axe-core + manual         |
| **Performance**       | iPhone SE (throttled 3G)                | Chrome DevTools         | Lighthouse ≥ 90           |
| **Touch Testing**     | iPhone 13, iPad, Galaxy Tab             | Safari, Chrome          | Touch targets, gestures   |
| **Offline**           | Flight mode + throttled                 | Chrome DevTools         | Service Worker, Queue     |
| **Orientation**       | Portrait + Landscape                    | Todos                   | Lock/Unlock, layout shift |
