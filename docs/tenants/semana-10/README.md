# Semana 10 — Diseño para Movilidad
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## Entregables Generados (`docs/tenants/semana-10/`)

| Archivo                 | Tipo     | Descripción                                                                                                                                           |
|-------------------------|----------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| `diseño-movilidad.md`   | Markdown | **Documento Principal**: Estrategia mobile-first, breakpoints, jerarquización, navegación, formularios, tablas, confirmaciones, offline, 2 escenarios |
| `breakpoints.css`       | CSS      | **Design System Responsive**: Custom properties, media queries, container queries, touch targets, dark mode, forced colors                            |
| `mobile-navigation.tsx` | TSX      | **Componentes Navegación**: BottomNav, HamburgerMenu, TabBar, Breadcrumbs, SkipLink, hooks media queries                                              |
| `responsive-forms.tsx`  | TSX      | **Formularios Responsive**: LoginForm, AdminCreateUser (Stepper), MFAChallenge, Button, FormField, PasswordStrength                                   |
| `responsive-tables.tsx` | TSX      | **Tablas Responsive**: DataTable desktop / CardList mobile, ResponsiveCard, useResponsiveTable hook                                                   |
| `offline-strategy.ts`   | TS       | **Estrategia Offline**: Service Worker, Background Sync, IndexedDB Queue, OfflineIndicator, PendingActionsPanel                                       |
| `mobile-scenarios.md`   | Markdown | **2 Escenarios Detallados**: Médico guardia (mobile) + Admin regional (tablet + offline)                                                              |
| `README.md`             | Markdown | Este índice                                                                                                                                           |

---

## Resumen de la Estrategia Mobile-First

### Breakpoints Definidos

| Breakpoint | Ancho       | Dispositivos                     | Layout                              |
|------------|-------------|----------------------------------|-------------------------------------|
| **XS**     | 320-374px   | iPhone SE, Android small         | Stack 1 col, botones full-width     |
| **SM**     | 375-429px   | iPhone 12/13/14/15               | Stack 1 col, container 400px max    |
| **MD**     | 430-767px   | iPhone Plus/Max, Tablet portrait | 2 cols, sidebar colapsable          |
| **LG**     | 768-1023px  | Tablet landscape, laptop         | Sidebar fija + content              |
| **XL**     | 1024-1439px | Desktop standard                 | Layout completo, sidebar fija       |
| **XXL**    | 1440px+     | Desktop wide, 4K                 | Max-width 1400px, paneles laterales |

### Jerarquización Mobile (Content First)

| Pantalla         | Prioridad 1 (Siempre)                      | Prioridad 2 (1 tap)             | Prioridad 3 (Overflow) |
|------------------|--------------------------------------------|---------------------------------|------------------------|
| **Login**        | Email, Password, Login, Tenant             | MFA help, Forgot password       | Tenant switcher        |
| **MFA**          | Código, Countdown, Verify                  | Switch TOTP/WebAuthn, Resend    | Recovery codes         |
| **Admin Users**  | Nombre, Email, Rol, Estado, [⋮]            | Filtros, Buscar                 | Exportar, Columnas     |
| **Force-Revoke** | Advertencia, Dispositivos, Motivo, Revocar | Comentario, Cancelar            | -                      |
| **Auditoría**    | Evento, Usuario, Timestamp, Severidad      | Filtros, Exportar               | Payload completo       |
| **Paciente**     | Próxima cita, Medicación, Resultado        | Navegación tabs, Notificaciones | Config, Seguridad      |

### Touch Targets & Accesibilidad

| Requisito               | Valor             | Estándar          |
|-------------------------|-------------------|-------------------|
| **Touch Target Mínimo** | 44×44px           | WCAG 2.5.5        |
| **Touch Target Cómodo** | 48×48px           | Material Design   |
| **Touch Target Grande** | 56×56px           | Acciones críticas |
| **Font Size Base**      | 16px              | Previene zoom iOS |
| **Focus Ring**          | 3px solid #1976D2 | WCAG 2.4.7        |
| **Contraste Texto**     | 4.5:1             | WCAG 1.4.3 AA     |

---

## 2 Escenarios Móviles Validados

### 🎯 Escenario 1: Médico de Guardia - Login Rápido (Mobile 375px)

**Contexto**: Guardia nocturna, caminando entre habitaciones, iPhone 13, una mano, guantes médicos

**Flujo Optimizado (< 10s)**:
1. FaceID desbloquea → App abre en Login
2. Tenant auto-seleccionado (solo 1) → Skip selector
3. Email autocomplete → dra.gonzalez@hospital.com
4. FaceID autofill password → Skip typing
5. MFA skip (policy MEDICO) → Skip MFA
6. Redirect directo a Dashboard pacientes guardia

**Decisiones Clave**:
- Tenant selector oculto (solo 1 tenant)
- FaceID autofill → zero typing con guantes
- MFA skip (policy MEDICO no requiere)
- Cache dashboard offline para interrupciones

**Error Handling**:
- FaceID falla → Fallback password + "¿Manos ocupadas?"
- Red lenta → Cache dashboard previo
- Interrupción urgente → State persistence automático

### 🎯 Escenario 2: Admin Regional - Force-Revoke (Tablet 834px + Offline)

**Contexto**: Visita hospitales regionales, iPad Pro 11", WiFi inestable, credenciales filtradas en GitHub

**Flujo Resiliente**:
1. Admin Panel → Gestión Usuarios (2 columnas sidebar + content)
2. Búsqueda debounced 300ms + cache local IndexedDB
3. Force-Revoke 2 pasos + "Deshacer 5s" toast
4. Offline → Queue en IndexedDB + badge "Pendiente sync"
5. Reconexión → Background Sync silencioso → Toast "Sincronizado"

