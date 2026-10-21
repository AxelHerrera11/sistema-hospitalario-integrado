### 3.2 Modal Crítico Full-Screen (Desktop)

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

**Mobile - Full Screen Modal:**
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
│  [ INICIAR SESIÓN DE NUEVO ]       │
│  ← Full width, focus auto          │
│                                     │
│  [ Ver detalles ]                  │
└─────────────────────────────────────┘
```

---

### 3.3 Login Forzado Post-Revocación

```
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
```

---

## 4. Estados de UI - Consistencia Visual

### 4.1 Botones - Estados Definidos
| Estado       | Primary            | Secondary          | Danger             | Ghost              | Loading |
|--------------|--------------------|--------------------|--------------------|--------------------|---------|
| **Default**  | `#1976D2`          | `#FFF` + border    | `#C62828`          | Transparent        | Spinner |
| **Hover**    | `#1565C0`          | `#F5F5F5`          | `#B71C1C`          | `#E3F2FD`          | -       |
| **Focus**    | Ring 3px `#1976D2` | Ring 3px `#1976D2` | Ring 3px `#C62828` | Ring 3px `#1976D2` | -       |
| **Active**   | `#0D47A1`          | `#EEEEEE`          | `#B71C1C`          | `#BBDEFB`          | -       |
| **Disabled** | `#90CAF9`          | `#E0E0E0`          | `#EF9A9A`          | `#BBDEFB`          | Spinner |
| **Loading**  | Spinner inline     | Spinner inline     | Spinner inline     | Spinner inline     | Spinner |

### 4.2 Inputs - Estados
| Estado       | Border          | Background | Icon/Label             |
|--------------|-----------------|------------|------------------------|
| **Default**  | `#E0E0E0`       | `#FFFFFF`  | Label normal           |
| **Focus**    | `#1976D2` (3px) | `#FFFFFF`  | Label + hint           |
| **Error**    | `#C62828` (2px) | `#FFF3E0`  | Label + ⚠️ + error msg |
| **Success**  | `#2E7D32` (2px) | `#E8F5E9`  | Label + ✅              |
| **Disabled** | `#E0E0E0`       | `#F5F5F5`  | Label + disabled       |
| **Loading**  | `#1976D2`       | `#FFFFFF`  | Spinner en botón       |

### 4.3 Toast Notifications
| Tipo         | Color Border | Icon | Duration   | Action       |
|--------------|--------------|------|------------|--------------|
| **Success**  | `#2E7D32`    | ✅    | 3s         | -            |
| **Error**    | `#C62828`    | 🔴   | 5s         | [Reintentar] |
| **Warning**  | `#ED6C02`    | ⚠️   | 5s         | [Acción]     |
| **Critical** | `#B71C1C`    | 🛑   | Persistent | [Acción]     |
| **Info**     | `#1976D2`    | ℹ️   | 4s         | -            |

---

## 5. Accesibilidad - Verificación por Componente

| Componente     | 1.4.3 Contraste | 2.1.1 Teclado | 2.4.7 Foco | 3.3.1 Error ID | 4.1.2 ARIA           | Estado          |
|----------------|-----------------|---------------|------------|----------------|----------------------|-----------------|
| Login Form     | ✅ 4.5:1         | ✅ Tab/Enter   | ✅ 3px ring | ✅ aria-invalid | ✅ aria-describedby   | ✅ Pass          |
| MFA Challenge  | ✅               | ✅             | ✅          | ✅              | ✅ aria-live="polite" | ✅ Pass          |
| Admin Create   | ✅               | ✅             | ✅          | ✅              | ❌ H-005              | ⚠️ Fix Sprint 2 |
| Admin List     | ✅               | ✅             | ✅          | ✅              | ❌ H-003, H-005       | ⚠️ Fix Sprint 2 |
| Force-Revoke   | ✅               | ✅             | ✅          | ✅              | ✅                    | ✅ Pass          |
| Audit Logs     | ✅               | ✅             | ❌ H-004    | ✅              | ❌ H-003              | ⚠️ Fix Sprint 2 |
| Critical Modal | ✅               | ✅ (trap)      | ✅ (auto)   | ✅              | ✅                    | ✅ Pass          |
| Patient Portal | ✅               | ✅             | ✅          | ✅              | ✅                    | ✅ Pass          |

