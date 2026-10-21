# Wireframe 04: Admin - Lista Usuarios + Force-Revoke
## Estado: LISTA USUARIOS (Desktop)
┌─────────────────────────────────────────────────────────────────────────────┐
│  👥 Gestión de Usuarios - Hospital Central                    [+ Nuevo]      │
│  ─────────────────────────────────────────────────────────────────────────  │
│  🔍 Buscar: [________________________________________]  ▼ Filtros          │
│  📅 [Exportar CSV]  [Exportar PDF]  [Columnas]  [Densidad: Cómoda ▼]      │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌──┬──────────────────┬────────────────┬──────────────┬────────┬─────────┐ │
│  │☑ │ Usuario          │ Email          │ Roles        │ Estado │ Acciones│ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Dra. González    │ dra.g@hch.com  │ 🩺 MEDICO    │ 🟢 Act │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Lic. Ruiz        │ lic.r@hch.com  │ 💉 ENFERMERO │ 🟢 Act │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Dr. Silva        │ dr.s@hch.com   │ 🔍 AUDITOR   │ 🟡 MFA │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Dra. Torres      │ adm.t@hch.com  │ 👑 ADMIN     │ 🔴 Bloq│ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Enf. Martínez    │ enf.m@hch.com  │ 💉 ENFERMERO │ 🟢 Act │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Dr. López        │ dr.l@hch.com   │ 🩺 MEDICO    │ 🟡 MFA │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Adm. García      │ adm.g@hch.com  │ 👑 ADMIN     │ 🟢 Act │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Enf. González    │ enf.g@hch.com  │ 💉 ENFERMERO │ 🟢 Act │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Dr. Martín       │ dr.m@hch.com   │ 🩺 MEDICO    │ 🟢 Act │ [⋮] ▼  │ │
│  ├──┼──────────────────┼────────────────┼──────────────┼────────┼─────────┤ │
│  │☐ │ Lic. Fernández   │ lic.f@hch.com  │ 💉 ENFERMERO │ 🟡 MFA │ [⋮] ▼  │ │
│  └──┴──────────────────┴────────────────┴──────────────┴────────┴─────────┘ │
│  ◄ Anterior  1  [2]  [3]  [4]  [5]  ...  [15]  Siguiente ►   [50 por pág] │
│  📊 Total: 147 usuarios  |  Activos: 142  |  Bloqueados: 3  |  MFA: 12    │
└─────────────────────────────────────────────────────────────────────────────┘
Interacciones:
- Click fila: Selección múltiple (checkbox) + highlight row
- Click [⋮]: Abre menú dropdown (Keyboard: Enter/Space, Escape cierra)
- Header checkbox: Seleccionar todo (página actual)
- Columnas: Resizable, reordenables, ocultables
- Filtros: Sidebar colapsable (Roles, Estado, MFA, Fecha creación)
## Estado: MENÚ ACCIONES [⋮] (Dropdown)
┌─────────────────────────────────────────────────────────────────────────────┐
│  [⋮] Menú Acciones - Dra. Torres (adm.t@hch.com)          [×] (ESC)        │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  ✏️  Editar usuario                                              │   │
│   │  ─────────────────────────────────────────────────────────────    │   │
│   │  🔑  Forzar MFA                                                  │   │
│   │  ─────────────────────────────────────────────────────────────    │   │
│   │  🔒  Bloquear usuario                                            │   │
│   │  ─────────────────────────────────────────────────────────────    │   │
│   │  🔴  Revocar TODAS las sesiones    ← .btn-danger                │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│  Navegación Teclado:                                                       │
│  - Tab/Shift+Tab: Navegar items                                            │
│  - Enter/Space: Ejecutar acción                                            │
│  - Escape: Cerrar menú                                                     │
│  - Flechas ↑↓: Navegar items                                               │
│  - Home/End: Primer/Último item                                            │
└─────────────────────────────────────────────────────────────────────────────┘
## Estado: MODAL FORCE-REVOKE (Confirmación Peligrosa)
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔴 Revocar Sesiones - Dra. Torres                    [×] Cerrar (ESC)      │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│   │  ⚠️ Revocar TODAS las sesiones de Dra. Torres                     │   │
│   │                                                                     │   │
│   │  Se cerrarán sesión en TODOS los dispositivos (3 activos):        │   │
│   │   📱 iPhone 15 Pro (iOS) - Chrome 119  -  Hoy 08:30               │   │
│   │   💻 MacBook Pro (macOS) - Safari 17  -  Ayer 14:22               │   │
│   │  🔐 YubiKey 5C NFC (Passkey) -  Hace 2 horas                       │   │
│   │                                                                     │   │
│   │  El usuario deberá iniciar sesión nuevamente en todos los          │   │
│   │  dispositivos. Los tokens de acceso serán invalidados inmediatamente│   │
│   │  y los refresh tokens eliminados.                                 │   │
│   │                                                                     │   │
│   │  Motivo *:                                                          │   │
│   │   ▼ [ Seguridad ▼ ]  [ Cambio de rol ]  [ Sospecha compromiso ]   │   │
│   │         [ Dispositivo perdido ]  [ Cambio de rol ]  [ Otro... ]    │   │
│   │                                                                     │   │
│   │  Comentario (opcional):                                            │   │
│   │  [____________________________________________________________]    │   │
│   │                                                                     │   │
│   │  ⚠️ Esta acción es IRREVERSIBLE. Se registrará en auditoría.      │   │
│   └─────────────────────────────────────────────────────────────────────┘   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  [ Cancelar ]                    [ REVOCAR SESIONES ]  ← .btn-danger      │
└─────────────────────────────────────────────────────────────────────────────┘
Focus Management:
- Focus inicial: Primer input (Motivo dropdown)
- Tab: Motivo → Comentario → Cancelar → Revocar
- Escape: Cierra modal
- Tab Trap: Cicla dentro del modal
- Focus visible: Outline 3px #1976D2
## Estado: TOAST RESULTADO (Éxito Force-Revoke)
┌─────────────────────────────────────────────────────────────────────────────┐
│  🟢 Toast: 3 sesiones revocadas para Dra. Torres                           │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ✅ 3 sesiones revocadas para Dra. Torres                                  │
│  Motivo: Seguridad                                                         │
│  [ Ver en Auditoría ]  [ Deshacer (5s) ]                                   │
│  ─────────────────────────────────────────────────────────────────────────  │
└─────────────────────────────────────────────────────────────────────────────┘
Auto-dismiss: 5 segundos (pausa en hover)
Accesibilidad: role="status" aria-live="polite"
## Estado: TOAST ERROR (Sin Permisos)
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔴 Toast: No tiene permisos para revocar sesiones de este usuario         │
│  ─────────────────────────────────────────────────────────────────────────  │
│  🔴 No tiene permisos para revocar sesiones de este usuario               │
│  Se requiere rol TENANT_ADMIN o PLATFORM_ADMIN                            │
│  [ Ver Permisos ]  [ Contactar Admin ]                                     │
└─────────────────────────────────────────────────────────────────────────────┘
## Responsive: Tablet (768px) - Lista Compacta
┌────────────────────────────────────────────────────────────────┐
│  👥 Gestión Usuarios - HCH          [+]  [☰]                  │
│  ────────────────────────────────────────────────────────────  │
│  🔍 [______________________]  [▼ Filtros]                     │
│  ────────────────────────────────────────────────────────────  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Dra. González          dra.g@hch.com   🩺 MEDICO  🟢 [⋮]  │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ Lic. Ruiz              lic.r@hch.com   💉 ENFERMERO 🟢 [⋮] │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ Dr. Silva              dr.s@hch.com    🔍 AUDITOR  🟡 [⋮] │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ Dra. Torres            adm.t@hch.com   👑 ADMIN   🔴 [⋮]  │ │
│  ├────────────────────────────────────────────────────────────┤ │
│  │ ...                                                        │ │
│  └────────────────────────────────────────────────────────────┘ │
│  [◄] 1 [2] [3] [4] [5] [►]    50/pág                            │
└────────────────────────────────────────────────────────────────┘
## Responsive: Mobile (320px) - Cards Apiladas
┌─────────────────────────────────────┐
│  👥 Usuarios HCH        [+] [☰]     │
│  ─────────────────────────────────  │
│  🔍 [___________________] [▼]       │
│  ─────────────────────────────────  │
│  ┌───────────────────────────────┐  │
│  │ 👤 Dra. González              │  │
│  │ 📧 dra.g@hch.com              │  │
│  │ 🏷️ MEDICO        🟢 Activo    │  │
│  │ ─────────────────────────     │  │
│  │ [⋮] Acciones                  │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ 👤 Lic. Ruiz                  │  │
│  │ 📧 lic.r@hch.com              │  │
│  │ 🏷️ ENFERMERO       🟢 Activo  │  │
│  │ ─────────────────────────     │  │
│  │ [⋮] Acciones                  │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ 👤 Dr. Silva                  │  │
│  │ 📧 dr.s@hch.com               │  │
│  │ 🏷️ AUDITOR        🟡 MFA      │  │
│  │ ─────────────────────────     │  │
│  │ [⋮] Acciones                  │  │
│  └───────────────────────────────┘  │
│  ◄ 1 [2] [3] [4] [5] ►   20/pág    │
└─────────────────────────────────────┘
## Mermaid para draw.io/Figma
mermaid
flowchart TD
    subgraph "Lista Usuarios - Estados"
        LIST[Lista Usuarios\n+ Filtros + Paginación]
        ROW_CLICK[Click Fila\n→ Selección]
        ACTION_MENU[Click [⋮]\n→ Dropdown]
        LIST --> ROW_CLICK
        LIST --> ACTION_MENU
    end
    subgraph "Menú Acciones"
        MENU[Dropdown [⋮]]
        EDIT[✏️ Editar]
        MFA[🔑 Forzar MFA]
        BLOCK[🔒 Bloquear]
        REVOKE[🔴 Revocar Sesiones]
        MENU --> EDIT
        MENU --> MFA
        MENU --> BLOCK
        MENU --> REVOKE
    end
    subgraph "Force-Revoke Flow"
        REVOKE --> CONFIRM_MODAL[Modal Confirmación\n⚠️ Peligroso]
        CONFIRM_MODAL --> USER_CANCEL[Cancelar]
        CONFIRM_MODAL --> USER_CONFIRM[Confirmar + Motivo]
        USER_CONFIRM --> EXECUTE[Ejecutar Revocación]
        EXECUTE --> SUCCESS[Toast Éxito\n3 sesiones revocadas]
        EXECUTE --> ERROR[Toast Error\nSin permisos]
        SUCCESS --> REFRESH_LIST[Actualizar Lista]
    end
    style REVOKE fill:#FFEBEE,stroke:#C62828
    style CONFIRM_MODAL fill:#FFF3E0,stroke:#E65100
    style SUCCESS fill:#E8F5E9,stroke:#2E7D32
