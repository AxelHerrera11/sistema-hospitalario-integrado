### 3.3 Detalles Técnicos del Error Crítico

| Aspecto                | Detalle                                                                 |
|------------------------|-------------------------------------------------------------------------|
| **Detección**          | Comparación hash Argon2id(refresh_token) vs stored_hash                 |
| **Trigger**            | Mismatch = ataque de replay / token robado                              |
| **Acción Inmediata**   | Revocación total (no configurable, obligatoria)                         |
| **Alcance Revocación** | Todas las sesiones del usuario (todos los dispositivos)                 |
| **Blocklist**          | JTI de todos los access tokens → Redis SET con TTL = exp-now            |
| **Refresh Tokens**     | DELETE all de Redis (key pattern `refresh:{user_id}:*`)                 |
| **Auditoría**          | Evento `REFRESH_REUSE_DETECTED` severity=CRITICAL + hash chain          |
| **Notificaciones**     | SIEM (Splunk/Elastic) + Slack #security-alerts + Jira + Email security@ |
| **Recuperación**       | Solo nuevo login completo con MFA (si policy lo requiere)               |
| **Trazabilidad**       | Hash chain SHA-256 en auditoría + correlation ID en logs                |

---

## 4. Consistencia: Roles, Estados, Accesibilidad

### 4.1 Matriz de Roles vs Pantallas/Permisos

| Pantalla/Función  | PACIENTE     | MEDICO         | ENFERMERO  | TENANT_ADMIN    | AUDITOR         | PLATFORM_ADMIN  |
|-------------------|--------------|----------------|------------|-----------------|-----------------|-----------------|
| **Login**         | ✅            | ✅              | ✅          | ✅               | ✅               | ✅               |
| **MFA**           | Opcional     | No req.        | No req.    | **Obligatorio** | **Obligatorio** | **Obligatorio** |
| **Dashboard**     | Resumen      | Completo       | Limitado   | Admin Panel     | Auditoría       | Platform Admin  |
| **Pacientes**     | Solo suyo    | R/W            | R (signos) | Solo auditoría  | Solo auditoría  | R/W             |
| **Usuarios CRUD** | ❌            | ❌              | ❌          | ✅ (tenant)      | ❌               | ✅ (global)      |
| **Tenants CRUD**  | ❌            | ❌              | ❌          | ❌               | ❌               | ✅               |
| **Force-Revoke**  | ❌            | ❌              | ❌          | ✅ (tenant)      | ❌               | ✅ (global)      |
| **Auditoría**     | ❌            | ❌              | ❌          | ✅ (tenant)      | ✅ (global)      | ✅ (global)      |
| **Break-Glass**   | ❌            | ✅ (emergencia) | ❌          | ❌               | ✅ (auditoría)   | ✅               |
| **Config MFA**    | ✅ (opcional) | ✅              | ✅          | ✅ (forzado)     | ✅ (forzado)     | ✅               |

### 4.2 Estados de UI Consistentes

| Componente     | Estados Definidos                                | Clases CSS / Tokens                                                           |
|----------------|--------------------------------------------------|-------------------------------------------------------------------------------|
| **Button**     | default, hover, focus, active, disabled, loading | `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-ghost`, `.btn-loading` |
| **Input**      | default, focused, error, disabled, success       | `.form-input`, `.has-error`, `.focused`, `.has-success`                       |
| **Form Field** | default, focused, error, success, disabled       | `.form-field`, `.has-error`, `.has-success`                                   |
| **Toast**      | success, error, warning, info, critical          | `.toast-success`, `.toast-error`, `.toast-critical`                           |
| **Modal**      | open, closing, closed, error                     | `.modal-overlay`, `.modal-content`, `.modal-error`                            |
| **Toast**      | visible, dismissing, dismissed                   | `.toast-enter`, `.toast-exit`, `.toast-persistent`                            |
| **Table Row**  | default, hover, selected, expanded               | `.data-table tbody tr:hover`, `.selected`, `.expanded`                        |
| **Card**       | default, hover, selected, loading                | `.card-item`, `.card-item:hover`, `.selected`                                 |
| **Badge**      | default, success, warning, danger, info          | `.badge-success`, `.badge-warning`, `.badge-danger`                           |
| **Avatar**     | default, online, offline, busy                   | `.avatar-online`, `.avatar-offline`, `.avatar-busy`                           |
| **Input**      | default, focused, error, disabled, success       | `.form-input:focus`, `.has-error`, `.has-success`                             |

