# Semana 11 — Especificación del prototipo (parte 1: navegación y flujos)
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** acceso por hospital, administración de usuarios y revocación de sesión
**Versión:** 1.1 | **Fecha:** 2026-10-21

> **Alcance.** Especificación de diseño con prototipo **estático** (mockups ASCII y Mermaid) más
> un prototipo navegable **parcial** en el SPA Vue (`/prototipos/tenants-responsive`, Semana 10).
> Cada flujo indica qué existe en `develop` **[Implementado]** y qué es diseño **[Propuesto]**.
> Roles: códigos literales de `config/rbac.php`. La parte 2 (consistencia, stack y plan de
> pruebas) está en [`prototype-spec1.md`](prototype-spec1.md).

---

## 1. Mapa de navegación

```
                         ┌──────────────────────┐
                         │  /login [Impl.]      │
                         │  X-Tenant-ID + correo│
                         │  + contraseña        │
                         └──────────┬───────────┘
                                    │
                 ┌──────────────────┴──────────────────┐
                 ▼                                     ▼
       ┌───────────────────┐                ┌────────────────────┐
       │ MFA TOTP [Prop.]  │                │ Error / sin red     │
       │ (existe en el     │                │ [Impl.]             │
       │ micro-HIS sem. 3) │                └────────────────────┘
       └─────────┬─────────┘
                 ▼
       ┌──────────────────────────────────────────────┐
       │  Inicio (/) [Impl.] — menú según permisos     │
       └───────┬──────────────────┬───────────────────┘
               │                  │
               ▼                  ▼
     ┌──────────────────┐  ┌──────────────────────────┐
     │ Módulos clínicos │  │ /admin/users [Impl.]      │
     │ (otros equipos)  │  │ usuarios.ver              │
     └──────────────────┘  └────────────┬─────────────┘
                                         │
              ┌──────────────┬───────────┼────────────┬───────────────┐
              ▼              ▼           ▼            ▼               ▼
        ┌──────────┐  ┌────────────┐ ┌──────────┐ ┌───────────┐ ┌────────────┐
        │Asignar / │  │ Desactivar │ │ Cerrar   │ │ Alta de   │ │ Auditoría  │
        │quitar rol│  │ [Impl.]    │ │ 1 sesión │ │ usuario   │ │ [Prop.]    │
        │ [Impl.]  │  │            │ │ [Impl.]  │ │ [Prop.]   │ │auditoria.ver│
        └──────────┘  └────────────┘ └──────────┘ └───────────┘ └────────────┘

  Transversal [Impl.]: refresh por cookie (POST /api/v1/auth/refresh), logout,
  sesión expirada → evento auth:session-expired → /login.
  Superadmin [Impl.]: alta de hospital (POST /api/v1/admin/tenants, tenants.crear).
```

Se retiraron de la v1.0: recuperación de contraseña, break-glass, gestión global de sesiones,
alertas de seguridad y portal de paciente. Ninguno tiene contrato en `develop`; quedan como
trabajo futuro, fuera de este prototipo.

---

## 2. Camino feliz: acceso → usuarios → cerrar una sesión

| Paso | Pantalla | Acción | Contrato | Permiso / rol | Estado |
|---|---|---|---|---|---|
| 1 | `/login` | Ingresa UUID del hospital, correo y contraseña | `POST /api/v1/auth/login` + `X-Tenant-ID` | — | [Implementado] |
| 2 | MFA | Código TOTP de 6 dígitos | `POST /auth/mfa` del micro-HIS sem. 3 | `admin_hospital`, `superadmin` | [Propuesto en el HIS] |
| 3 | `/` | Menú según permisos de la sesión | `GET /api/v1/auth/me` | — | [Implementado] |
| 4 | `/admin/users` | Abre la lista de usuarios del hospital | `GET /api/v1/admin/users` | `usuarios.ver` | [Implementado] |
| 5 | Acciones → Cerrar una sesión | Confirma en el modal | `DELETE /api/v1/admin/sessions/{session}` | `sesiones.revocar_ajenas` | [Implementado] |
| 6 | Resultado | Aviso «Sesión cerrada» | — | — | [Implementado] |
| 7 | Auditoría | Consulta el evento | — | `auditoria.ver` | [Propuesto] |

Diferencias con la v1.0:
- No hay «Deshacer» ni «restaurar sesiones». Una revocación invalida los tokens y no se revierte;
  el usuario recupera el acceso volviendo a iniciar sesión.
- El paso 5 cierra **una** sesión, que es lo que existe. «Cerrar todas» queda [Propuesto].

### 2.1 Pantallas del camino feliz (escritorio)

Las versiones móviles están en `prototype-screens-mobile.md` y `prototype-screens-mobile1.md`.

**Pantalla 1 — Inicio de sesión [Implementado]**
```
┌─────────────────────────────────────────────────────────────────┐
│  Hospital HIS                               Inicio  Acceso      │
├─────────────────────────────────────────────────────────────────┤
│                 ┌───────────────────────────────┐               │
│                 │  Iniciar sesión               │               │
│                 │  ID del hospital (X-Tenant-ID)│               │
│                 │  [___________________________]│               │
│                 │  Correo                       │               │
│                 │  [___________________________]│               │
│                 │  Contraseña             [Ver] │               │
│                 │  [___________________________]│               │
│                 │  [          ENTRAR          ] │               │
│                 └───────────────────────────────┘               │
└─────────────────────────────────────────────────────────────────┘
```

