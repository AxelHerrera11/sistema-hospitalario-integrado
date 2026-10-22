
### Menú Acciones [⋮] (Dropdown)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  [⋮] Menú Acciones - Dra. Torres (adm.t@hch.com)          [×] (ESC)        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ✏️  Editar usuario                                                         │
│  ─────────────────────────────────────────────────────────────────────     │
│  🔑  Forzar MFA                                                           │
│  ─────────────────────────────────────────────────────────────────────     │
│  🔒  Bloquear usuario                                                      │
│  ─────────────────────────────────────────────────────────────────────     │
│  🔴  Revocar TODAS las sesiones    ← .btn-danger                          │
│  ─────────────────────────────────────────────────────────────────────     │
│  📋  Ver auditoría                                                          │
│  ─────────────────────────────────────────────────────────────────────     │
│  🗑️  Eliminar usuario (soft delete)                                       │
│  ─────────────────────────────────────────────────────────────────────     │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Modal Force-Revoke (Paso 1: Confirmar)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ⚠️ Confirmar Revocación                                            [×]    │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ⚠️ Revocar TODAS las sesiones de Dra. Torres                              │
│                                                                             │
│  Se cerrarán sesión en TODOS los dispositivos (3 activos):                │
│    📱 iPhone 15 Pro (iOS) - Chrome 119  -  Hoy 08:30                     │
│    💻 MacBook Pro (macOS) - Safari 17  -  Ayer 14:22                     │
│    🔐 YubiKey 5C NFC (Passkey) -  Hace 2 horas                            │
│                                                                             │
│  El usuario deberá iniciar sesión nuevamente en todos los                 │
│  dispositivos. Los tokens de acceso serán invalidados inmediatamente      │
│  y los refresh tokens eliminados.                                         │
│                                                                             │
│  [ Cancelar ]                    [ Continuar → ]                          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Modal Force-Revoke Paso 2: Ejecutar
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔴 Ejecutar Revocación                                             [×]    │
├─────────────────────────────────────────────────────────────────────────────┤
|                                                                             |
|  Motivo *:  ▼ [ Sospecha compromiso ▼ ]  [ Cambio de rol ]  [ Seguridad ] |
|           [ Dispositivo perdido ]  [ Cambio de rol ]  [ Otro... ]         |
|                                                                             |
|  Comentario (opcional):                                                    |
|  [ Credenciales filtradas en GitHub gist público - Acción inmediata____ ] |
|                                                                             |
|  ⚠️ Esta acción es IRREVERSIBLE. Se registrará en auditoría.             |
|                                                                             |
|  [ Cancelar ]                    [ REVOCAR AHORA ]  ← .btn-danger        |
|                                                                             |
└─────────────────────────────────────────────────────────────────────────────┘
```

### Toast Éxito con Deshacer
```
┌─────────────────────────────────────────────────────────────────┐
│  ✅ 3 sesiones revocadas para Dra. Torres                       │
│  Motivo: Sospecha compromiso                                    │
│  [ Ver en Auditoría ]  [ Deshacer (4s) ]                        │
└─────────────────────────────────────────────────────────────────┘
```

### Toast Error (Sin Permisos)
```
┌─────────────────────────────────────────────────────────────────┐
│  🔴 No tiene permisos para revocar sesiones de este usuario   │
│  Se requiere rol TENANT_ADMIN o PLATFORM_ADMIN                 │
│  [ Ver Permisos ]  [ Contactar Admin ]                          │
└─────────────────────────────────────────────────────────────────┘
```

---

## 4. Auditoría (`/audit/logs`)

### Tabla con Filtros + Fila Expandida
```
┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│  📋 Auditoría de Movimientos - Hospital Central                              [Exportar]      │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                             │
│  🔍 Filtros:  📅 [2026-10-01] → [2026-10-18]  👤 [________________________]  🏷️ [▼ Todos] │
│               🏥 Módulo: [▼ Todos]  ⚠️ Severidad: [▼ Todas] [Aplicar]                      │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                             │
│  ┌──┬────────────────────────┬─────────────┬──────────────┬────────┬────────────────────┐  │
│  │▶ │ 2026-10-18 10:45:00    │ sys         │ REFRESH_REUSE│ Auth   │ 🔴 CRÍTICO       │  │
│  ├──┼────────────────────────┼─────────────┼──────────────┼────────┼────────────────────┤  │
│  │  │  ▼ DETALLE: 🔴 REFRESH_REUSE_DETECTED - CRÍTICO                              │  │
│  │  │ ┌─────────────────────────────────────────────────────────────────────────┐   │  │
│  │  │ │  🔴 REFRESH_REUSE_DETECTED - CRÍTICO                                    │   │  │
│  │  │ │  ─────────────────────────────────────────────────────────────────────  │   │  │
│  │  │ │  📋 Información Básica:                                                 │   │  │
│  │  │ │  • Event ID: evt_7f8a9b2c-4d1e-4f3a-8b7c-9d0e1f2a3b4c                 │   │  │
│  │  │ │  • Timestamp: 2026-10-18 10:45:00.123Z                                │   │  │
│  │  │ │  • Usuario: sys (service-account)                                      │   │  │
│  │  │ │  • Tenant: Hospital Central (HCH-001)                                 │   │  │
│  │  │ │  • IP Origen: 192.168.1.45                                            │   │  │
│  │  │ │  • User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...           │   │  │
│  │  │ │  • Device ID: dev_7k9m2n1p                                            │   │  │
│  │  │ │  • Severidad: CRÍTICO                                                  │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  🔍 Detalles Técnicos:                                                 │   │  │
│  │  │ │  • Refresh Token Reutilizado: rt_abc123...                            │   │  │
│  │  │ │  • Access Token Original (JTI): jwt_eyJhbGciOiJSUzI1NiIs...           │   │  │
│  │  │ │  • Access Token Nuevo (intentado): jwt_eyJhbGciOiJSUzI1NiIs...        │   │  │
│  │  │ │  • Dispositivo: Chrome 119 / Windows 10 / 192.168.1.45               │   │  │
│  │  │ │  • Trust Score: 15/100 (SOSPECHOSO)                                   │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  ⚡ Acciones Automáticas Ejecutadas:                                    │   │  │
│  │  │ │  ✅ Revocación TOTAL de todas las sesiones del usuario (3)             │   │  │
│  │  │ │  ✅ Blocklist de todos los Access Tokens (3 JTI)                       │   │  │
│  │  │ │  ✅ Eliminación de todos los Refresh Tokens (3)                        │   │  │
│  │  │ │  ✅ Alerta CRÍTICA enviada a SIEM (Splunk)                            │   │  │
│  │  │ │  ✅ Ticket Jira auto-creado: SEC-2026-1018-0042                       │   │  │
│  │  │ │  ✅ Notificación Slack #security-alerts                                │   │  │
│  │  │ │  ✅ Notificación Email a security@hospital.com                         │   │  │
│  │  │ │  ✅ Log en auditoría inmutable (Hash Chain verificado ✅)             │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  🔗 Hash Chain Verificación:                                           │   │  │
│  │  │ │  • Hash Anterior: a1b2c3d4e5f6... (verificado ✅)                     │   │  │
│  │  │ │  • Hash Actual: e5f6g7h8i9j0... (calculado ✅)                       │   │  │
│  │  │ │  • ✅ INTEGRIDAD VERIFICADA - Sin manipulación detectada             │   │  │
│  │  │ │                                                                          │   │  │
│  │  │ │  [ Ver en SIEM ]  [ Crear Incidencia Jira ]  [ Exportar JSON ] [ Cerrar ]│  │
│  │  │ └─────────────────────────────────────────────────────────────────────────┘   │
│  ├──┼────────────────────────┼─────────────┼──────────────┼────────┼────────────┤
│  │  │ 2026-10-18 11:20:05    │ dra.gonzalez │ MFA_SUCCEEDED│ Auth   │ 🟢 INFO      │
│  └──┴────────────────────────┴─────────────┴──────────────┴────────┴────────────┘
```

### Modal Detalle Evento Crítico (Full-Screen)
```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│  🔴 REFRESH_REUSE_DETECTED - CRÍTICO                                        [×] Cerrar    │
├─────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                         │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
│  │  📋 INFORMACIÓN BÁSICA                                                          │   │
│  │  ─────────────────────────────────────────────────────────────────────────     │   │
│  │  • Event ID: evt_7f8a9b2c-4d1e-4f3a-8b7c-9d0e1f2a3b4c                         │   │
│  │  • Event Name: REFRESH_REUSE_DETECTED                                         │   │
│  │  • Severidad: CRÍTICO                                                          │   │
│  │  • Timestamp: 2026-10-18 10:45:00.123Z                                        │   │
│  │  • Correlación ID: corr_8f9b0c1d-5e2f-4a4b-9c8d-0e1f2a3b4c5d                  │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                         │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
│  │  👤 USUARIO Y TENANT                                                            │   │
│  │  ─────────────────────────────────────────────────────────────────────────     │   │
│  │  • Usuario: sys (service-account)                                             │   │
│  │  • User ID: usr_00000000-0000-0000-0000-000000000000                         │   │
│  │  • Tenant: Hospital Central (HCH-001)                                         │   │
│  │  • Tenant ID: tnt_00000000-0000-0000-0000-000000000001                       │   │
│  │  • Roles: [SYSTEM_SERVICE]                                                    │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                         │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
│  │  🔍 DETALLES TÉCNICOS                                                            │   │
│  │  ─────────────────────────────────────────────────────────────────────────     │   │
│  │  • Refresh Token Reutilizado: rt_abc123def456... (primeros 12)               │   │
│  │  • Access Token Original (JTI): jwt_eyJhbGciOiJSUzI1NiIsInR5cCI6...          │   │
│  │  • Access Token Nuevo Intentado: jwt_eyJhbGciOiJSUzI1NiIsInR5cCI6...          │   │
│  │  • Dispositivo: dev_7k9m2n1p                                                  │   │
│  │  • User Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)...                  │   │
│  │  • IP Origen: 192.168.1.45 (Geolocalización: Madrid, ES)                     │   │
│  │  • Trust Score: 15/100 (SOSPECHOSO) - Primer uso desde esta IP              │   │
│  │  • Geolocalización Previa: 192.168.1.100 (Madrid, ES) - 2h atrás            │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                         │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
│  │  ⚡ ACCIONES AUTOMÁTICAS EJECUTADAS                                             │   │
│  │  ─────────────────────────────────────────────────────────────────────────     │   │
│  │  ✅ Revocación TOTAL de todas las sesiones del usuario (3 sesiones)           │   │
│  │  ✅ Blocklist de todos los Access Tokens (3 JTI agregados)                    │   │
│  │  ✅ Eliminación de todos los Refresh Tokens (3 eliminados)                    │   │
│  │  ✅ Alerta CRÍTICA enviada a SIEM (Splunk)                                    │   │
│  │  ✅ Ticket Jira auto-creado: SEC-2026-1018-0042                              │   │
│  │  ✅ Notificación Slack #security-alerts                                       │   │
│  │  ✅ Notificación Email a security@hospital.com                                │   │
│  │  ✅ Notificación PagerDuty al on-call security engineer                      │   │
│  │  ✅ Log en auditoría inmutable (Hash Chain verificado)                        │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                         │
│  ┌─────────────────────────────────────────────────────────────────────────────────┐   │
│  │  🔗 VERIFICACIÓN INTEGRIDAD (Hash Chain)                                        │   │
│  │  ─────────────────────────────────────────────────────────────────────────     │   │
│  │  • Hash Anterior: a1b2c3d4e5f6g7h8... (verificado ✅)                         │   │
│  │  • Payload: {...}                                                               │   │
│  │  • Timestamp: 2026-10-18T10:45:00.123Z                                       │   │
│  │  • Hash Calculado: SHA256(prev_hash || payload || timestamp)                  │   │
│  │  • Hash Actual: e5f6g7h8i9j0k1l2... (calculado ✅)                            │   │
│  │  • ✅ INTEGRIDAD VERIFICADA - Sin manipulación detectada                      │   │
│  └─────────────────────────────────────────────────────────────────────────────────┘   │
│                                                                                         │
│  ─────────────────────────────────────────────────────────────────────────────────     │
│  [ Ver en SIEM ]  [ Crear Incidencia Jira ]  [ Exportar JSON ]  [ Cerrar ]          │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

