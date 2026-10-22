# Semana 08 — Wireframes Anotados y Accesibilidad
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación  
**Versión:** 1.0 | **Fecha:** 2026-10-18

> Complementa [`ux-user-flows.md`](ux-user-flows.md) (personas, flujos, errores, ayuda contextual y KPIs).

---

## 1. Wireframes Anotados (4-6 Pantallas Clave)

### 1.1 Pantalla 1: Login Form (Estados: Vacío, Carga, Error, Éxito)

```
┌─────────────────────────────────────────────────────────────────┐
│  🏥 SISTEMA HOSPITALARIO INTEGRADO                              │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  🔐 Iniciar Sesión                                      │   │
│   │                                                         │   │
│   │  ┌─────────────────────────────────────────────────┐   │   │
│   │  │ Email institucional                    👤       │   │   │
│   │  │ dra.gonzalez@hospital.com                    │   │   │
│   │  └─────────────────────────────────────────────────┘   │   │
│   │                                                         │   │
│   │  ┌─────────────────────────────────────────────────┐   │   │
│   │  │ Contraseña                          👁️        │   │   │
│   │  │ ••••••••••••••••                              │   │   │
│   │  └─────────────────────────────────────────────────┘   │   │
│   │  [☐ Recordarme en este dispositivo (30 días)]         │   │
│   │                                                         │   │
│   │  ┌─────────────────────────────────────────────────┐   │   │
│   │  │          [ INICIAR SESIÓN ]  ← Primary          │   │   │
│   │  └─────────────────────────────────────────────────┘   │   │
│   │                                                         │   │
│   │  ┌─────────────────────────────────────────────────┐   │   │
│   │  │  ¿Olvidó su contraseña?  |  ¿Problemas con MFA? │   │   │
│   │  └─────────────────────────────────────────────────┘   │   │
│   │                                                         │   │
│   │  ℹ️ Tenant: Hospital Central (HCH-001)                │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Estados:                                                       │
│  🟢 VACÍO: Placeholders visibles, botón habilitado              │
│  🟡 CARGA: Spinner en botón, inputs disabled, "Validando..."   │
│  🔴 ERROR: Borde rojo inputs, mensaje "Credenciales inválidas. │
│      Intento 2 de 5. ¿Olvidó contraseña?" + enlace ayuda       │
│  🟢 ÉXITO: Toast verde "Bienvenida, Dra. González" + redirect  │
└─────────────────────────────────────────────────────────────────┘
```

**Reglas de Interacción:**
- **Enter** en cualquier campo = Submit
- **Tab** navega: Email → Password → Checkbox → Botón → Enlaces
- **Auto-focus** en Email al cargar
- **Password visibility toggle** (👁️) muestra/oculta texto
- **Tenant selector** solo si usuario pertenece a múltiples tenants (dropdown en header)

---

### 1.2 Pantalla 2: MFA Challenge (TOTP + WebAuthn)

```
┌─────────────────────────────────────────────────────────────────┐
│  🔐 Autenticación de Doble Factor Requerida                    │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  📱 Método: Aplicación Autenticadora (TOTP)            │   │
│   │                                                         │   │
│   │  1. Abra su app (Google Authenticator, Authy, etc.)   │   │
│   │  2. Ingrese el código de 6 dígitos                     │   │
│   │                                                         │   │
│   │  ┌─────────────────────────────────────────────────┐   │   │
│   │  │  Código: [ 1 2 3 4 5 6 ]  ◄ Auto-focus, numérico│   │   │
│   │  └─────────────────────────────────────────────────┘   │   │
│   │                                                         │   │
│   │  ⏱️ Código expira en: 25 segundos                      │   │
│   │                                                         │   │
│   │  ┌─────────────────────────────────────────────────┐   │   │
│   │  │        [ VERIFICAR CÓDIGO ]                     │   │   │
│   │  └─────────────────────────────────────────────────┘   │   │
│   │                                                         │   │
│   │  ─────────────────────────────────────────────────      │   │
│   │  🔑 ¿Prefiere Passkey?  [ Usar FaceID / TouchID ]      │   │
│   │                                                         │   │
│   │  ❓ ¿Problemas?  [ No tengo acceso a la app ]          │   │
│   └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  Estados:                                                       │
│  🟢 DESAFÍO: Cuenta regresiva 30s, auto-submit al completar 6  │
│  🟡 VERIFICANDO: Spinner, "Verificando código..."              │
│  🔴 ERROR: "Código inválido. Intento 2 de 3" + botón Reenviar  │
│  🔴 BLOQUEO: "Demasiados intentos. Intente en 30 minutos"      │
│  🟢 ÉXITO: Transición suave a dashboard                         │
└─────────────────────────────────────────────────────────────────┘
```

