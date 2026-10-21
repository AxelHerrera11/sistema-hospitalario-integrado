# Semana 11 — Prototipo Navegable: Desktop + Mobile
## 1. Resumen del Prototipo
### Objetivo
Prototipo navegable (Desktop + Mobile) que cubre:
- **Happy Path**: Login → MFA → Dashboard → Admin → Force-Revoke
- **Critical Error Path**: Refresh Reuse Detection → Revocación Total → Forced Re-login

### Alcance del Prototipo
| Flujo             | Pantallas    | Interacciones     | Estados                        |
|-------------------|--------------|-------------------|--------------------------------|
| **Happy Path**    | 12 pantallas | 25+ interacciones | Loading, Success, Error, Empty |
| **Error Crítico** | 5 pantallas  | 8 interacciones   | Critical, Loading, Recovery    |

## 2. Happy Path - Detalle Paso a Paso
### Paso 1: Login (`/auth/login`)
**Desktop (1024px+)**
```
┌─────────────────────────────────────────────────────────────────┐
│  🏥 SISTEMA HOSPITALARIO INTEGRADO                              │
├─────────────────────────────────────────────────────────────────┤
│                    ┌─────────────────────────┐                  │
│                    │  🔐 Iniciar Sesión      │                  │
│                    │  Tenant: Hospital Central ▼              │
│                    │  [_________________________________]      │
│                    │  Email institucional 👤                  │
│                    │  [dra.gonzalez@hospital.com________]      │
│                    │  Contraseña              👁️              │
│                    │  [●●●●●●●●●●●••••••••]  │                  │
│                    │  [☐ Recordarme 30 días]                   │
│                    │  ┌─────────────────────┐                 │
│                    │  │   [ INICIAR SESIÓN ]  │ ← Primary     │
│                    │  └─────────────────────┘                 │
│                    │  ¿Olvidó su contraseña? | ¿Problemas MFA? │
│                    │  ℹ️ Tenant: Hospital Central (HCH-001)    │
│                    └───────────────────────────────────────────┘
└─────────────────────────────────────────────────────────────────┘
```
**Mobile (375px)**
```
┌─────────────────────────────────────┐
│  🏥 SISTEMA HOSPITALARIO            │
├─────────────────────────────────────┤
│   ┌───────────────────────────────┐ │
│   │  🔐 Iniciar Sesión            │ │
│   │  Tenant: Hospital Central ▼  │ │
│   │  [_________________________]  │ │
│   │  Email institucional     👤   │ │
│   │  [_________________________]  │ │
│   │  Contraseña             👁️    │ │
│   │  [_________________________]  │ │
│   │  [☐ Recordarme 30 días]       │ │
│   │  [ INICIAR SESIÓN ] ← 100%    │ │
│   │  ¿Olvidó? | ¿Problemas MFA?   │ │
│   │  ℹ️ Tenant: HCH-001           │ │
│   └───────────────────────────────┘ │
└─────────────────────────────────────┘
```
**Interacciones:**
- [x] Email validation inline (onBlur)
- [x] Password toggle visibility (👁️)
- [x] Tenant selector (auto-skip si solo 1 tenant)
- [x] FaceID/TouchID autofill (mobile)
- [x] Loading state en submit (spinner + "Validando...")
- [x] Error inline + toast (credenciales inválidas, cuenta bloqueada)