---

## 5. Paciente Portal (Mobile-First)

### Dashboard Resumen (Mobile 375px)
```
┌─────────────────────────────────────┐
│  🏥 Mi Portal - María Paz    👤 [≡] │
├─────────────────────────────────────┤
│  Hola, María              [🔔 3]    │
├─────────────────────────────────────┤
│                                     │
│  ┌───────────────────────────────┐  │
│  │ 📅 PRÓXIMAS CITAS             │  │
│  │ ─────────────────────────────  │  │
│  │ 📅 2026-10-20 10:00           │  │
│  │ 🏥 Cardiología - Dr. González │  │
│  │ [ Ver ] [ 🗓️ ]               │  │
│  ├─────────────────────────────┤  │
│  │ 📅 2026-10-25 09:30          │  │
│  │ 🏥 Endocrinología - Dra. Torres│  │
│  │ [ Ver ] [ 🗓️ ]               │  │
│  └───────────────────────────────┘  │
│                                     │
│  ┌───────────────────────────────┐  │
│  │ 💊 MEDICACIÓN ACTUAL          │  │
│  │ ─────────────────────────────  │  │
│  │ 💊 Losartán 50mg              │  │
│  │ 1 comp/día - Desayuno         │  │
│  ├─────────────────────────────┤  │
│  │ 💊 Metformina 850mg           │  │
│  │ 2 comp/día - Comida/Cena      │  │
│  └───────────────────────────────┘  │
│                                     │
│  ┌───────────────────────────────┐  │
│  │ 📋 RESULTADOS RECIENTES       │  │
│  │ ─────────────────────────────  │  │
│  │ 📄 Hemograma 2026-10-15      │  │
│  │ [ Ver ]                       │  │
│  ├─────────────────────────────┤  │
│  │ 📄 Radiografía 2026-10-10    │  │
│  │ [ Ver ]                       │  │
│  └───────────────────────────────┘  │
│                                     │
│  ─────────────────────────────────  │
│  📄 Mi Expediente  |  📅 Mis Citas  │
│  💊 Medicación  |  ⚙️ Config        │
│                                     │
│  🔒 Seguridad: Último acceso 17/10  │
│  🔐 MFA: No configurado [Configurar]│
└─────────────────────────────────────┘
```

