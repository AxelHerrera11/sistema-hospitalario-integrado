# Semana 11 — Prototipo Navegable: Desktop + Mobile
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## 1. Mapa de Navegación (Site Map / Flow Diagram)

```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│                           MAPA DE NAVEGACIÓN - MÓDULO TENANTS & AUTH                        │
└─────────────────────────────────────────────────────────────────────────────────────────────┘

                                    ┌─────────────────┐
                                    │   ENTRY POINT   │
                                    │  /auth/login    │
                                    └────────┬────────┘
                                             │
                          ┌──────────────────┼──────────────────┐
                          ▼                  ▼                  ▼
                    ┌───────────┐      ┌─────────────┐    ┌──────────────┐
                    │  LOGIN    │      │  MFA CHALLENGE │  │  FORGOT PASS │
                    │  (Email/  │      │  (TOTP/WebAuthn)│  │  /forgot     │
                    │  Password)│      └───────┬───────┘    └──────────────┘
                    └─────┬─────┘              │
                          │                    │
                    ┌─────┴─────┐      ┌───────┴───────┐
                    │  SUCCESS  │      │  MFA SUCCESS  │
                    └─────┬─────┘      └───────┬───────┘
                          │                    │
                          ▼                    ▼
                    ┌─────────────────────────────────────┐
                    │         DASHBOARD (Role-based)       │
                    └─────────────────┬───────────────────┘
                                      │
          ┌───────────────────────────┼───────────────────────────┐
          ▼                           ▼                           ▼
    ┌───────────────┐         ┌───────────────┐          ┌───────────────┐
    │   PACIENTE    │         │   CLÍNICO     │          │   ADMIN       │
    │  (Patient)    │         │  (Medic/Enf)  │          │  (Tenant/Plat)│
    └───────┬───────┘         └───────┬───────┘          └───────┬───────┘
            │                         │                         │
    ┌───────┴───────┐         ┌───────┴───────┐         ┌───────┴───────┐
    ▼               ▼         ▼               ▼         ▼               ▼
┌─────────┐  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│Dashboard│  │Citas/Meds│ │Pacientes │ │Expediente│ │Usuarios  │ │Tenants   │
│  Resumen│  │  /Recetas│ │  Lista   │ │  Clínico │ │  CRUD    │ │  CRUD    │
└────┬────┘  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘
     │            │            │            │            │            │
     ▼            ▼            ▼            ▼            ▼            ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         TRANSVERSAL: AUTENTICACIÓN / SESIONES               │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │
│  │ REFRESH     │  │ LOGOUT      │  │ FORCE REVOKE│  │ MFA SETUP   │       │
│  │ TOKEN       │  │ (own/admin) │  │ (admin)     │  │ (TOTP/Pass) │       │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │
└─────────────────────────────────────────────────────────────────────────────┘

     │                         │                         │
     ▼                         ▼                         ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         TRANSVERSAL: AUDITORÍA / SEGURIDAD                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐       │
│  │ AUDIT LOGS  │  │ BREAK-GLASS │  │ SESSION MGR │  │ SECURITY    │       │
│  │ (Filtros/Exp)│ │  (Emergency)│  │ (List/Revoke)│ │  ALERTS     │       │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘       │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Happy Path: Login → MFA → Dashboard → Admin → Force-Revoke

### 2.1 Flujo Completo (Pasos Numerados)

| Paso  | Pantalla                      | Acción Usuario                | Resultado                           | Validación             |
|-------|-------------------------------|-------------------------------|-------------------------------------|------------------------|
| **1** | `/auth/login`                 | Ingresa email + password      | POST /auth/login                    | 200 + tokens           |
| **2** | MFA Challenge                 | Ingresa código TOTP 6 dígitos | POST /auth/mfa                      | 200 + tokens           |
| **3** | Dashboard                     | Redirección automática        | Dashboard según rol                 | Role-based UI          |
| **4** | Admin Panel → Usuarios        | Click "Gestión Usuarios"      | Lista paginada + filtros            | TENANT_ADMIN required  |
| **5** | Usuario → [⋮] → Revocar       | Click "Revocar TODAS"         | Modal 2-paso confirmación           | PLATFORM/TENANT_ADMIN  |
| **6** | Confirmar → Motivo → Ejecutar | Click "REVOCAR AHORA"         | Toast "3 revocadas" + "Deshacer 5s" | Audit log + Event      |
| **7** | Ver Auditoría                 | Click "Auditoría"             | Tabla filtrable + export            | AUDITOR/PLATFORM_ADMIN |

---

### 2.2 Pantallas del Happy Path (Desktop + Mobile)

#### **Pantalla 1: Login Form**
```
┌─────────────────────────────────────────────────────────────────┐
│  Desktop (1024px+)                                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                    ┌─────────────────────────┐                  │
│                    │  🔐 Iniciar Sesión      │                  │
│                    │                         │                  │
│                    │  Email institucional    │                  │
│                    │  [____________________] │                  │
│                    │                         │                  │
│                    │  Contraseña        👁️ │                  │
│                    │  [____________________] │                  │
│                    │  [☐ Recordarme 30 días] │                  │
│                    │                         │                  │
│                    │  [ INICIAR SESIÓN ]     │  ← Primary btn   │
│                    │                         │                  │
│                    │  ¿Olvidó? | ¿Problemas MFA?               │
│                    │                         │                  │
│                    │  ℹ️ Tenant: Hospital Central (HCH-001)    │
│                    └─────────────────────────┘                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────┐
│  Mobile (375px)                     │
├─────────────────────────────────────┤
│                                     │
│   🔐 Iniciar Sesión                 │
│                                     │
│   Email institucional          👤   │
│  [______________________________]   │
│                                     │
│  Contraseña                   👁️    │
│  [______________________________]   │
│                                     │
│  [☐ Recordarme 30 días]             │
│                                     │
│  [ INICIAR SESIÓN ]  ← 100% width   │
│                                     │
│  ¿Olvidó? | ¿Problemas MFA?         │
│                                     │
│  ℹ️ Tenant: HCH-001                 │
└─────────────────────────────────────┘
```

#### **Pantalla 2: MFA Challenge (TOTP + WebAuthn)**
```
┌─────────────────────────────────────────────────────────────────┐
│  Desktop                                                         │
├─────────────────────────────────────────────────────────────────┤
│  🔐 Autenticación de Doble Factor Requerida                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  📱 Método: Aplicación Autenticadora (TOTP)                     │
│                                                                 │
│  1. Abra su app (Google Authenticator, Authy, etc.)            │
│  2. Ingrese el código de 6 dígitos                              │
│                                                                 │
│  Código: [ 1 ][ 2 ][ 3 ][ 4 ][ 5 ][ 6 ]  ← Auto-advance        │
│                                                                 │
│  ⏱️ Código expira en: 25s  ████████████░░░░░░░░░░░░░░░░      │
│                                                                 │
│  [ VERIFICAR CÓDIGO ]          ← Primary btn                    │
│                                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                 │
│  🔑 ¿Prefiere Passkey?                                          │
│  [ Usar FaceID / TouchID / Llave de seguridad ]                 │
│                                                                 │
│  ❓ ¿Problemas?  [ No tengo acceso a la app ]                   │
└─────────────────────────────────────────────────────────────────┘
```

#### **Pantalla 3: Dashboard Admin (Desktop 2-column Layout)**
```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│  ☰ Admin Panel          Hospital Central (HCH-001)    👤 Carlos Ruiz [≡]  [🔔 3]           │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                             │
│  ┌────────────┬──────────────────────────────────────────────────────────────────────────┐  │
│  │  SIDEBAR   │  👥 Gestión Usuarios                              [+ Nuevo]              │  │
│  │            │  ──────────────────────────────────────────────────────────────────────  │  │
│  │ 👥         │  🔍 [________________________________________]  [▼ Filtros]            │  │
│  │ 🏥         │  📅 [Exportar CSV] [Exportar PDF] [Columnas] [Densidad: Cómoda ▼]     │  │
│  │ 🔐         │  ──────────────────────────────────────────────────────────────────────  │  │
│  │ 🔐         │  ┌──┬─────────────────┬────────────────┬──────────────┬────────┬───────┐  │
│  │ 🚪         │  │☑ │ Usuario          │ Email           │ Roles        │ Estado │ ⋮   │  │
│  │           │  ├──┼─────────────────┼────────────────┼──────────────┼────────┼───────┤  │
│  │           │  │☐ │ Dra. González    │ dra.g@hch.com   │ 🩺 MEDICO    │ 🟢 Act │ ⋮   │  │
│  │           │  ├──┼─────────────────┼────────────────┼──────────────┼────────┼───────┤  │
│  │           │  │☐ │ Lic. Ruiz        │ lic.r@hch.com   │ 💉 ENFERMERO │ 🟢 Act │ ⋮   │  │
│  │           │  ├──┼─────────────────┼────────────────┼──────────────┼────────┼───────┤  │
│  │           │  │☐ │ Dr. Silva        │ dr.s@hch.com    │ 🔍 AUDITOR   │ 🟡 MFA │ ⋮   │  │
│  │           │  ├──┼─────────────────┼────────────────┼──────────────┼────────┼───────┤  │
│  │           │  │☐ │ Dra. Torres      │ adm.t@hch.com   │ 👑 ADMIN     │ 🔴 Bloq│ ⋮   │  │
│  │           │  └──┴─────────────────┴────────────────┴──────────────┴────────┴───────┘  │  │
│  │           │                                                                             │  │
│  │           │  ◄ Anterior  1  [2]  [3]  [4]  [5]  ...  [15]  Siguiente ►               │  │
│  └────────────┴──────────────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────────────────┘
```

#### **Pantalla 4: Modal Force-Revoke (2-Paso)**
```
Paso 1: Confirmar Intención
┌─────────────────────────────────────────────────────────────────┐
│  ⚠️ Confirmar Revocación                                  [×]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ¿Revocar TODAS las sesiones de <strong>Dra. Torres</strong>?    │
│                                                                 │
│  Se cerrarán sesión en TODOS los dispositivos (3 activos):     │
│    📱 iPhone 15 Pro (iOS) - Chrome 119  -  Hoy 08:30          │
│    💻 MacBook Pro (macOS) - Safari 17  -  Ayer 14:22          │
│    🔐 YubiKey 5C NFC (Passkey) -  Hace 2 horas                 │
│                                                                 │
│  El usuario deberá iniciar sesión nuevamente en todos los      │
│  dispositivos. Los tokens de acceso serán invalidados          │
│  inmediatamente y los refresh tokens eliminados.               │
│                                                                 │
│  [ Cancelar ]                    [ Continuar → ]               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘

Paso 2: Ejecutar con Motivo
┌─────────────────────────────────────────────────────────────────┐
│  🔴 Ejecutar Revocación                                   [×]  │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Motivo *:  ▼ [ Sospecha compromiso ▼ ]  [ Cambio de rol ]    │
│                                                                 │
│  Comentario (opcional):                                         │
│  [ Credenciales filtradas en GitHub gist público_____________ ] │
│                                                                 │
│  ⚠️ Deshacer disponible 5 segundos tras ejecutar               │
│                                                                 │
│  [ ← Volver ]                    [ REVOCAR AHORA ]  ← .btn-danger│
└─────────────────────────────────────────────────────────────────┘

Toast Éxito (con Deshacer):
┌─────────────────────────────────────────────────────────────────┐
│  ✅ 3 sesiones revocadas para Dra. Torres                       │
│  Motivo: Sospecha compromiso                                    │
│  [ Ver en Auditoría ]  [ Deshacer (4s) ]                        │
└─────────────────────────────────────────────────────────────────┘
```

#### **Pantalla 5: Auditoría (Desktop Table + Expandible Row)**
```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│  📋 Auditoría de Movimientos - Hospital Central                          [Exportar]         │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                             │
│  🔍 Filtros:  📅 [2026-10-01] → [2026-10-18]  👤 [__________]  🏷️ [▼ Todos]  ⚠️ [▼ Todas] │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│  ▶ 2026-10-18 10:45:00  │ sys          │ REFRESH_REUSE  │ Auth   │ 🔴 CRÍTICO  ▼         │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│  ▼ DETALLE: 🔴 REFRESH_REUSE_DETECTED - CRÍTICO                                           │
│  ──────────────────────────────────────────────────────────────────────────────────────   │
│  • Event ID: evt_7f8a...  • User: sys (service)  • Tenant: HCH-001  • IP: 192.168.1.45  │
│  • Refresh Token Reutilizado: rt_abc123...  • Trust Score: 15/100 (SOSPECHOSO)          │
│  ✅ Acciones: Revocación TOTAL + Blocklist + Alerta SIEM + Jira SEC-2026-1018-0042      │
│  ✅ Hash Chain: a1b2c3d4... → e5f6g7h8... (VERIFICADO ✅)                                │
│  [ Ver en SIEM ]  [ Crear Jira ]  [ Exportar JSON ]  [ Cerrar ]                          │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│   2026-10-18 11:20:05  │ dra.gonzalez  │ MFA_SUCCEEDED │ Auth   │ 🟢 INFO                 │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│  🟠 REVOKE_SESSION  │ adm.torres  │ Admin  │ 🟠 WARN                                                                    │
└─────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Critical Error Path: Reuse Detection → Total Revocation → Login