**WebAuthn Flow (Alternativa):**
```
┌─────────────────────────────────────────────────────────────────┐
│  🔑 Autenticación con Passkey                                   │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  🔐 Toque el sensor de huella / FaceID / Windows Hello │   │
│   │                                                         │   │
│   │        [ 👆 Autenticar con Dispositivo ]               │   │
│   │                                                         │   │
│   │  Dispositivos registrados:                              │   │
│   │  • 📱 iPhone 15 Pro (FaceID) - Hoy 08:30               │   │
│   │  💻 MacBook Pro (TouchID) - Ayer 14:22                 │   │
│   │  🔐 YubiKey 5 NFC - Semanal                            │   │
│   │                                                         │   │
│   │  [ Registrar nuevo Passkey ]                           │   │
│   └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

### 1.3 Pantalla 3: Admin - Alta Usuario (Modal)

```
┌─────────────────────────────────────────────────────────────────┐
│  ➕ Nuevo Usuario                              [×] Cerrar       │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Email *                              │  dra.nueva@hch  │   │
│  │  ──────────────────────────────────  │  ─────────────  │   │
│  │  ✅ Formato válido @hospital.com     │                 │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Nombre Completo *                  │  Dra. Ana López   │   │
│  │  ────────────────────────────────  │  ───────────────  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  Roles * (Mín 1, Máx 5)                ▼  [☑] MEDICO   │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │  [☐] ENFERMERO   [☐] TENANT_ADMIN   [☐] AUDITOR        │   │
│  │  [☐] PACIENTE                                           │   │
│  │                                                         │   │
│  │  ℹ️ TENANT_ADMIN y AUDITOR requieren MFA obligatorio   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  [☑] Forzar MFA al primer login (recomendado)          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│  [ Cancelar ]                    [ CREAR USUARIO ]  ← Primary  │
│                                                                 │
│  Estados:                                                       │
│  🟢 VACÍO: Placeholders, botón disabled hasta campos requeridos │
│  🟡 VALIDANDO: Spinner en botón, "Creando usuario..."          │
│  🔴 ERROR CAMPO: Borde rojo + mensaje bajo campo               │
│  🔴 ERROR SERVIDOR: Toast rojo "Email duplicado en tenant"     │
│  🔴 ERROR POLICY: Lista errores password (HIBP, longitud, etc) │
│  🟢 ÉXITO: Toast verde "Usuario creado. Credenciales enviadas" │
└─────────────────────────────────────────────────────────────────┘
```

**Validación Password en Tiempo Real:**
```
┌─────────────────────────────────────────────────────────────────┐
│  Contraseña *          │  [●●●●●●●●●●●●]  [👁️]  Fortaleza: 🟢  │
│  ─────────────────────────────────────────────────────────────  │
│  ✅ 12+ caracteres                                    12/12   │
│  ✅ Mayúscula                                         A/a     │
│  ✅ Minúscula                                         a/A     │
│  ✅ Número                                            0-9     │
│  ✅ Símbolo                                           !@#$%   │
│  ✅ No en HaveIBeenPwned                              ✓       │
│  ✅ No en últimos 5 passwords                         ✓       │
└─────────────────────────────────────────────────────────────────┘
```

---

### 1.4 Pantalla 4: Admin - Lista Usuarios + Force-Revoke

```
┌─────────────────────────────────────────────────────────────────┐
│  👥 Gestión de Usuarios - Hospital Central          [+ Nuevo]  │
│  ─────────────────────────────────────────────────────────────  │
│  🔍 Buscar: [________________________]  ▼ Filtros  [Exportar]  │
│  ─────────────────────────────────────────────────────────────  │
│  ┌──┬──────────────┬──────────┬──────────┬────────┬────────────┐│
│  │☑ │ Usuario      │ Email    │ Roles    │ Estado │ Acciones   ││
│  ├──┼──────────────┼──────────┼──────────┼────────┼────────────┤│
│  │☐ │ Dra. González│ dra.g... │ 🩺 MEDICO│ 🟢 Act │ [⋮] Menú  ││
│  ├──┼──────────────┼──────────┼──────────┼────────┼────────────┤│
│  │☐ │ Lic. Ruiz    │ lic.r... │ 💉 ENFER │ 🟢 Act │ [⋮] Menú  ││
│  ├──┼──────────────┼──────────┼──────────┼────────┼────────────┤│
│  │☐ │ Dr. Silva    │ dr.s...  │ 🔍 AUDIT │ 🟡 MFA │ [⋮] Menú  ││
│  ├──┼──────────────┼──────────┼──────────┼────────┼────────────┤│
│  │☐ │ Dra. Torres  │ adm.t... │ 👑 ADMIN │ 🔴 Bloq│ [⋮] Menú  ││
│  └──┴──────────────┴──────────┴──────────┴────────┴────────────┘│
│                                                                 │
│  Menú Acciones [⋮] por fila:                                    │
│  ┌─────────────────────────────────────────┐                    │
│  │  ✏️ Editar usuario                      │                    │
│  │  🔑 Forzar MFA                          │                    │
│  │  🔒 Bloquear / Desbloquear              │                    │
│  │  ─────────────────────────────────      │                    │
│  │  🔴 Revocar TODAS las sesiones  ← Danger│                    │
│  └─────────────────────────────────────────┘                    │
│                                                                 │
│  Modal Force-Revoke:                                            │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  ⚠️ Revocar Sesiones de Dra. Torres                     │   │
│  │                                                         │   │
│  │  Se cerrarán TODAS las sesiones activas (3 dispositivos)│   │
│  │  El usuario deberá iniciar sesión nuevamente.           │   │
│  │                                                         │   │
│  │  Motivo: [Seguridad ▼]  [Cambio rol] [Sospecha] [Otro] │   │
│  │                                                         │   │
│  │  [ Cancelar ]          [ REVOCAR SESIONES ] ← Danger   │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

