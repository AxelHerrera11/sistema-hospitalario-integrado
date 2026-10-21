# Wireframe 06: Paciente - Portal (Vista Resumen)
## Estado: DASHBOARD RESUMEN (Desktop)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🏥 Mi Portal - María Paz                               👤  [Cerrar Sesión]│
│  ─────────────────────────────────────────────────────────────────────────  │
│  Hola, María                                                      [🔔 3]    │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  ┌─────────────────────────────┐ ┌─────────────────────────────┐ ┌───────┐  │
│  │ 📅 PRÓXIMAS CITAS          │ │ 💊 MEDICACIÓN ACTUAL        │ │ 📋 RES│  │
│  │ ─────────────────────────── │ │ ─────────────────────────── │ │ ULTAD │  │
│  │ 📅 2026-10-20 10:00         │ │ 💊 Losartán 50mg            │ │ ──────│ │
│  │ 🏥 Cardiología - Dr. González│ │    1 comp/día - Desayuno    │ │ 📄 Hem│ │
│  │    [ Ver detalle ] [ 🗓️ ]   │ │                             │ │ ograma│ │
│  │ ─────────────────────────── │ │ ─────────────────────────── │ │ 2026- │ │
│  │ 📅 2026-10-25 09:30         │ │ 💊 Metformina 850mg         │ │ 10-15 │ │
│  │ 🏥 Endocrinología - Dra. Torres│ │    2 comp/día - Comida/Cena │ │ Ver ▶ │ │
│  │    [ Ver detalle ] [ 🗓️ ]   │ │                             │ │       │ │
│  │ ─────────────────────────── │ │ ─────────────────────────── │ │ ──────│ │
│  │ [ Ver todas las citas ▶ ]   │ │ [ Ver toda medicación ▶ ]   │ │ 📄 Rad│ │
│  └─────────────────────────────┘ └─────────────────────────────┘ │ iogr. │ │
│                                                                     │ 2026- │ │
│  ───────────────────────────────────────────────────────────────── │ 10-10 │ │
│                                                                     │ Ver ▶ │ │
│  📄 Mi Expediente  |  📅 Mis Citas  |  💊 Medicación  |  ⚙️ Config   │       │ │
│                                                                     └───────┘ │
│  🔒 Seguridad: Último acceso 2026-10-17 19:30  |  [Ver actividad]             │
│  🔐 MFA: No configurado  |  [Configurar MFA]                                  │
└─────────────────────────────────────────────────────────────────────────────┘

Reglas Minimización Datos (Paciente):
✅ VISIBLE: Diagnósticos, Medicación, Citas, Resultados, Resumen clínico
❌ OCULTO: Notas psiquiátricas/sensibles (requiere consentimiento explícito)
❌ OCULTO: Observaciones internas de médicos
❌ OCULTO: Auditoría interna del sistema
❌ OCULTO: Notas de enfermería sensibles
✅ VISIBLE: Solo resumen de alta, medicación activa, citas próximas, resultados recientes
```

## Estado: CARD DETALLE CITA (Click "Ver detalle")
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  📅 Detalle de Cita                                    [×] Cerrar           │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  📅 Cardiología - Consulta de Control                              │     │
│  │  ─────────────────────────────────────────────────────────────────  │    │
│  │  📅 Fecha: 2026-10-20 (Lunes)                                      │    │
│  │  🕐 Hora: 10:00 - 10:30                                            │    │
│  │  👨‍⚕️ Profesional: Dr. González (Cardiología)                        │    │
│  │  🏥 Ubicación: Hospital Central - Planta 2, Consultorio 204        │    │
│  │  🏷️ Tipo: Consulta de control post-infarto                         │    │
│  │  📋 Preparación: Traer últimos análisis, medicación actual         │    │
│  │  📍 Ubicación: [ Ver en mapa ] [ Agregar a calendario ]            │    │
│  │                                                                     │    │
│  │  📋 Historial: Última visita 2026-07-15 (Control post-infarto)     │    │
│  │  📄 Documentos: [ Ver informe alta ] [ Ver ECG ] [ Ver analíticas ]│    │
│  │                                                                     │    │
│  │  ⚠️ Recordatorio: 24h antes (email + push)  |  1h antes (push)     │    │
│  │                                                                     │   │
│  │  ─────────────────────────────────────────────────────────────────  │   │
│  │  [ Cancelar cita ]  [ Reprogramar ]  [ Confirmar asistencia ]       │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                            │
└────────────────────────────────────────────────────────────────────────────┘
```