### 3.1 Flujo de Error Crítico

```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│                        CRITICAL ERROR PATH: REFRESH REUSE DETECTED                      │
└─────────────────────────────────────────────────────────────────────────────────────────┘

PASO 1: Usuario hace POST /auth/refresh con refresh token REUTILIZADO
         ↓
PASO 2: Backend detecta REUSE (hash no coincide con stored hash)
         ↓
PASO 3: EJECUCIÓN AUTOMÁTICA DE SEGURIDAD:
         ├─ Revocación TOTAL de TODAS las sesiones del usuario
         ├─ Blocklist de TODOS los access tokens (JTI)
         ├─ Eliminación de TODOS los refresh tokens
         ├─ Alerta CRÍTICA a SIEM (Splunk/Elastic)
         ├─ Ticket Jira auto-creado: SEC-2026-1018-XXXX
         ├─ Notificación Slack #security-alerts
         ├─ Email a Security Team
         └─ Log en auditoría inmutable (REFRESH_REUSE_DETECTED, CRITICAL)
         ↓
PASO 4: Respuesta al cliente: 401 "Token Compromised - Actividad sospechosa"
         ↓
PASO 5: Cliente muestra MODAL CRÍTICO FULL-SCREEN:
         ↓
PASO 6: Usuario FORZADO a re-login completo (nuevo MFA si corresponde)
         ↓
PASO 7: Nuevo par de tokens generado con NUEVO refresh token
         ↓
PASO 8: Auditoría registra: REFRESH_REUSE_DETECTED + LOGIN_SUCCESS (nuevo)
```