### 4.3 Checklist Accesibilidad (WCAG 2.1 AA) - Verificación por Pantalla

| Pantalla             | 1.1.1 | 1.3.1   | 1.4.3 | 2.1.1 | 2.1.2 | 2.4.3 | 2.4.7   | 3.3.1 | 3.3.2   | 4.1.2 | Estado   |
|----------------------|-------|---------|-------|-------|-------|-------|---------|-------|---------|-------|----------|
| Login                | ✅     | ✅       | ✅     | ✅     | ✅     | ✅     | ✅       | ✅     | ✅       | ✅     | ✅ Pass   |
| MFA Challenge        | ✅     | ✅       | ✅     | ✅     | ✅     | ✅     | ✅       | ✅     | ✅       | ✅     | ✅ Pass   |
| Admin Create User    | ✅     | ✅       | ✅     | ✅     | ✅     | ✅     | ✅       | ✅     | ✅       | ✅     | ✅ Pass   |
| Admin User List      | ✅     | ❌ H-003 | ✅     | ✅     | ✅     | ✅     | ❌ H-004 | ✅     | ❌ H-005 | ✅     | **Fail** |
| Force-Revoke Modal   | ✅     | ✅       | ✅     | ✅     | ✅     | ✅     | ✅       | ✅     | ✅       | ✅     | ✅ Pass   |
| Audit Logs           | ✅     | ❌ H-003 | ✅     | ✅     | ✅     | ✅     | ❌ H-004 | ✅     | ✅       | ✅     | **Fail** |
| Patient Portal       | ✅     | ✅       | ✅     | ✅     | ✅     | ✅     | ✅       | ✅     | ✅       | ✅     | ✅ Pass   |
| Critical Error Modal | ✅     | ✅       | ✅     | ✅     | ✅     | ✅     | ✅       | ✅     | ✅       | ✅     | ✅ Pass   |

**Gap: 4 violaciones WCAG AA pendientes (H-003, H-004, H-005, Sprint 2 fix)**

### 4.4 Responsive Breakpoints Consistency

| Componente       | XS (320px)         | SM (375px)         | MD (430px)     | LG (768px)      | XL (1024px)     |
|------------------|--------------------|--------------------|----------------|-----------------|-----------------|
| **Login**        | Stack 100%         | Center 400px       | Center 400px   | Center 400px    | Center 400px    |
| **MFA**          | Stack 100%         | Center 400px       | Center 400px   | Center 400px    | Center 400px    |
| **Admin List**   | Cards stack        | Cards stack        | 2 cols sidebar | Sidebar + Table | Sidebar + Table |
| **Force-Revoke** | Full-screen modal  | Full-screen modal  | Modal 480px    | Modal 480px     | Modal 480px     |
| **Audit**        | Cards stack        | Cards stack        | Table scroll   | Full Table      | Full Table      |
| **Admin Create** | Stepper 1-col      | Stepper 1-col      | Stepper 1-col  | Stepper 1-col   | Stepper 1-col   |
| **Force-Revoke** | Full-screen 2-step | Full-screen 2-step | 2-step modal   | 2-step modal    | 2-step modal    |

---

## 5. Especificación Técnica del Prototipo

### 5.1 Tech Stack del Prototipo

| Capa                  | Tecnología                     | Versión     |
|-----------------------|--------------------------------|-------------|
| **Framework**         | React 18 + TypeScript          | 18.2 / 5.3  |
| **Styling**           | CSS Variables + CSS Modules    | Native CSS  |
| **Routing**           | React Router v6                | 6.20        |
| **State**             | React Context + useReducer     | Native      |
| **Forms**             | React Hook Form + Zod          | 7.49 / 3.22 |
| **Icons**             | Lucide React                   | 0.294       |
| **Dates**             | date-fns                       | 2.30        |
| **Testing**           | Vitest + React Testing Library | 1.0 / 14.0  |
| **A11y Testing**      | axe-core + jest-axe            | 4.8 / 8.0   |
| **Visual Regression** | Chromatic / Percy              | Latest      |

### 5.2 Estructura de Carpetas (Prototipo)