## Estado: SEGURIDAD Y ACTIVIDAD

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔒 Seguridad y Actividad                                      [×] Cerrar  │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  🕐 Último Acceso                                                   │   │
│  │  ─────────────────────────────────────────────────────────────────  │    │
│  │  🕐 Último login: 2026-10-17 19:30:15                               │   │
│  │  📱 Dispositivo: iPhone 14 (iOS 17.1) - Safari                      │   │
│  │  📍 Ubicación: Madrid, España (IP: 192.168.1.100)                   │   │
│  │  ⏱️ Duración sesión: 45 min                                         │   │
│  │                                                                     │    │
│  │  📊 Historial Reciente (últimos 7 días):                            │   │
│  │  • 2026-10-17 19:30 - iPhone 14 - Madrid - 45 min  ✅               │   │
│  │  • 2026-10-16 08:15 - iPhone 14 - Madrid - 12 min  ✅               │   │
│  │  • 2026-10-15 14:30 - Chrome/Windows - Madrid - 8 min  ✅           │   │
│  │  • 2026-10-14 09:00 - iPhone 14 - Barcelona - 5 min  ⚠️ Nuevo       │   │
│  │                                                                     │    │
│  │  [ Ver historial completo ▶ ]  [ Reportar actividad sospechosa ]    │   │
│  └─────────────────────────────────────────────────────────────────────┘    │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐    │
│  │  🔐 Autenticación de Doble Factor (MFA)                            │    │
│  │  ─────────────────────────────────────────────────────────────────  │    │
│  │  Estado: ❌ No configurado                                         │    │
│  │                                                                     │    │
│  │  ⚠️ Recomendado: Active MFA para proteger su cuenta               │   │
│  │                                                                     │   │
│  │  ┌─────────────────────────────────────────────────────────────┐   │   │
│  │  │  [ Configurar TOTP (App Autenticadora) ]  📱               │   │   │
│  │  │  [ Configurar Passkey (FaceID/TouchID) ]  🔑               │   │   │
│  │  └─────────────────────────────────────────────────────────────┘   │   │
│  │                                                                     │   │
│  │  ℹ️ La MFA añade una capa extra de seguridad. Incluso si alguien  │   │
│  │  obtiene su contraseña, necesitará su dispositivo para entrar.   │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │  🔔 Dispositivos de Confianza                                       │   │
│  │  ─────────────────────────────────────────────────────────────────  │   │
│  │  📱 iPhone 14 (iOS 17.1)        │  🟢 Confiable  │  Hoy 19:30  [⋮] │   │
│  │  💻 Chrome/Windows 11           │  🟢 Confiable  │  Ayer 14:30 [⋮] │   │
│  │  📱 iPad Air (iPadOS 17)        │  🟡 Poco uso   │  Hace 3 días [⋮] │   │
│  │                                                                     │   │
│  │  [ Revocar dispositivo ]  [ Renombrar ]  [ Ver detalles ]          │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```
## Estado: CONFIGURAR MFA (Onboarding)
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔐 Configurar Autenticación Doble Factor                  [×] Cerrar       │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  🔐 Elija su método preferido                                      │   │
│   │  ─────────────────────────────────────────────────────────────────  │   │
│   │                                                                     │   │
│   │  ┌─────────────────────────────────────────────────────────────┐   │   │
│   │  │  📱 Aplicación Autenticadora (TOTP)                         │   │   │
│   │  │  • Google Authenticator, Authy, Microsoft Authenticator    │   │   │
│   │  │  • Código de 6 dígitos, expira cada 30 segundos           │   │   │
│   │  │  • Funciona sin internet                                   │   │   │
│   │  │  │                                                         │   │   │
│   │  │  │        [ CONFIGURAR TOTP ]  ← .btn-primary             │   │   │
│   │  │  └─────────────────────────────────────────────────────────┘   │   │
│   │  ┌─────────────────────────────────────────────────────────────┐   │   │
│   │  │  🔑 Passkey (FaceID / TouchID / Windows Hello / YubiKey)   │   │   │
│   │  │  • Autenticación biométrica o llave de seguridad          │   │   │
│   │  │  • Más seguro y conveniente (sin códigos)                  │   │   │
│   │  │  • Sincroniza entre dispositivos (iCloud/Google/Windows)  │   │   │
│   │  │  │                                                         │   │   │
│   │  │  │        [ CONFIGURAR PASSKEY ]  ← .btn-secondary        │   │   │
│   │  │  └─────────────────────────────────────────────────────────┘   │   │
│   │  ─────────────────────────────────────────────────────────────────  │   │
│   │  [ Omitir por ahora ]  ← .btn-ghost (si no requerido por rol)    │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```
## Estado: CONFIGURAR TOTP (Paso a Paso)
```
Paso 1: Escanear QR
┌─────────────────────────────────────────────────────────────────────────────┐
│  📱 Configurar Aplicación Autenticadora                    [×] Cerrar       │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  Paso 1 de 3: Escanear código QR                                   │   │
│   │  ─────────────────────────────────────────────────────────────────  │   │
│   │                                                                     │   │
│   │  1. Abra su app autenticadora (Google Authenticator, Authy, etc.)  │   │
│   │  2. Toque "+" para agregar nueva cuenta                            │   │
│   │  3. Seleccione "Escanear código QR"                                │   │
│   │  4. Apunte la cámara al código de abajo                            │   │
│   │                                                                     │   │
│   │        ████████████████████████████████████████                   │   │
│   │        ██  ┌────────────────────────────────┐  ██                   │   │
│   │        ██  │  ████ ██  ████ ████ ████     │  ██                   │   │
│   │        ██  │  ██  ██ ██  ██ ██  ██ ██     │  ██                   │   │
│   │        ██  │  ████ ██ ████ ████ ████     │  ██                   │   │
│   │        ██  │  ██  ██ ██  ██ ██  ██ ██     │  ██                   │   │
│   │        ██  │  ████ ██ ████ ████ ████     │  ██                   │   │
│   │        ████████████████████████████████████████                   │   │
│   │                                                                     │   │
│   │  🔐 Secreto manual (si no puede escanear):                         │   │
│   │  [ JBSWY3DPEHPK3PXP  ]  [ Copiar ]                                  │   │
│   │                                                                     │   │
│   │  [ ANTERIOR ]                    [ SIGUIENTE ]  ← .btn-primary     │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘

Paso 2: Verificar Código
┌─────────────────────────────────────────────────────────────────────────────┐
│  📱 Verificar Código                                       [×] Cerrar       │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  Paso 2 de 3: Ingrese código de 6 dígitos                          │   │
│   │  ─────────────────────────────────────────────────────────────────  │   │
│   │                                                                     │   │
│   │  Ingrese el código que muestra su app autenticadora:              │   │
│   │                                                                     │   │
│   │        [ 1 ][ 2 ][ 3 ][ 4 ][ 5 ][ 6 ]  ◄ Auto-advance            │   │
│   │                                                                     │   │
│   │  ⏱️ Código expira en: 28 segundos  ████████████░░░░░░░░░░░░░░░░  │   │
│   │                                                                     │   │
│   │  [ ANTERIOR ]                    [ VERIFICAR ]  ← .btn-primary     │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘

Paso 3: Completado + Códigos Recuperación
┌─────────────────────────────────────────────────────────────────────────────┐
│  ✅ MFA Configurado Exitosamente                            [×] Cerrar       │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  ✅ Autenticación TOTP activada correctamente                      │   │
│   │  ─────────────────────────────────────────────────────────────────  │   │
│   │                                                                     │   │
│   │  🔐 Códigos de Recuperación (Guárdelos en lugar seguro):          │   │
│   │  ─────────────────────────────────────────────────────────────────  │   │
│   │  1.  A1B2-C3D4    2.  E5F6-G7H8    3.  I9J0-K1L2                  │   │
│   │  4.  M3N4-O5P6    5.  Q7R8-S9T0    6.  U1V2-W3X4                  │   │
│   │  7.  Y5Z6-A7B8    8.  C9D0-E1F2    9.  G3H4-I5J6                  │   │
│   │  10. K7L8-M9N0                                                    │   │
│   │                                                                     │   │
│   │  ⚠️ IMPORTANTE: Guarde estos códigos en lugar seguro.             │   │
│   │  Cada código solo se puede usar UNA VEZ.                          │   │
│   │  Si pierde acceso a su app, use uno de estos códigos.            │   │
│   │                                                                     │   │
│   │  [ Descargar PDF ]  [ Copiar al portapapeles ]  [ Imprimir ]      │   │
│   │                                                                     │   │
│   │  [ CONFIGURAR PASSKEY TAMBIÉN ]  [ FINALIZAR ]  ← .btn-primary     │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```
## Responsive: Mobile (320px) - Dashboard Apilado
```
┌─────────────────────────────────────┐
│  🏥 Mi Portal - María Paz    👤 [≡] │
│  ─────────────────────────────────  │
│  Hola, María              [🔔 3]    │
│  ─────────────────────────────────  │
│  ┌───────────────────────────────┐  │
│  │ 📅 PRÓXIMAS CITAS             │  │
│  │ ─────────────────────────────  │  │
│  │ 📅 2026-10-20 10:00           │  │
│  │ 🏥 Cardiología - Dr. González │  │
│  │ [ Ver ] [ 🗓️ ]               │  │
│  ├─────────────────────────────┤  │
│  │ 📅 2026-10-25 09:30          │  │
│  │ 🏥 Endocrinología - Dra. Torres│ │
│  │ [ Ver ] [ 🗓️ ]               │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ 💊 MEDICACIÓN ACTUAL          │  │
│  │ ─────────────────────────────  │  │
│  │ 💊 Losartán 50mg              │  │
│  │ 1 comp/día - Desayuno         │  │
│  ├─────────────────────────────┤  │
│  │ 💊 Metformina 850mg           │  │
│  │ 2 comp/día - Comida/Cena      │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ 📋 RESULTADOS RECIENTES       │  │
│  │ ─────────────────────────────  │  │
│  │ 📄 Hemograma 2026-10-15      │  │
│  │ [ Ver ]                      │  │
│  ├─────────────────────────────┤  │
│  │ 📄 Radiografía 2026-10-10    │  │
│  │ [ Ver ]                      │  │
│  └───────────────────────────────┘  │
│  ─────────────────────────────────  │
│  📄 Expediente | 📅 Citas | 💊 Meds │
│  ─────────────────────────────────  │
│  🔒 Último acceso: 17/10 19:30      │
│  🔐 MFA: No configurado [Configurar]│
└─────────────────────────────────────┘

Navegación Inferior (Fixed):
┌─────────────────────────────────────┐
│ 📄 Expediente  📅 Citas  💊 Meds  ⚙️ │
└─────────────────────────────────────┘
```
## Responsive: Tablet (768px) - 2 Columnas
```
┌────────────────────────────────────────────────────────────────┐
│  🏥 Mi Portal - María Paz                    👤 [Cerrar] [🔔 3] │
│  ────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────┐ ┌──────────────────────────────┐  │
│  │ 📅 PRÓXIMAS CITAS       │ │ 💊 MEDICACIÓN ACTUAL         │  │
│  │ ─────────────────────── │ │ ──────────────────────────── │  │
│  │ 📅 20/10 10:00 Cardio   │ │ 💊 Losartán 50mg 1/día      │  │
│  │ Dr. González [Ver][🗓️] │ │ 💊 Metformina 850mg 2/día    │  │
│  │ ─────────────────────── │ │                             │  │
│  │ 📅 25/10 09:30 Endo    │ │ [Ver toda medicación ▶]      │  │
│  │ Dra. Torres [Ver][🗓️]  │ │                             │  │
│  └─────────────────────────┘ └──────────────────────────────┘  │
│  ┌─────────────────────────┐ ┌──────────────────────────────┐  │
│  │ 📋 RESULTADOS RECIENTES │ │ 🔒 SEGURIDAD                 │  │
│  │ ─────────────────────── │ │ ──────────────────────────── │  │
│  │ 📄 Hemograma 15/10 [Ver]│ │ 🕐 Último: 17/10 19:30      │  │
│  │ 📄 Radiografía 10/10 [V]│ │ 📱 iPhone 14 🟢 Hoy 19:30   │  │
│  │                         │ │ 🔐 MFA: No configurado       │  │
│  │ [Ver todos ▶]           │ │ [Configurar MFA]             │  │
│  └─────────────────────────┘ └──────────────────────────────┘  │
│  📄 Expediente  📅 Citas  💊 Medicación  ⚙️ Config              │
└────────────────────────────────────────────────────────────────┘
```
## Mermaid para draw.io/Figma
```mermaid
flowchart TD
    subgraph "Patient Portal - Navegación"
        HOME[Dashboard Resumen\n4 Cards: Citas, Medicación, Resultados, Seguridad]
        APPOINTMENTS[Mis Citas\nLista + Filtros + Detalle]
        MEDICATION[Medicación\nLista + Detalle + Recordatorios]
        RECORDS[Expediente/Resultados\nCategorías + Búsqueda]
        SECURITY[Seguridad\nActividad + MFA + Dispositivos]
        SETTINGS[Configuración\nNotificaciones, Privacidad, Accesibilidad]
        
        HOME --> APPOINTMENTS
        HOME --> MEDICATION
        HOME --> RECORDS
        HOME --> SECURITY
        HOME --> SETTINGS
    end
    
    subgraph "Flujo MFA Onboarding"
        MFA_START[Click "Configurar MFA"]
        MFA_CHOICE{Elegir Método}
        TOTP_SETUP[TOTP Setup\n1. QR → 2. Código → 3. Recovery Codes]
        WEBAUTHN_SETUP[WebAuthn Setup\nRegistrar Dispositivo]
        MFA_DONE[MFA Activo\nRecovery Codes Guardados]
        
        MFA_START --> MFA_CHOICE
        MFA_CHOICE -->|TOTP| TOTP_SETUP
        MFA_CHOICE -->|Passkey| WEBAUTHN_SETUP
        TOTP_SETUP --> MFA_DONE
        WEBAUTHN_SETUP --> MFA_DONE
    end
    
    subgraph "Seguridad - Minimización Datos"
        VISIBLE[✅ VISIBLE\nDiagnósticos, Medicación, Citas\nResultados, Resumen]
        HIDDEN[❌ OCULTO\nNotas psiquiátricas\nNotas internas médicos\nAuditoría sistema]
        
        VISIBLE -.-> PATIENT
        HIDDEN -.-> PATIENT
    end
    
    style HOME fill:#E3F2FD,stroke:#1565C0
    style TOTP_SETUP fill:#FFF3E0,stroke:#E65100
    style MFA_DONE fill:#E8F5E9,stroke:#2E7D32
    style VISIBLE fill:#E8F5E9,stroke:#2E7D32
    style HIDDEN fill:#FFEBEE,stroke:#C62828
```
## Accesibilidad Específica Paciente
| Criterio               | Implementación                                        |
|------------------------|-------------------------------------------------------|
| **Contraste Cards**    | 4.5:1 mínimo (borde #E0E0E0, fondo #FFFFFF)           |
| **Focus Visible**      | `outline: 3px solid #1976D2` en cards, botones, links |
| **Navegación Teclado** | Tab: Nav → Cards → Botones → Footer nav               |
| **Screen Reader**      | `aria-label` en cards, `aria-expanded` en acordeones  |
| **Zoom 200%**          | Cards se apilan verticalmente, sin scroll horizontal  |
| **Reducir Movimiento** | Desactiva animaciones slide/flip en cards             |
| **Alto Contraste**     | Modo Windows High Contrast / macOS Increase Contrast  |
| **Tamaño Texto**       | `rem` units, respeta preferencia usuario              |

## Métricas UX Paciente (KPIs)
| Métrica                      | Objetivo                | Medición                                    |
|------------------------------|-------------------------|---------------------------------------------|
| **Tasa Adopción MFA**        | > 60% pacientes activos | (pacientes_con_mfa / pacientes_activos)     |
| **Tasa Cancelación Cita**    | < 5%                    | (citas_canceladas / citas_programadas)      |
| **Tiempo Configuración MFA** | < 3 min                 | Timestamp inicio → recovery codes guardados |
| **Satisfacción Portal**      | > 4.0/5                 | Encuesta trimestral (NPS)                   |
| **Acceso Expediente**        | > 80% pacientes/mes     | Unique users / total pacientes              |
| **Accesibilidad**            | 0 violaciones AA        | axe-core CI + testing manual                |