### Paso 2: MFA Challenge (`/auth/mfa`)
**Desktop**
```
┌─────────────────────────────────────────────────────────────────┐
│  🔐 Autenticación de Doble Factor Requerida                     │
├─────────────────────────────────────────────────────────────────┤
│   ┌─────────────────────────────────────────────────────────┐   │
│   │  📱 Método: Aplicación Autenticadora (TOTP)             │   │
│   │  1. Abra su app (Google Authenticator, Authy, etc.)    │   │
│   │  2. Ingrese el código de 6 dígitos                     │   │
│   │  Código: [ 1 ][ 2 ][ 3 ][ 4 ][ 5 ][ 6 ] ◄ Auto-advance  │   │
│   │  ⏱️ Código expira en: 25s  ████████████░░░░░░░░░░░░░░  │   │
│   │  [ VERIFICAR CÓDIGO ]        ← Primary btn             │   │
│   │  ─────────────────────────────────────────────────────  │   │
│   │  🔑 ¿Prefiere Passkey?                                  │   │
│   │  [ Usar FaceID / TouchID / Llave de seguridad ]        │   │
│   │  ❓ ¿Problemas?  [ No tengo acceso a la app ]          │   │
│   └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```
**Mobile (375px)**
```
┌─────────────────────────────────────┐
│  🔐 Autenticación 2FA              │
├─────────────────────────────────────┤
│  📱 App Autenticadora (TOTP)       │
│  1. Abra su app                    │
│  2. Ingrese código 6 dígitos       │
│  Código: [1][2][3][4][5][6]        │
│  ← 6 inputs 100% width c/u         │
│  ⏱️ Expira en: 25s ████████░░░░░░  │
│  [ VERIFICAR CÓDIGO ]              │
│  ← 100% width                      │
│  ─────────────────────────────────  │
│  🔑 ¿Prefiere Passkey?             │
│  [ Usar FaceID / TouchID ]         │
│  ❓ ¿Problemas?                    │
│  [ No tengo acceso a la app ]      │
└─────────────────────────────────────┘
```
### Paso 3: Dashboard Role-Based
#### Admin Dashboard (Desktop - 2 Columnas)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ☰ Admin Panel          Hospital Central (HCH-001)    👤 Carlos Ruiz [≡]  [🔔 3]│
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌────────────┬──────────────────────────────────────────────────────────┐  │
│  │  SIDEBAR   │  👥 Gestión Usuarios                              [+ Nuevo]  │
│  │            │  ──────────────────────────────────────────────────────  │  │
│  │ 👥         │  🔍 [________________________________________]  [▼ Filtros]│  │
│  │ 🏥         │  📅 [Exportar CSV] [Exportar PDF] [Columnas] [Densidad: ▼]│  │
│  │ 🔐         │  ──────────────────────────────────────────────────────  │  │
│  │ 🔐         │  ┌──┬─────────────────┬────────────────┬────────────┬────┐│
│  │ 🚪         │  │☑ │ Usuario          │ Email           │ Roles      │ Est │ ⋮ │
│  │            │  ├──┼─────────────────┼────────────────┼────────────┼────┼───┤
│  │            │  │☐ │ Dra. González    │ dra.g@hch.com   │ 🩺 MEDICO  │ 🟢  │ ⋮ │
│  │            │  ├──┼─────────────────┼────────────────┼────────────┼────┼───┤
│  │            │  │☐ │ Lic. Ruiz        │ lic.r@hch.com   │ 💉 ENFERMERO│ 🟢 │ ⋮ │
│  │            │  ├──┼─────────────────┼────────────────┼────────────┼────┼───┤
│  │            │  │☐ │ Dr. Silva        │ dr.s@hch.com    │ 🔍 AUDITOR │ 🟡  │ ⋮ │
│  │            │  ├──┼─────────────────┼────────────────┼────────────┼────┼───┤
│  │            │  │☐ │ Dra. Torres      │ adm.t@hch.com   │ 👑 ADMIN   │ 🔴  │ ⋮ │
│  │            │  └──┴─────────────────┴────────────────┴────────────┴────┴───┘  │
│  │            │                                                                 │
│  │            │  ◄ Anterior  1  [2]  [3]  [4]  [5]  ...  [15]  Siguiente ►     │
│  └────────────┴────────────────────────────────────────────────────────────────┘
└─────────────────────────────────────────────────────────────────────────────┘
```

**Mobile - Cards Stack**
```
┌─────────────────────────────────────┐
│  👥 Usuarios HCH          [+] [☰]   │
├─────────────────────────────────────┤
│  🔍 [___________________] [▼]       │
├─────────────────────────────────────┤
│                                     │
│  ┌───────────────────────────────┐ │
│  │ 👤 Dra. González              │ │
│  │ 📧 dra.g@hch.com              │ │
│  │ 🏷️ MEDICO        🟢 Activo    │ │
│  │ ─────────────────────────     │ │
│  │ [⋮] Acciones                  │ │
│  └───────────────────────────────┘ │
│                                     │
│  ┌───────────────────────────────┐ │
│  │ 👤 Lic. Ruiz                  │ │
│  │ 📧 lic.r@hch.com              │ │
│  │ 🏷️ ENFERMERO       🟢 Activo   │ │
│  └───────────────────────────────┘ │
│                                     │
│  ◄ 1 [2] [3] [4] [5] ►   20/pág   │
└─────────────────────────────────────┘
```

---

### Paso 4: Force-Revoke (Modal 2-Paso)

#### Paso 1: Confirmar Intención
```
┌─────────────────────────────────────────────────────────────────┐
│  ⚠️ Confirmar Revocación                                  [×]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ¿Revocar TODAS las sesiones de <strong>Dra. Torres</strong>?   │
│                                                                 │
│  Se cerrarán sesión en TODOS los dispositivos (3 activos):    │
│    📱 iPhone 15 Pro (iOS) - Chrome 119  -  Hoy 08:30          │
│    💻 MacBook Pro (macOS) - Safari 17  -  Ayer 14:22          │
│    🔐 YubiKey 5C NFC (Passkey) -  Hace 2 horas                 │
│                                                                 │
│  El usuario deberá iniciar sesión nuevamente en todos los      │
│  dispositivos. Los tokens de acceso serán invalidados          │
│  inmediatamente y los refresh tokens eliminados.              │
│                                                                 │
│  [ Cancelar ]                    [ Continuar → ]               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