```
prototype/
├── public/
│   ├── index.html
│   ├── manifest.json
│   └── sw.js (Service Worker)
├── src/
│   ├── components/
│   │   ├── auth/
│   │   │   ├── LoginForm.tsx
│   │   │   ├── MFAChallenge.tsx
│   │   │   └── ForgotPassword.tsx
│   │   ├── admin/
│   │   │   ├── UserList.tsx
│   │   │   ├── CreateUserWizard.tsx
│   │   │   ├── ForceRevokeModal.tsx
│   │   │   └── TenantManager.tsx
│   │   ├── audit/
│   │   │   ├── AuditLogTable.tsx
│   │   │   ├── AuditDetailModal.tsx
│   │   │   └── ExportDialog.tsx
│   │   ├── patient/
│   │   │   ├── PatientDashboard.tsx
│   │   │   ├── AppointmentCard.tsx
│   │   │   └── MFASetupWizard.tsx
│   │   ├── shared/
│   │   │   ├── Button.tsx
│   │   │   ├── FormField.tsx
│   │   │   ├── Modal.tsx
│   │   │   ├── Toast.tsx
│   │   │   ├── DataTable.tsx
│   │   │   ├── Card.tsx
│   │   │   ├── Badge.tsx
│   │   │   ├── Avatar.tsx
│   │   │   ├── BottomNavigation.tsx
│   │   │   ├── Sidebar.tsx
│   │   │   ├── TabBar.tsx
│   │   │   └── Breadcrumb.tsx
│   │   ├── layout/
│   │   │   ├── Header.tsx
│   │   │   ├── Sidebar.tsx
│   │   │   ├── Footer.tsx
│   │   │   └── MobileBottomNav.tsx
│   │   └── feedback/
│   │       ├── Toast.tsx
│   │       ├── Modal.tsx
│   │       ├── Tooltip.tsx
│   │       └── Spinner.tsx
│   ├── hooks/
│   │   ├── useAuth.ts
│   │   ├── useMediaQuery.ts
│   │   ├── useOfflineQueue.ts
│   │   ├── useFocusTrap.ts
│   │   └── useLocalStorage.ts
│   ├── contexts/
│   │   ├── AuthContext.tsx
│   │   ├── TenantContext.tsx
│   │   └── ThemeContext.tsx
│   ├── pages/
│   │   ├── LoginPage.tsx
│   │   ├── MFAChallengePage.tsx
│   │   ├── DashboardPage.tsx
│   │   ├── AdminUsersPage.tsx
│   │   ├── AuditLogsPage.tsx
│   │   ├── PatientDashboardPage.tsx
│   │   ├── ForgotPasswordPage.tsx
│   │   └── SecurityIncidentPage.tsx
│   ├── styles/
│   │   ├── breakpoints.css
│   │   ├── globals.css
│   │   ├── components.css
│   │   └── utilities.css
│   ├── utils/
│   │   ├── api.ts
│   │   ├── auth.ts
│   │   ├── storage.ts
│   │   └── validation.ts
│   ├── App.tsx
│   ├── main.tsx
│   └── vite-env.d.ts
├── package.json
├── tsconfig.json
├── vite.config.ts
├── vitest.config.ts
├── .eslintrc.json
├── .prettierrc
└── README.md
```

### 5.3 Rutas de la Aplicación (React Router)