### Detalle Cita (Modal)
```
┌─────────────────────────────────────────────────────────────────┐
│  📅 Detalle de Cita                                    [×] Cerrar │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  📅 Cardiología - Consulta de Control                  │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │  📅 Fecha: 2026-10-20 (Lunes)                          │   │
│  │  🕐 Hora: 10:00 - 10:30                                 │   │
│  │  👨‍⚕️ Profesional: Dr. González (Cardiología)          │   │
│  │  🏥 Ubicación: Hospital Central - Planta 2, Consultorio│   │
│  │  🏷️ Tipo: Consulta de control post-infarto            │   │
│  │  📋 Preparación: Traer últimos análisis, medicación   │   │
│  │  📍 Ubicación: [ Ver en mapa ] [ Agregar a calendario ] │   │
│  │                                                                   │
│  │  📋 Historial: Última visita 2026-07-15 (Control post)│   │
│  │  📄 Documentos: [ Ver informe alta ] [ Ver ECG ] [ Ver analíticas ]│
│  │                                                                   │
│  │  ⚠️ Recordatorio: 24h antes (email + push)  |  1h antes (push)  │
│  │                                                                   │
│  │  ─────────────────────────────────────────────────────────  │   │
│  │  [ Cancelar cita ]  [ Reprogramar ]  [ Confirmar asistencia ] │
│  └─────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

### Seguridad y MFA Onboarding (Mobile)
```
Paso 1: Elegir Método
┌─────────────────────────────────────────────────────────────────┐
│  🔐 Configurar Autenticación Doble Factor          [×] Cerrar   │
├─────────────────────────────────────────────────────────────────┤
|                                                                 |
|  ┌─────────────────────────────────────────────────────────┐   |
|  │  📱 Aplicación Autenticadora (TOTP)                    |   |
|  │  • Google Authenticator, Authy, Microsoft Authenticator |   |
|  │  • Código de 6 dígitos, expira cada 30 segundos        |   |
|  │  • Funciona sin internet                                |   |
|  │  │                                                         |   |
|  │  │        [ CONFIGURAR TOTP ]  ← .btn-primary          |   |
|  │  └─────────────────────────────────────────────────────┘   |
|                                                                 |
|  ┌─────────────────────────────────────────────────────────┐   |
|  │  🔑 Passkey (FaceID / TouchID / Windows Hello / YubiKey)|   |
|  │  • Autenticación biométrica o llave de seguridad       |   |
|  │  • Más seguro y conveniente (sin códigos)              |   |
|  │  • Sincroniza entre dispositivos (iCloud/Google/Win)  |   |
|  │  │                                                         |   |
|  │  │        [ CONFIGURAR PASSKEY ]  ← .btn-secondary     |   |
|  │  └─────────────────────────────────────────────────────┘   |
|                                                                 |
|  ─────────────────────────────────────────────────────────────  |
|  [ Omitir por ahora ]  ← .btn-ghost (si no requerido por rol) |
└─────────────────────────────────────────────────────────────────┘