#### Paso 2: Ejecutar con Motivo
```
┌─────────────────────────────────────────────────────────────────┐
│  🔴 Ejecutar Revocación                                   [×]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Motivo *:  ▼ [ Sospecha compromiso ▼ ]  [ Cambio de rol ]    │
│                                                                 │
│  Comentario (opcional):                                         │
│  [ Credenciales filtradas en GitHub gist público____________ ] │
│                                                                 │
│  ⚠️ Deshacer disponible 5 segundos tras ejecutar              │
│                                                                 │
│  [ ← Volver ]                    [ REVOCAR AHORA ] ← .btn-danger│
└─────────────────────────────────────────────────────────────────┘
```

#### Toast Éxito con Deshacer
```
┌─────────────────────────────────────────────────────────────────┐
│  ✅ 3 sesiones revocadas para Dra. Torres                       │
│  Motivo: Sospecha compromiso                                    │
│  [ Ver en Auditoría ]  [ Deshacer (4s) ]                        │
└─────────────────────────────────────────────────────────────────┘
```

---

### Paso 5: Auditoría (`/audit/logs`)

**Desktop - Tabla + Fila Expandida**
```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│  📋 Auditoría de Movimientos - Hospital Central                              [Exportar]      │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                             │
│  🔍 Filtros:  📅 [2026-10-01] → [2026-10-18]  👤 [__________]  🏷️ [▼ Todos]  ⚠️ [▼ Todas] │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                             │
│  ┌──┬────────────────────────┬─────────────┬──────────────┬────────┬────────────────────┐  │
│  │▶ │ 2026-10-18 10:45:00    │ sys         │ REFRESH_REUSE│ Auth   │ 🔴 CRÍTICO       │  │
│  ├──┼────────────────────────┼─────────────┼──────────────┼────────┼────────────────────┤  │
│  │  │  ▼ DETALLE: 🔴 REFRESH_REUSE_DETECTED - CRÍTICO                               │  │
│  │  │ ┌─────────────────────────────────────────────────────────────────────────┐   │  │
│  │  │ │  🔴 REFRESH_REUSE_DETECTED - CRÍTICO                                    │   │  │
│  │  │ │  ─────────────────────────────────────────────────────────────────────  │   │  │
│  │  │ │  📋 Información Básica:                                                  │   │  │
│  │  │ │  • Event ID: evt_7f8a9b2c-4d1e-4f3a-8b7c-9d0e1f2a3b4c                  │   │  │
│  │  │ │  • Timestamp: 2026-10-18 10:45:00.123Z                                 │   │  │
│  │  │ │  • Usuario: sys (service-account)                                      │   │  │
│  │  │ │  • Tenant: Hospital Central (HCH-001)                                  │   │  │
│  │  │ │  • IP Origen: 192.168.1.45                                             │   │  │
│  │  │ │  • User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...           │   │  │
│  │  │ │  • Device ID: dev_7k9m2n1p                                            │   │  │
│  │  │ │  • Severidad: CRÍTICO                                                  │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  🔍 Detalles Técnicos:                                                 │   │  │
│  │  │ │  • Refresh Token Reutilizado: rt_abc123...                            │   │  │
│  │  │ │  • Access Token Original (JTI): jwt_eyJhbGciOiJSUzI1NiIs...          │   │  │
│  │  │ │  • Access Token Nuevo (intentado): jwt_eyJhbGciOiJSUzI1NiIs...        │   │  │
│  │  │ │  • Dispositivo: Chrome 119 / Windows 10 / 192.168.1.45               │   │  │
│  │  │ │  • Trust Score: 15/100 (SOSPECHOSO)                                   │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  ⚡ Acciones Automáticas Ejecutadas:                                    │   │  │
│  │  │ │  ✅ Revocación TOTAL de todas las sesiones del usuario (3)            │   │  │
│  │  │ │  ✅ Blocklist de todos los Access Tokens (3 JTI)                       │   │  │
│  │  │ │  ✅ Eliminación de todos los Refresh Tokens (3)                        │   │  │
│  │  │ │  ✅ Alerta CRÍTICA enviada a SIEM (Splunk)                            │   │  │
│  │  │ │  ✅ Ticket Jira auto-creado: SEC-2026-1018-0042                       │   │  │
│  │  │ │  ✅ Notificación Slack #security-alerts                                │   │  │
│  │  │ │  ✅ Notificación Email a security@hospital.com                         │   │  │
│  │  │ │  ✅ Log en auditoría inmutable (Hash Chain verificado ✅)             │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  🔗 Hash Chain Verificación:                                           │   │  │
│  │  │ │  • Hash Anterior: a1b2c3d4e5f6... (verificado ✅)                    │   │  │
│  │  │ │  • Hash Actual: e5f6g7h8i9j0... (calculado ✅)                       │   │  │
│  │  │ │  • ✅ INTEGRIDAD VERIFICADA - Sin manipulación detectada             │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  [ Ver en SIEM ]  [ Crear Incidencia Jira ]  [ Exportar JSON ] [ Cerrar ]│  │
│  │  │ └─────────────────────────────────────────────────────────────────────────┘   │
│  ├──┼────────────────────────┼─────────────┼──────────────┼────────┼──────────────┤
│  │  │ 2026-10-18 11:20:05  │ dra.gonzalez │ MFA_SUCCEEDED │ Auth  │ 🟢 INFO      │
│  └──┴────────────────────────┴─────────────┴──────────────┴────────┴──────────────┘
```

---

## 3. Critical Error Path - Pantallas

### 3.1 Refresh Reuse Detection (POST /auth/refresh)

**Request:**
```http
POST /auth/refresh
Content-Type: application/json
{ "refresh_token": "rt_compromised_xyz" }
```

**Response - 401 Unauthorized:**
```json
{
  "success": false,
  "error": {
    "code": "REFRESH_REUSE_DETECTED",
    "message": "Actividad sospechosa detectada. Por seguridad, TODAS sus sesiones han sido revocadas.",
    "severity": "CRITICAL",
    "action_required": "RELOGIN_REQUIRED",
    "revoked_sessions": 3,
    "blocked_access_tokens": 3
  }
}
```