### 1.5 Pantalla 5: Auditoría - Consulta Logs (AUDITOR)

```
┌─────────────────────────────────────────────────────────────────┐
│  📋 Auditoría de Movimientos - Hospital Central    [Exportar]   │
│  ─────────────────────────────────────────────────────────────  │
│  🔍 Filtros:  📅 Desde: [2026-10-01]  Hasta: [2026-10-18]      │
│               👤 Usuario: [____________]  🏷️ Evento: [▼ Todos] │
│               🏥 Módulo: [▼ Todos]  ⚠️ Severidad: [▼ Todas]     │
│  ─────────────────────────────────────────────────────────────  │
│  ┌──┬────────────────┬─────────┬──────────┬────────┬───────────┐│
│  │  │ Fecha/Hora     │ Usuario │ Evento   │ Módulo │ Severidad ││
│  ├──┼────────────────┼─────────┼──────────┼────────┼───────────┤│
│  │▶ │ 2026-10-18     │ dr.silva│ LOGIN_   │ Auth   │ 🟢 INFO   ││
│  │  │ 08:30:15       │         │ SUCCESS  │        │           ││
│  ├──┼────────────────┼──────────┼──────────┼────────┼───────────┤│
│  │  │ 2026-10-18     │ adm.tor │ REVOKE_  │ Admin  │ 🟠 WARN   ││
│  │  │ 09:15:22       │         │ SESSION  │        │           ││
│  ├──┼────────────────┼──────────┼──────────┼────────┼───────────┤│
│  │  │ 2026-10-18     │ sys     │ REFRESH_ │ Auth   │ 🔴 CRIT   ││
│  │  │ 10:45:00       │         │ REUSE_DET│        │           ││
│  ├──┼────────────────┼──────────┼──────────┼────────┼───────────┤│
│  │  │ 2026-10-18     │ dra.gon │ MFA_     │ Auth   │ 🟢 INFO   ││
│  │  │ 11:20:05       │         │ SUCCEEDED│        │           ││
│  └──┴────────────────┴─────────┴──────────┴────────┴───────────┘│
│                                                                 │
│  Detalle Evento (Click fila ▶):                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  🔴 REFRESH_REUSE_DETECTED - CRÍTICO                     │   │
│  │  ─────────────────────────────────────────────────────   │   │
│  │  Usuario: sys (service-account)                          │   │
│  │  Tenant: Hospital Central                                │   │
│  │  Device: Chrome 119 / Windows 10 / 192.168.1.45         │   │
│  │  IP: 192.168.1.45                                        │   │
│  │  Timestamp: 2026-10-18 10:45:00.123Z                     │   │
│  │  Severidad: CRÍTICO                                      │   │
│  │  Acciones: Revocación total ejecutada + Alerta SIEM      │   │
│  │  Hash Chain: a1b2c3d4... → e5f6g7h8... (verificado ✓)   │   │
│  │  [ Ver en SIEM ]  [ Crear Incidencia Jira ]  [ Cerrar ]  │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

### 1.6 Pantalla 6: Paciente - Portal (Vista Resumen)

```
┌─────────────────────────────────────────────────────────────────┐
│  🏥 Mi Portal - María Paz                    👤  [Cerrar Sesión]│
│  ─────────────────────────────────────────────────────────────  │
│  Hola, María                                          [🔔 3]    │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │ 📅 PRÓXIMAS     │  │ 💊 MEDICACIÓN   │  │ 📋 RESULTADOS   │ │
│  │ CITAS           │  │ ACTUAL          │  │ RECIENTES       │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤ │
│  │ 📅 2026-10-20  │  │ 💊 Losartán 50mg│  │ 📄 Hemograma    │ │
│  │ 10:00 - Cardio  │  │ 1 comp/día      │  │ 2026-10-15      │ │
│  │ Dr. González    │  │                 │  │ Ver ▶           │ │
│  ├─────────────────┤  ├─────────────────┤  ├─────────────────┤ │
│  │ 📅 2026-10-25  │  │ 💊 Metformina   │  │ 📄 Radiografía  │ │
│  │ 09:30 - Endo    │  │ 850mg 2/día     │  │ 2026-10-10      │ │
│  │ Dra. Torres     │  │                 │  │ Ver ▶           │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│  📄 Mi Expediente  |  📅 Mis Citas  |  💊 Medicación  |  ⚙️ Config│
│                                                                 │
│  🔒 Seguridad: Último acceso 2026-10-17 19:30 | [Ver actividad] │
│  🔐 MFA: No configurado  | [Configurar MFA]                      │
└─────────────────────────────────────────────────────────────────┘
```

**Reglas de Minimización (Paciente NO ve):**
- ❌ Notas psiquiátricas/sensibles sin consentimiento explícito
- ❌ Observaciones internas de médicos
- ❌ Auditoría interna del sistema
- ✅ Solo: Diagnósticos, medicación, citas, resultados, resumen

---

## 2. Accesibilidad (WCAG 2.1 AA)

### 2.1 Checklist por Pantalla

| Criterio                | Login                      | MFA | Admin Alta | Admin Lista | Auditoría | Paciente |
|-------------------------|----------------------------|-----|------------|-------------|-----------|----------|
| **Contraste 4.5:1**     | ✅                          | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Foco Visible**        | ✅ (outline 3px)            | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Navegación Teclado**  | ✅ (Tab order)              | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Labels ARIA**         | ✅ (email, password)        | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Mensajes Error ARIA** | ✅ (role=alert)             | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Live Regions**        | ✅ (toast)                  | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Zoom 200%**           | ✅ (responsive)             | ✅   | ✅          | ✅           | ✅         | ✅        |
| **Reducir Movimiento**  | ✅ (prefers-reduced-motion) | ✅   | ✅          | ✅           | ✅         | ✅        |

### 2.2 Implementación Técnica Requerida

```html
<!-- Ejemplo: Login Form Accesible -->
<form id="login-form" novalidate>
  <div class="form-group">
    <label for="email">Email institucional</label>
    <input type="email" 
           id="email" 
           name="email" 
           autocomplete="email"
           aria-describedby="email-hint email-error"
           aria-invalid="false"
           required>
    <span id="email-hint" class="hint">Use su email @hospital.com</span>
    <span id="email-error" class="error" aria-live="polite" hidden></span>
  </div>
  
  <div class="form-group">
    <label for="password">Contraseña</label>
    <div class="password-field">
      <input type="password" 
             id="password" 
             name="password" 
             autocomplete="current-password"
             aria-describedby="pwd-hint pwd-error pwd-strength"
             required>
      <button type="button" 
              class="toggle-visibility" 
              aria-label="Mostrar contraseña"
              aria-pressed="false">
        👁️
      </button>
    </div>
    <span id="pwd-hint" class="hint">Mín 12 chars, mayúscula, minúscula, número, símbolo</span>
    <div id="pwd-strength" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" aria-label="Fortaleza contraseña"></div>
    <span id="pwd-error" class="error" aria-live="assertive" hidden></span>
  </div>
  
  <button type="submit" class="btn-primary" disabled>
    <span class="btn-text">Iniciar Sesión</span>
    <span class="spinner" hidden aria-hidden="true"></span>
  </button>
  
  <div class="form-links">
    <a href="/forgot-password" id="forgot-link">¿Olvidó su contraseña?</a>
    <span aria-hidden="true">|</span>
    <a href="/mfa-help" id="mfa-help-link">¿Problemas con MFA?</a>
  </div>
</form>

<!-- Toast Accessible -->
<div id="toast-container" aria-live="polite" aria-atomic="true"></div>
```