### 3.2 Pantallas del Error Crítico

#### **Pantalla E1: Refresh Request con Reuse Detection**
```
POST /auth/refresh
{ "refresh_token": "rt_compromised_xyz" }

Response: 401 Unauthorized
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

#### **Pantalla E2: Modal Crítico Full-Screen (Desktop + Mobile)**

**Desktop:**
```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│  🛑 ACTIVIDAD SOSPECHOSA DETECTADA                                                    [×] │
├─────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                         │
│  ⚠️ SE HA DETECTADO REUTILIZACIÓN DE TOKEN DE ACTUALIZACIÓN                          │
│                                                                                         │
│  Por seguridad, se han ejecutado AUTOMÁTICAMENTE las siguientes acciones:              │
│                                                                                         │
│  🔴 Revocación TOTAL de todas sus sesiones activas (3 sesiones)                       │
│  🔴 Invalidación de todos los tokens de acceso (blocklist)                             │
│  🔴 Eliminación de todos los tokens de actualización (refresh tokens)                 │
│  🔴 Alerta CRÍTICA enviada al equipo de seguridad (SIEM)                              │
│  🔴 Ticket de seguridad auto-creado: SEC-2026-1018-XXXX                               │
│                                                                                         │
│  Esta acción es IRREVERSIBLE y se ha registrado en la auditoría inmutable.            │
│                                                                                         │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐  │
│  │                          [ INICIAR SESIÓN DE NUEVO ]                            │  │
│  │                         ← Primary, full-width, focus auto                       │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                         │
│  [ Ver detalles de seguridad ]  ← Link a /security/incident/SEC-2026-1018-XXXX       │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

**Mobile (Full-screen Modal):**
```
┌─────────────────────────────────────┐
│  🛑 ACTIVIDAD SOSPECHOSA        [×] │
├─────────────────────────────────────┤
│                                     │
│  ⚠️ REUTILIZACIÓN DE TOKEN         │
│  DETECTADA                         │
│                                     │
│  Por seguridad, TODAS sus sesiones │
│  han sido REVOCADAS automáticamente│
│                                     │
│  ✅ 3 sesiones cerradas             │
│  ✅ Tokens invalidados              │
│  ✅ Alerta enviada a seguridad     │
│                                     │
│  [ INICIAR SESIÓN DE NUEVO ]       │
│  ← Full width, focus auto          │
│                                     │
│  [ Ver detalles ]                  │
└─────────────────────────────────────┘
```

#### **Pantalla E3: Login Forzado Post-Revocación**
┌─────────────────────────────────────────────────────────────────┐
│  Desktop                                                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│                    ┌─────────────────────────┐                  │
│                    │  🔐 Iniciar Sesión      │                  │
│                    │                         │                  │
│                    │  ⚠️ Sus sesiones previas │                  │
│                    │     fueron revocadas    │                  │
│                    │     por seguridad       │                  │
│                    │                         │                  │
│                    │  Email institucional    │                  │
│                    │  [____________________] │                  │
│                    │                         │                  │
│                    │  Contraseña        👁️ │                  │
│                    │  [____________________] │                  │
│                    │                         │                  │
│                    │  [ INICIAR SESIÓN ]     │                  │
│                    │                         │                  │
│                    │  ℹ️ Se requerirá MFA    │                  │
│                    │     al iniciar sesión   │                  │
│                    └─────────────────────────┘                  │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