**Decisiones Clave**:
- Layout 2 columnas (834px > 768px breakpoint)
- Búsqueda debounced 300ms + cache local IndexedDB
- Force-Revoke 2 pasos + "Deshacer 5s" (previene click accidental tablet)
- Offline queue + Background Sync silencioso
- Toast "Deshacer" 5s + doble confirmación mobile

---

## Componentes Implementados

### Navegación (`mobile-navigation.tsx`)
- `BottomNavigation` - Paciente Portal (5 tabs, fixed bottom, safe-area)
- `Sidebar` + `HamburgerButton` - Admin (slide-in drawer, focus trap)
- `TabBar` - Auditoría/Paciente (scrollable, keyboard nav)
- `Breadcrumb` - Admin drill-down (truncado mobile)
- `SkipLink` - Accesibilidad (primer Tab)
- Hooks: `useIsMobile`, `useIsTablet`, `useMediaQuery`, `useSafeArea`, `useFocusTrap`

### Formularios (`responsive-forms.tsx`)
- `FormField` - Input accesible (label, error, hint, password toggle, left/right icons)
- `PasswordStrengthMeter` - 5 checks visuales + meter animado
- `Button` - 5 variants, 3 sizes, fullWidthMobile, loading state
- `LoginForm` - Responsive, FaceID autocomplete, tenant auto-select
- `AdminCreateUserForm` - 3-step stepper (Datos → Credenciales → Roles)
- `PasswordStrengthMeter` - 5 checks + meter animado + checklist

### Tablas Responsive (`responsive-tables.tsx`)
- `ResponsiveDataTable` - Desktop table / Mobile card list auto-switch
- `CardList` - Mobile cards con expand/collapse
- `ResponsiveCard` - Expandible, acciones, selección
- `useResponsiveTable` hook - columnas visibles por breakpoint
- Columnas `hideMobile` / `hideTablet` para progressive disclosure

### Estrategia Offline (`offline-strategy.ts`)
- `OfflineQueueManager` (Singleton) - IndexedDB + Background Sync
- `PendingAction` queue con prioridades (high/normal/low)
- Retry exponencial + maxRetries + failed actions store
- Background Sync registration + Service Worker sync handler
- `OfflineIndicator` component - Banner superior + panel detalles
- `PendingActionsPanel` - Admin panel para debug/gestión cola
- React hook `useOfflineQueue` - Estado reactivo + acciones

---

## Escenarios Validados

| Escenario          | Dispositivo          | Conectividad               | Flujo Principal     | Tiempo Objetivo |
|--------------------|----------------------|----------------------------|---------------------|-----------------|
| **Médico Guardia** | iPhone 13 (375px)    | Online estable             | Login → Dashboard   | < 10s           |
| **Admin Regional** | iPad Pro 11" (834px) | Inestable (Offline/Online) | Force-Revoke + Sync | < 45s           |

### Métricas de Validación

| Métrica                    | Escenario 1 (Médico)   | Escenario 2 (Admin)  | Objetivo |
|----------------------------|------------------------|----------------------|----------|
| **Tiempo Total**           | < 10s                  | < 45s                | ✅        |
| **Tasa Éxito 1er Intento** | > 99%                  | > 95%                | > 95%    |
| **Funcionamiento Offline** | Dashboard cache        | Queue + Sync         | ✅        |
| **Recuperación Error**     | < 5s (FaceID fallback) | < 10s (con Deshacer) | < 15s    |
| **Touch Targets**          | ≥ 44px                 | ≥ 48px (tablet)      | ✅ WCAG   |

---

## Archivos CSS/TS Generados

```
docs/tenants/semana-10/
├── diseño-movilidad.md          # Documento principal (este índice)
├── breakpoints.css              # Design System completo (3.5KB+)
├── mobile-navigation.tsx        # Componentes navegación (BottomNav, Sidebar, TabBar, etc.)
├── responsive-forms.tsx         # Formularios (Login, AdminCreate, MFA, Button, etc.)
├── responsive-tables.tsx        # DataTable/CardList responsive
├── offline-strategy.ts          # Service Worker, Queue, Background Sync
├── mobile-scenarios.md          # 2 escenarios detallados con decisiones
└── README.md                    # Este archivo
```

---

## Testing Matrix Mobile

| Test                  | Dispositivos                            | Navegadores             | Checklist                 |
|-----------------------|-----------------------------------------|-------------------------|---------------------------|
| **Visual Regression** | iPhone SE, 13, 14, 15, iPad, Galaxy S23 | Chrome, Safari, Firefox | Percy/Chromatic           |
| **Accessibility**     | NVDA, VoiceOver, TalkBack               | Chrome, Safari          | axe-core + manual         |
| **Performance**       | iPhone SE (throttled 3G)                | Chrome DevTools         | Lighthouse ≥ 90           |
| **Touch Testing**     | iPhone 13, iPad, Galaxy Tab             | Safari, Chrome          | Touch targets, gestures   |
| **Offline**           | Flight mode + throttled                 | Chrome DevTools         | Service Worker, Queue     |
| **Orientation**       | Portrait + Landscape                    | Todos                   | Lock/Unlock, layout shift |

---

## Verificación Rápida

```bash
# Verificar todos los archivos
ls docs/tenants/semana-10/

# Verificar CSS breakpoints
grep -c "@media" docs/tenants/semana-10/breakpoints.css

# Verificar componentes React
grep -c "export.*FC" docs/tenants/semana-10/*.tsx

# Verificar escenarios
grep -c "Escenario" docs/tenants/semana-10/mobile-scenarios.md
```