---

## 6. Responsive Breakpoints - Verificación

| Componente       | XS (320px)    | SM (375px)    | MD (430px)    | LG (768px)      | XL (1024px)     |
|------------------|---------------|---------------|---------------|-----------------|-----------------|
| **Login**        | Stack 100%    | Center 400px  | Center 400px  | Center 400px    | Center 400px    |
| **MFA**          | Stack 100%    | Center 400px  | Center 400px  | Center 400px    | Center 400px    |
| **Admin List**   | Cards stack   | Cards stack   | 2 cols        | Sidebar + Table | Sidebar + Table |
| **Force-Revoke** | Full-screen   | Full-screen   | Modal 480px   | Modal 480px     | Modal 480px     |
| **Audit**        | Cards stack   | Cards stack   | Scroll H      | Full Table      | Full Table      |
| **Admin Create** | Stepper 1-col | Stepper 1-col | Stepper 1-col | Stepper 1-col   | Stepper 1-col   |

---

## 7. Métricas de Validación (KPIs)

| Métrica                    | Objetivo         | Happy Path | Error Crítico           |
|----------------------------|------------------|------------|-------------------------|
| **Tiempo Total**           | < 15s / < 45s    | ~12s       | ~25s (incluye re-login) |
| **Tasa Éxito 1er Intento** | > 95%            | 98%        | N/A (forzado)           |
| **Errores Críticos**       | 0                | 0          | 0 (manejados)           |
| **Recuperación Error**     | < 15s            | N/A        | < 10s (con Deshacer)    |
| **Accesibilidad (axe)**    | 0 violaciones AA | 0          | 0                       |
| **Touch Targets**          | ≥ 44px           | ✅          | ✅                       |
| **Font Size**              | ≥ 16px           | ✅          | ✅                       |

---

## 8. Entregables del Prototipo (Listos para Handoff)

| Archivo                              | Tipo     | Descripción                              |
|--------------------------------------|----------|------------------------------------------|
| `prototype-spec.md`                  | Markdown | Especificación completa (este documento) |
| `prototype-navigation-map.mmd`       | Mermaid  | Mapa navegación completo                 |
| `prototype-happy-path.mmd`           | Mermaid  | Happy Path flow                          |
| `prototype-error-path.mmd`           | Mermaid  | Critical Error Path                      |
| `prototype-happy-path.md`            | Markdown | Happy Path screenshots/descripciones     |
| `prototype-error-path.md`            | Markdown | Error Path screenshots/descripciones     |
| `prototype-screens-desktop.md`       | Markdown | Desktop screenshots                      |
| `prototype-screens-mobile.md`        | Markdown | Mobile screenshots                       |
| `prototype-consistency-checklist.md` | Markdown | Roles/Estados/Accesibilidad              |
| `prototype-tech-spec.md`             | Markdown | Tech spec completa                       |

---

## 9. Handoff a Desarrollo (Checklist)

| Entregable                    | Estado        | Responsable       | Fecha     |
|-------------------------------|---------------|-------------------|-----------|
| Design Tokens (JSON)          | ✅ Listo       | Design            | Semana 11 |
| Component Library (Storybook) | ✅ Publicado   | Design + Frontend | Semana 11 |
| Figma Prototype Links         | ✅ Compartidos | Design            | Semana 11 |
| Zeroheight/Zeplin Specs       | ✅ Publicados  | Design            | Semana 11 |
| Copy/Strings (i18n keys)      | ✅ Aprobados   | UX Writer         | Semana 11 |
| Accessibility Annotations     | ✅ En diseños  | Design + A11y     | Semana 11 |
| Responsive Breakpoints        | ✅ Definidos   | Design + Frontend | Semana 11 |
| Animation Specs               | ✅ Definidos   | Design + Frontend | Semana 11 |
