# Semana 10 — Diseño para Movilidad

**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación

**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»

**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## 1. Estrategia Mobile-First

### 1.1 Principios de Diseño Mobile

| Principio                  | Aplicación en Módulo Tenants & Auth                                             |

|----------------------------|---------------------------------------------------------------------------------|

| **Content First**          | Priorizar: Login, MFA, Revocación sesión. Secundario: Admin usuarios, Auditoría |

| **Progressive Disclosure** | Login → MFA → Dashboard. Admin: Lista → Detalle → Acciones                      |

| **Touch-First**            | Touch targets ≥ 44px, gestos swipe, pull-to-refresh                             |

| **Offline-First**          | Cache credenciales (biométrico), sync pendiente al reconectar                   |

| **Performance**            | Lazy load, code splitting, service worker, < 3s TTI                             |

### 1.2 Breakpoints Definidos

| Breakpoint | Ancho       | Dispositivos                                    | Layout Principal                    |

|------------|-------------|-------------------------------------------------|-------------------------------------|

| **XS**     | 320-374px   | iPhone SE, Android small                        | Stack 1 col, full-width buttons     |

| **SM**     | 375-429px   | iPhone 12/13/14/15, Android standard            | Stack 1 col, 400px max container    |

| **MD**     | 430-767px   | iPhone Plus/Max, Android large, Tablet portrait | 2 cols (sidebar colapsable)         |

| **LG**     | 768-1023px  | Tablet landscape, small laptop                  | Sidebar fija + content              |

| **XL**     | 1024-1439px | Desktop standard                                | Layout completo, sidebar fija       |

| **XXL**    | 1440px+     | Desktop wide, 4K                                | Max-width 1400px, paneles laterales |

### 1.3 Container Queries (Modern Approach)

```css

*/\* Container-based responsive (más flexible que media queries) \*/*

@container (min-width: 320px) {

  .auth-container { padding: 16px; max-width: 100%; }

  .btn-primary { width: 100%; min-height: 48px; }

}

@container (min-width: 480px) {

  .auth-container { padding: 24px; max-width: 400px; margin: 0 auto; }

  .btn-primary { width: auto; min-width: 200px; }

}

@container (min-width: 768px) {

  .admin-layout { display: grid; grid-template-columns: 260px 1fr; }

  .sidebar { position: sticky; top: 0; height: 100vh; }

}

@container (min-width: 1024px) {

  .audit-table { display: table; }

  .audit-card { display: none; }

}

```

---

## 2. Jerarquización de Contenido y Acciones (Mobile)

### 2.1 Matriz de Prioridad por Pantalla

| Pantalla                 | Prioridad 1 (Siempre visible)                       | Prioridad 2 (Accesible 1 tap)   | Prioridad 3 (Menú/Overflow)       | Oculto en Mobile                                |

|--------------------------|-----------------------------------------------------|---------------------------------|-----------------------------------|-------------------------------------------------|

| **Login**                | Email, Password, Botón Login, Tenant selector       | MFA help, Forgot password       | Tenant switcher (si multi-tenant) | -                                               |

| **MFA Challenge**        | Código input, Countdown, Verify btn                 | Switch TOTP/WebAuthn, Resend    | Recovery codes link               | QR code (opcional, tap para ampliar)            |

| **Admin Lista Usuarios** | Nombre, Email, Rol badge, Estado, Acciones [⋮]      | Filtros (colapsable), Buscar    | Exportar, Columnas, Densidad      | Columnas secundarias (Último login, MFA status) |

| **Admin Alta Usuario**   | Email, Nombre, Contraseña, Roles, Crear             | Password strength, MFA checkbox | Tenant selector (si Platform)     | -                                               |

| **Force-Revoke Modal**   | Advertencia, Dispositivos list, Motivo, Revocar     | Comentario, Cancelar            | -                                 | -                                               |

| **Auditoría**            | Evento, Usuario, Timestamp, Severidad               | Filtros (colapsable), Exportar  | Detalle expandible                | Payload completo (tap para ver)                 |

| **Paciente Portal**      | Próxima cita, Medicación activa, Resultado reciente | Navegación tabs, Notificaciones | Configuración, Seguridad          | Notas psiquiátricas, Auditoría                  |

### 2.2 Reducción de Carga Cognitiva

| Técnica                    | Implementación                                                                            |

|----------------------------|-------------------------------------------------------------------------------------------|

| **Chunking**               | Login: 2 pasos (Credenciales → MFA). Admin Create: 3 secciones colapsables                |

| **Progressive Disclosure** | Admin List: Card → Tap → Detalle → Acciones. Audit: Fila → Expand → Detalle → Acciones    |

| **Smart Defaults**         | Tenant: Auto-seleccionado si solo 1. Roles: MEDICO por defecto. MFA: Auto si rol requiere |

| **Visual Hierarchy**       | Primary action: Primary button. Destructive: Danger button. Secondary: Outline/Ghost      |