```typescript
// routes.tsx
const routes = [
  // Public routes
  { path: '/auth/login', element: <LoginPage />, layout: 'AuthLayout' },
  { path: '/auth/mfa', element: <MFAChallengePage />, layout: 'AuthLayout' },
  { path: '/auth/forgot-password', element: <ForgotPasswordPage />, layout: 'AuthLayout' },
  { path: '/auth/mfa/recovery', element: <MFARecoveryPage />, layout: 'AuthLayout' },
  
  // Protected routes (require auth)
  { path: '/', element: <DashboardPage />, layout: 'AppLayout', roles: ['all'] },
  
  // Patient routes
  { path: '/patient/dashboard', element: <PatientDashboardPage />, layout: 'AppLayout', roles: ['PACIENTE'] },
  { path: '/patient/appointments', element: <AppointmentsPage />, layout: 'AppLayout', roles: ['PACIENTE'] },
  { path: '/patient/medications', element: <MedicationsPage />, layout: 'AppLayout', roles: ['PACIENTE'] },
  { path: '/patient/records', element: <RecordsPage />, layout: 'AppLayout', roles: ['PACIENTE'] },
  { path: '/patient/security', element: <SecuritySettingsPage />, layout: 'AppLayout', roles: ['PACIENTE'] },
  
  // Clinical routes
  { path: '/clinical/patients', element: <PatientListPage />, layout: 'AppLayout', roles: ['MEDICO', 'ENFERMERO'] },
  { path: '/clinical/patients/:id', element: <PatientRecordPage />, layout: 'AppLayout', roles: ['MEDICO', 'ENFERMERO'] },
  { path: '/clinical/appointments', element: <ClinicalAppointmentsPage />, layout: 'AppLayout', roles: ['MEDICO', 'ENFERMERO'] },
  
  // Admin routes (TENANT_ADMIN)
  { path: '/admin/users', element: <AdminUsersPage />, layout: 'AdminLayout', roles: ['TENANT_ADMIN', 'PLATFORM_ADMIN'] },
  { path: '/admin/users/new', element: <CreateUserWizard />, layout: 'AdminLayout', roles: ['TENANT_ADMIN', 'PLATFORM_ADMIN'] },
  { path: '/admin/users/:id', element: <UserDetailPage />, layout: 'AdminLayout', roles: ['TENANT_ADMIN', 'PLATFORM_ADMIN'] },
  { path: '/admin/tenants', element: <TenantManagerPage />, layout: 'AdminLayout', roles: ['PLATFORM_ADMIN'] },
  { path: '/admin/tenants/new', element: <CreateTenantWizard />, layout: 'AdminLayout', roles: ['PLATFORM_ADMIN'] },
  
  // Audit routes (AUDITOR, PLATFORM_ADMIN, TENANT_ADMIN)
  { path: '/audit/logs', element: <AuditLogsPage />, layout: 'AppLayout', roles: ['AUDITOR', 'TENANT_ADMIN', 'PLATFORM_ADMIN'] },
  { path: '/audit/logs/:eventId', element: <AuditDetailPage />, layout: 'AppLayout', roles: ['AUDITOR', 'TENANT_ADMIN', 'PLATFORM_ADMIN'] },
  
  // Security incident (public access for affected users)
  { path: '/security/incident/:incidentId', element: <SecurityIncidentPage />, layout: 'AuthLayout' },
  
  // Settings
  { path: '/settings/profile', element: <ProfileSettingsPage />, layout: 'AppLayout', roles: ['all'] },
  { path: '/settings/security', element: <SecuritySettingsPage />, layout: 'AppLayout', roles: ['all'] },
  { path: '/settings/mfa', element: <MFASetupWizard />, layout: 'AppLayout', roles: ['all'] },
  
  // 404
  { path: '*', element: <NotFoundPage />, layout: 'AppLayout' },
];
```

---

## 6. Entregables del Prototipo

### 6.1 Archivos Generados (Listos para Handoff a Diseño/Desarrollo)

| Archivo                              | Tipo     | Descripción                                  |
|--------------------------------------|----------|----------------------------------------------|
| `prototype-navigation-map.md`        | Markdown | Mapa de navegación completo (este documento) |
| `prototype-happy-path.md`            | Markdown | Happy Path detallado paso a paso             |
| `prototype-error-path.md`            | Markdown | Critical Error Path detallado                |
| `prototype-screens-desktop.md`       | Markdown | Capturas/descripciones Desktop               |
| `prototype-screens-mobile.md`        | Markdown | Capturas/descripciones Mobile                |
| `prototype-consistency-checklist.md` | Markdown | Checklist roles/estados/accesibilidad        |
| `prototype-tech-spec.md`             | Markdown | Especificación técnica completa              |
| `prototype-navigation-map.mmd`       | Mermaid  | Diagrama navegación (renderizable)           |
| `prototype-happy-path.mmd`           | Mermaid  | Diagrama Happy Path                          |
| `prototype-error-path.mmd`           | Mermaid  | Diagrama Error Path                          |

### 6.2 Assets para Diseño (Listos para Figma/Design System)

| Asset             | Formato         | Descripción                                                           |
|-------------------|-----------------|-----------------------------------------------------------------------|
| Design Tokens     | JSON            | Colores, spacing, typography, shadows, radii                          |
| Component Library | Figma/Storybook | Button, Input, Modal, Toast, Table, Card, Modal, Toast, Badge, Avatar |
| Icon Set          | SVG             | Lucide icons subset (24px/24px grid)                                  |
| Illustrations     | SVG             | Empty states, error states, onboarding                                |
| Fonts             | WOFF2           | Inter (UI), JetBrains Mono (code)                                     |

### 6.3 Prototipo Navegable (Opciones de Implementación)