Paso 2: Escanear QR
┌─────────────────────────────────────────────────────────────────┐
│  📱 Configurar Aplicación Autenticadora            [×] Cerrar   │
├─────────────────────────────────────────────────────────────────┤
|                                                                 |
|  ┌─────────────────────────────────────────────────────────┐   |
|  │  Paso 1 de 3: Escanear código QR                        |   |
|  │  ─────────────────────────────────────────────────────  |   |
|  │                                                         |   |
|  │  1. Abra su app autenticadora (Google Authenticator,    |   |
|  │     Authy, Microsoft Authenticator, etc.)              |   |
|  │  2. Toque "+" para agregar nueva cuenta                |   |
|  │  3. Seleccione "Escanear código QR"                    |   |
|  │  4. Apunte la cámara al código de abajo                |   |
|  │                                                         |   |
|  │        ████████████████████████████████████████        |   |
|  │        ██  ┌────────────────────────────────┐  ██       |   |
|  │        ██  │  ████ ██  ████ ████ ████     │  ██       |   |
|  │        ██  │  ██  ██ ██  ██ ██  ██ ██     │  ██       |   |
|  │        ██  │  ████ ██ ████ ████ ████     │  ██       |   |
|  │        ██  │  ██  ██ ██  ██ ██  ██ ██     │  ██       |   |
|  │        ██  │  ████ ██ ████ ████ ████     │  ██       |   |
|  │        ████████████████████████████████████████        |   |
|  │                                                         |   |
|  │  🔐 Secreto manual (si no puede escanear):             |   |
|  │  [ JBSWY3DPEHPK3PXP  ]  [ Copiar ]                     |   |
|  │                                                         |   |
|  │  [ ANTERIOR ]                    [ SIGUIENTE ]         |   |
|  └─────────────────────────────────────────────────────────┘   |
└─────────────────────────────────────────────────────────────────┘
```

---

## 6. Responsive Breakpoints - Verificación Visual

| Breakpoint       | Login                          | MFA                            | Admin List                    | Force-Revoke      | Audit                            | Patient                      |
|------------------|--------------------------------|--------------------------------|-------------------------------|-------------------|----------------------------------|------------------------------|
| **320px (XS)**   | Stack 100%, botones full-width | Stack 100%, inputs 6/cada 100% | Cards stack, menú hamburguesa | Full-screen modal | Cards stack, filtros colapsables | Stack vertical, nav inferior |
| **375px (SM)**   | Centrado 400px                 | Centrado 400px                 | Cards stack, hamburger        | Full-screen modal | Cards stack                      | Stack vertical, nav inferior |
| **430px (MD)**   | Centrado 400px                 | Centrado 400px                 | 2 cols, sidebar colapsable    | Modal 480px       | Tabla responsive (scroll H)      | 2 columnas grid              |
| **768px (LG)**   | Centrado 400px max             | Centrado 400px                 | Sidebar fija + tabla          | Modal 480px       | Tabla completa                   | 3-4 cols grid                |
| **1024px (XL)**  | Centrado 400px max             | Centrado 400px                 | Layout completo sidebar       | Modal 480px       | Tabla completa, panel detalle    | 3-4 cols, sidebar fija       |
| **1440px (XXL)** | Centrado 400px max             | Centrado 400px                 | Dashboard paneles             | Modal 480px       | Tabla ancha, panel detalle       | 4 cols, sidebar fija         |