**Pantalla 2 — Usuarios del hospital [Implementado]**
```
┌─────────────────────────────────────────────────────────────────────────────┐
│  Hospital HIS · Hospital Central             Inicio  Usuarios  Salir        │
├─────────────────────────────────────────────────────────────────────────────┤
│  Usuarios del hospital                                                      │
│  ┌──────────────────┬──────────────────────┬───────────────────┬──────────┐ │
│  │ Usuario          │ Correo               │ Roles             │ Acciones │ │
│  ├──────────────────┼──────────────────────┼───────────────────┼──────────┤ │
│  │ Dra. Ana González│ ana.gonzalez@hch.test│ medico            │  [▾]     │ │
│  │ Lic. Pedro Ruiz  │ pedro.ruiz@hch.test  │ enfermeria        │  [▾]     │ │
│  │ Carlos Méndez    │ carlos.m@hch.test    │ recepcion (inact.)│  [▾]     │ │
│  └──────────────────┴──────────────────────┴───────────────────┴──────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

**Pantalla 3 — Cerrar una sesión (confirmación) [Implementado]**
```
┌─────────────────────────────────────────────────────────────────┐
│  Cerrar sesión de Dra. Ana González                       [×]   │
├─────────────────────────────────────────────────────────────────┤
│  Sesión: Chrome · Windows · iniciada 21/10/2026 08:30           │
│                                                                 │
│  El dispositivo deberá volver a iniciar sesión. La acción no    │
│  se puede deshacer y queda registrada.                          │
│                                                                 │
│                          [ Cancelar ]   [ CERRAR SESIÓN ]       │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Camino de error crítico: reutilización de refresh token

### 3.1 Comportamiento esperado

| Paso | Qué ocurre | Estado en `develop` |
|---|---|---|
| 1 | El cliente envía `POST /api/v1/auth/refresh` con un refresh **ya consumido** (robado o repetido) | [Implementado] (`RefreshUserSession`) |
| 2 | El backend lo reconoce como reutilización | [Implementado]: `consumed_at` ya definido |
| 3 | Revoca toda la familia de refresh tokens y la sesión asociada | [Implementado]: `revokeReusedFamily()` |
| 4 | Registra `auth.refresh_reuse_detected` con severidad `critical` | [Implementado] |
| 5 | Responde 401 `REFRESH_TOKEN_REUSED`; el SPA limpia la sesión y vuelve a `/login` | [Implementado]: evento `auth:session-expired` en `app.js` |
| 6 | Cerrar también las **demás** sesiones del usuario en el hospital | [Propuesto]: hoy solo cae la sesión de esa familia (el micro-HIS sem. 3 sí las cierra todas) |

Se retiraron de la v1.0 las acciones que no existen y que no se pueden afirmar como automáticas:
blocklist en Redis, ticket Jira, aviso por Slack, correo al equipo de seguridad y reenvío al SIEM.

### 3.2 Respuesta del API (vigente)
```
HTTP/1.1 401 Unauthorized
{
  "error": "REFRESH_TOKEN_REUSED",
  "message": "Refresh token reutilizado; la familia de sesiones fue revocada."
}
```
El SPA no debe mostrar este texto técnico: muestra el aviso E1. La v1.0 proponía devolver cuántas
sesiones y tokens se revocaron; se descartó porque le sirve a un atacante y no al usuario.

### 3.3 Pantallas del error crítico

**E1 — Aviso de seguridad (móvil y escritorio) [Propuesto]**
```
┌─────────────────────────────────────┐
│  Sesión cerrada por seguridad       │  ← role="alertdialog", foco inicial
├─────────────────────────────────────┤
│  Detectamos un uso repetido de su   │
│  credencial de sesión. Por          │
│  seguridad cerramos esta sesión.    │
│                                     │
│  [   INICIAR SESIÓN DE NUEVO   ]    │  ← 100 % ancho en móvil
│                                     │
│  Si no fue usted, avise a la        │
│  administración del hospital.       │
└─────────────────────────────────────┘
```

**E2 — Inicio de sesión tras la revocación [Implementado: pantalla de login]**
```
┌─────────────────────────────────────┐
│  Iniciar sesión                     │
│  ┌───────────────────────────────┐  │
│  │ ℹ Sus sesiones anteriores se  │  │  ← mensaje propuesto
│  │ cerraron por seguridad.       │  │
│  └───────────────────────────────┘  │
│  ID del hospital  [26adadaf-…]      │
│  Correo           [____________]    │
│  Contraseña       [____________]    │
│  [          ENTRAR          ]       │
└─────────────────────────────────────┘
```

---

## 4. Trazabilidad con los demás archivos de Semana 11

| Archivo | Relación |
|---|---|
| `prototype-navigation-map.mmd` | Diagrama del mapa de la sección 1 |
| `prototype-happy-path.md` / `.mmd` | Detalle del camino feliz de la sección 2 |
| `prototype-error-path.md` / `.mmd` | Detalle del error crítico de la sección 3 |
| `prototype-screens-desktop*.md` | Pantallas de escritorio |
| `prototype-screens-mobile*.md` | Pantallas móviles (v1.1, sin duplicados) |
| `prototype-consistency-checklist.md` | Revisión de consistencia de diseño |

Los documentos anteriores de esta carpeta (v1.0) todavía mencionan «Deshacer» y roles en
mayúsculas. Ante cualquier diferencia prevalece esta especificación v1.1.