| **Smart Validation**       | Inline validation onBlur + debounced API. Password strength meter real-time               |

---

## 3. Navegación Mobile

### 3.1 Patrones de Navegación

| Patrón                | Uso en Módulo                           | Implementación                                          |

|-----------------------|-----------------------------------------|---------------------------------------------------------|

| **Bottom Navigation** | Paciente Portal (5 tabs)                | Fixed bottom bar, 5 items max, icons + labels           |

| **Hamburger Menu**    | Admin (Desktop/Tablet)                  | Slide-in drawer, overlay, focus trap                    |

| **Tab Bar**           | Auditoría (Filtros/Resultados/Exportar) | Segmented control, swipeable                            |

| **Breadcrumb**        | Admin > Usuarios > Detalle              | Truncado en mobile: "Admin / Usuarios / J. Pérez"       |

| **Back Navigation**   | Modales, Drill-down                     | Back button + swipe back (iOS) / Back gesture (Android) |

### 3.2 Bottom Navigation (Paciente Portal)

```

┌─────────────────────────────────────┐

│  🏥 Mi Portal - María Paz    [≡]    │  ← Header fijo

│  ─────────────────────────────────  │

│                                     │

│  ┌───────────────────────────────┐  │

│  │ 📅 PRÓXIMAS CITAS (2)         │  │

│  │ ─────────────────────────────  │  │

│  │ 📅 20/10 10:00 Cardio         │  │

│  │ 🏥 Dr. González [Ver][🗓️]     │  │

│  └───────────────────────────────┘  │

│                                     │

│  ┌───────────────────────────────┐  │

│  │ 💊 MEDICACIÓN ACTUAL (2)      │  │

│  │ ─────────────────────────────  │  │

│  │ 💊 Losartán 50mg 1/día        │  │

│  │ 💊 Metformina 850mg 2/día     │  │

│  └───────────────────────────────┘  │

│                                     │

│  ─────────────────────────────────  │

│  📄 Expediente  📅 Citas  💊 Meds  ⚙️ Config  │  ← Bottom Nav FIJO

└─────────────────────────────────────┘

```

**CSS Bottom Nav:**

```css

.bottom-nav {

  position: fixed;

  bottom: 0;

  left: 0;

  right: 0;

  display: flex;

  justify-content: space-around;

  padding: 8px 0;

  background: #FFFFFF;

  border-top: 1px solid #E0E0E0;

  box-shadow: 0 -2px 8px rgba(0,0,0,0.08);

  z-index: 1000;

  padding-bottom: env(safe-area-inset-bottom); */\* iPhone notch \*/*

}

.bottom-nav-item {

  display: flex;

  flex-direction: column;

  align-items: center;

  gap: 4px;

  padding: 8px 12px;

  min-width: 60px;

  color: #757575;

  text-decoration: none;

  font-size: 0.75rem;

  transition: color 0.2s;

}

.bottom-nav-item.active {

  color: #1976D2;

}

.bottom-nav-icon {

  font-size: 1.25rem;

}

```

### 3.3 Hamburger Menu (Admin)

```

┌─────────────────────────────────────┐

│  ☰  Admin Panel          [×]        │  ← Slide-in drawer

│  ─────────────────────────────────  │

│                                     │

│  👤 Dra. Admin (TENANT_ADMIN)       │

│  🏥 Hospital Central (HCH-001)      │

│                                     │

│  ─────────────────────────────────  │

│                                     │

│  👥  Gestión Usuarios               │

│  🏥  Gestión Tenants                │

│  🔐  Configuración Auth             │

│  ─────────────────────────────────  │

│                                     │

│  📊  Dashboard                      │

│  📋  Auditoría                      │

│  ⚙️  Configuración                  │

│                                     │

│  ─────────────────────────────────  │

│                                     │

│  🚪  Cerrar Sesión                  │

│                                     │

└─────────────────────────────────────┘

```

**JavaScript - Focus Trap:**

```javascript

*// HamburgerMenu.tsx*

const useFocusTrap = (isOpen: boolean, containerRef: RefObject<HTMLElement>) => {

  useEffect(() => {

    if (!isOpen) return;

    const container = containerRef.current;

    const focusableElements = container.querySelectorAll(

      'button, [href], input, select, textarea, [tabindex]\:not([tabindex="-1"])'

    );

    const firstElement = focusableElements[0] as HTMLElement;

    const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement;

    const handleTab = (e: KeyboardEvent) => {

      if (e.key !== 'Tab') return;

      if (e.shiftKey && document.activeElement === firstElement) {

        e.preventDefault();

        lastElement.focus();

      } else if (!e.shiftKey && document.activeElement === lastElement) {

        e.preventDefault();

        firstElement.focus();

      }

    };

    container.addEventListener('keydown', handleTab);

    firstElement?.focus();

    return () => container.removeEventListener('keydown', handleTab);

  }, [isOpen]);

};

```

---