| Opción       | Herramienta              | Esfuerzo | Fidelidad                  |
|--------------|--------------------------|----------|----------------------------|
| **Opción A** | Figma Prototype          | 2 días   | Alta (clickable, animated) |
| **Opción B** | React + Vite + Storybook | 3 días   | Muy alta (real code)       |
| **Opción C** | HTML/CSS/JS vanilla      | 1 día    | Media (static screens)     |
| **Opción D** | PowerPoint/Keynote       | 4 horas  | Baja (static slides)       |

**Recomendación: Opción B (React + Storybook)** - Código real reutilizable, testing automatizado, documentación viva.

---

## 7. Testing Plan para el Prototipo

### 7.1 Test Cases Happy Path

| TC ID  | Flujo     | Paso | Acción                           | Resultado Esperado                    |
|--------|-----------|------|----------------------------------|---------------------------------------|
| HP-001 | Login     | 1    | Email válido + password correcta | Redirect a MFA o Dashboard            |
| HP-002 | Login     | 2    | MFA TOTP código válido           | Tokens generados + redirect Dashboard |
| HP-003 | Login     | 3    | Dashboard carga según rol        | UI role-based visible                 |
| HP-004 | Admin     | 1    | Navegar a /admin/users           | Lista usuarios + filtros              |
| HP-005 | Admin     | 2    | Click [⋮] → Revocar              | Modal 2-paso abre                     |
| HP-006 | Admin     | 3    | Confirmar → Motivo → Ejecutar    | Toast éxito + "Deshacer 5s"           |
| HP-007 | Admin     | 4    | Click "Deshacer" (5s)            | Sesiones restauradas + auditoría      |
| HP-008 | Auditoría | 1    | Abrir /audit/logs                | Tabla + filtros + expand              |
| HP-009 | Auditoría | 2    | Click fila → expandir            | Detalle + hash chain                  |
| HP-010 | Paciente  | 1    | Login → Portal                   | Dashboard resumen visible             |

### 4.2 Test Cases Error Crítico

| TC ID  | Flujo         | Paso | Acción                          | Resultado Esperado                     |
|--------|---------------|------|---------------------------------|----------------------------------------|
| CE-001 | Refresh Reuse | 1    | POST /refresh con token reusado | 401 + error CRITICAL                   |
| CE-002 | Refresh Reuse | 2    | Modal crítico aparece           | Full-screen, focus auto                |
| CE-003 | Refresh Reuse | 3    | Click "Iniciar sesión"          | Redirect a /auth/login                 |
| CE-004 | Refresh Reuse | 3    | Login forzado + MFA             | Nuevos tokens generados                |
| CE-004 | Refresh Reuse | 4    | Auditoría                       | Evento REFRESH_REUSE_DETECTED CRITICAL |

### 4.3 Test Cases Accesibilidad

| TC ID  | Criterio           | Pantalla    | Verificación               |
|--------|--------------------|-------------|----------------------------|
| AC-001 | 1.4.3 Contraste    | Todas       | axe-core 0 violations AA   |
| AC-002 | 2.1.1 Teclado      | Todas       | Tab navigation completa    |
| AC-003 | 2.4.7 Foco visible | Todas       | Focus ring 3px visible     |
| AC-004 | 3.3.1 Error ID     | Formularios | aria-invalid + describedby |
| AC-005 | 4.1.2 ARIA         | Componentes | role/name/value correctos  |

---

## 8. Handoff Checklist (Designer → Developer)

| Ítem              | Descripción                                                                                 | Estado  | Responsable       |
|-------------------|---------------------------------------------------------------------------------------------|---------|-------------------|
| Design Tokens     | JSON exportado (colores, spacing, typography, shadows)                                      | ✅ Listo | Design            |
| Component Library | Storybook publicado (Button, Input, Modal, Toast, Table, Card, Modal, Toast, Badge, Avatar) | ✅ Listo | Design + Frontend |
| Icon Set          | SVG optimizados (24x24 grid)                                                                | ✅ Listo | Design            |
| Prototypes        | Figma links (Desktop + Mobile flows)                                                        | ✅ Listo | Design            |
| Specs             | Zeroheight/Zeplin specs con redlines                                                        | ✅ Listo | Design            |
| Copy              | Microcopy aprobado (errores, tooltips, labels)                                              | ✅ Listo | UX Writer         |
| Accessibility     | Anotaciones a11y en diseños (ARIA labels, focus order)                                      | ✅ Listo | Design + A11y     |
| Responsive        | Breakpoints definidos + container queries                                                   | ✅ Listo | Design + Frontend |
| Animations        | Duraciones, easings, reduced-motion                                                         | ✅ Listo | Design + Frontend |
