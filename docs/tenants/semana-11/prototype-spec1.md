# Semana 11 — Especificación del prototipo (parte 2: consistencia, stack y pruebas)
**Versión:** 1.1 | **Fecha:** 2026-10-21

> Continúa [`prototype-spec.md`](prototype-spec.md). Mismas etiquetas: **[Implementado]** (existe en
> `develop`) y **[Propuesto]** (diseño sin contrato). Nada de este documento afirma compilaciones,
> mediciones ni entregables que no estén en el repositorio.

---

## 4. Consistencia

### 4.1 Roles canónicos vs. pantallas

Códigos literales de `config/rbac.php`. Lo que cada rol ve lo decide el catálogo de permisos del
backend (`rbac:<permiso>` en `routes/api.php`), no esta tabla; aquí solo se resume el diseño.

| Pantalla / función | Permiso | Estado | Roles que la usan en el diseño |
|---|---|---|---|
| Inicio de sesión | — | [Implementado] | Todos |
| MFA TOTP | — | [Propuesto] | Obligatorio para `superadmin` y `admin_hospital`; opcional para el resto |
| Usuarios del hospital | `usuarios.ver` | [Implementado] | `admin_hospital` |
| Asignar / quitar rol | `rbac.asignar` | [Implementado] | `admin_hospital` |
| Desactivar usuario | `usuarios.desactivar` | [Implementado] | `admin_hospital` |
| Cerrar una sesión ajena | `sesiones.revocar_ajenas` | [Implementado] | `admin_hospital` |
| Alta de hospital | `tenants.crear` | [Implementado] | `superadmin` |
| Alta de usuario | — | [Propuesto] | `admin_hospital` |
| Cerrar todas las sesiones | — | [Propuesto] | `admin_hospital` |
| Auditoría de seguridad | `auditoria.ver` | [Propuesto] (sin vista ni endpoint) | según catálogo RBAC |

Correspondencia con la v1.0: TENANT_ADMIN → `admin_hospital`; PLATFORM_ADMIN → `superadmin`;
MEDICO → `medico`; ENFERMERO → `enfermeria`. AUDITOR y PACIENTE no existen en RBAC y se retiraron;
«break-glass» no tiene contrato y quedó fuera.

### 4.2 Estados de componentes

Solo los componentes que existen en el SPA (Semana 10).

| Componente | Archivo | Estados |
|---|---|---|
| Botón | `modules/tenants/responsive/BaseButton.vue` | normal, foco visible, deshabilitado, cargando (ignora clics) |
| Campo | `modules/tenants/responsive/FormField.vue` | normal, foco, error (`aria-invalid`), deshabilitado, contraseña visible/oculta |
| Medidor | `modules/tenants/responsive/PasswordStrengthMeter.vue` | 0–5 requisitos cumplidos |
| Indicador offline | `modules/tenants/offline/OfflineIndicator.vue` | sin red, pendientes, fallidas |
| Panel de cola | `modules/tenants/offline/PendingActionsPanel.vue` | vacía, pendientes, fallidas (solo metadatos) |

Toasts, modales genéricos, tablas expandibles, avatares y badges de la v1.0 son **[Propuesto]**:
no existen como componentes del proyecto.

### 4.3 Accesibilidad (WCAG 2.1 AA)

Revisión **manual del diseño**, no una medición automatizada. Los hallazgos H-003 a H-005 son los
registrados en la tabla de Semana 9 (`docs/tenants/semana-09/README.md`).

| Pantalla | Revisión del diseño | Hallazgos abiertos |
|---|---|---|
| Inicio de sesión | Etiquetas, errores con `role="alert"`, foco visible, 44 px | — |
| MFA | Un solo campo con `autocomplete="one-time-code"` | — (pantalla propuesta) |
| Usuarios del hospital | Pendiente de corregir | H-003 (1.3.1), H-004 (2.4.7), H-005 (3.3.2) |
| Cerrar sesión ajena | Confirmación explícita y resultado con `role="status"` | — |
| Auditoría | Severidad en texto, sin gestos ocultos | H-003, H-004 (pantalla propuesta) |

No se ha corrido axe-core sobre estas pantallas. Queda como prueba pendiente en la sección 7.

### 4.4 Puntos de quiebre

Se toman del CSS real de Semana 10: hasta 480 px (botones a todo el ancho), 640 px (una columna) y
1024 px (dos columnas); por encima, tres columnas. Para «Usuarios» y «Cerrar sesión» la tabla es
diseño: esas pantallas todavía no aplican estos cortes.

| Pantalla | ≤ 480 px | ≤ 640 px | ≤ 1024 px | > 1024 px |
|---|---|---|---|---|
| Inicio de sesión | Sin marco, 100 % ancho | Tarjeta de hasta 420 px | igual | igual |
| Usuarios | Tarjetas apiladas | Tarjetas apiladas | Tabla | Tabla |
| Cerrar sesión | Modal a pantalla completa | Modal a pantalla completa | Modal de 480 px | Modal de 480 px |

---

## 5. Stack del prototipo

### 5.1 Tecnología

El prototipo navegable usa el **mismo stack del proyecto** (`package.json`). La v1.0 proponía
React 18 + Storybook; se descartó porque el proyecto no tiene ni declara dependencias de React.

| Capa | Tecnología | Estado |
|---|---|---|
| Framework | Vue 3 (`<script setup>`) | [Implementado] |
| Build | Vite 7 + `laravel-vite-plugin` | [Implementado] |
| Rutas | vue-router 4 | [Implementado] |
| Estado de sesión | Pinia (`stores/auth.js`) | [Implementado] |
| HTTP | axios (`plugins/axios.js`, base `/api/v1`) | [Implementado] |
| Estilos | CSS con alcance (`<style scoped>`) y Tailwind 4 | [Implementado] |
| Pruebas de lógica | `node` + `node:assert` (`*.test.mjs`) | [Implementado] |
| Pruebas de componentes y axe | Vitest + Vue Test Utils + axe-core | [Propuesto] (no instalados) |

### 5.2 Archivos del prototipo (existentes)

```
resources/js/
├── router/index.js                         # ruta /prototipos/tenants-responsive
└── modules/tenants/
    ├── pages/
    │   ├── HospitalUsersPage.vue           # usuarios del hospital
    │   └── ResponsivePrototypePage.vue     # prototipo de Semana 10
    ├── responsive/
    │   ├── BaseButton.vue
    │   ├── FormField.vue
    │   ├── LoginForm.vue
    │   ├── PasswordStrengthMeter.vue
    │   ├── formRules.js
    │   └── formRules.test.mjs
    └── offline/
        ├── offlineQueue.js
        ├── offlineQueue.test.mjs
        ├── useOfflineQueue.js
        ├── OfflineIndicator.vue
        └── PendingActionsPanel.vue
```

### 5.3 Rutas del SPA relacionadas

| Ruta | Componente | Protección | Estado |
|---|---|---|---|
| `/login` | `modules/auth/pages/LoginPage.vue` | invitado | [Implementado] |
| `/` | `pages/HomePage.vue` | — | [Implementado] |
| `/admin/users` | `modules/tenants/pages/HospitalUsersPage.vue` | sesión requerida | [Implementado] |
| `/prototipos/tenants-responsive` | `modules/tenants/pages/ResponsivePrototypePage.vue` | pública | [Implementado] |
| `/mfa` | — | invitado | [Propuesto] |
| `/admin/users/nuevo` | — | sesión requerida | [Propuesto] |
| `/auditoria` | — | sesión requerida | [Propuesto] |

---

## 6. Entregables

### 6.1 Entregados en el repositorio

| Archivo | Tipo |
|---|---|
| `prototype-spec.md`, `prototype-spec1.md` | Especificación (v1.1) |
| `prototype-screens-mobile.md`, `prototype-screens-mobile1.md` | Mockups móviles (v1.1) |
| `prototype-screens-desktop*.md`, `prototype-happy-path*.md`, `prototype-error-path*.md` | Mockups y flujos (v1.0) |
| `prototype-navigation-map.mmd`, `prototype-happy-path.mmd`, `prototype-error-path.mmd` | Diagramas Mermaid |
| Prototipo Vue + capturas a 375/768/1280 px | `resources/js/modules/tenants/`, `docs/tenants/semana-10/evidencias/` |

### 6.2 No entregados

No hay Figma, Storybook, tokens JSON, set de íconos ni ilustraciones en el repositorio. La v1.0
los marcaba como «✅ Listo»; se corrigió a **No entregado**.

---

## 7. Plan de pruebas

### 7.1 Casos del camino feliz

| ID | Paso | Resultado esperado | Estado |
|---|---|---|---|
| HP-001 | Login con credenciales válidas | 200 y redirección a `/` | API cubierta en `tests/Feature/Tenants`; redirección pendiente |
| HP-002 | MFA TOTP válido | Tokens emitidos | Probado en el micro-HIS sem. 3 (`MicroHisSmokeTest`) |
| HP-003 | Abrir `/admin/users` con `usuarios.ver` | Lista del hospital | Pendiente (prueba de UI) |
| HP-004 | Cerrar una sesión ajena | 200 y la sesión deja de servir | Pendiente (prueba de UI) |
| HP-005 | Doble clic en «Entrar» | Una sola petición | Cubierto (`formRules.test.mjs`) |

### 7.2 Casos de error crítico

| ID | Paso | Resultado esperado | Estado |
|---|---|---|---|
| CE-001 | Refresh ya consumido | 401 `REFRESH_TOKEN_REUSED` | Cubierto (`SessionLifecycleTest`) |
| CE-002 | SPA recibe el 401 | Sesión limpia y vuelta a `/login` | Pendiente (prueba de UI) |
| CE-003 | Auditoría | `auth.refresh_reuse_detected` crítico | Pendiente: el código lo registra, pero ninguna prueba lo verifica |

### 7.3 Accesibilidad

| ID | Criterio | Verificación | Estado |
|---|---|---|---|
| AC-001 | 1.4.3 Contraste | axe-core | Pendiente |
| AC-002 | 2.1.1 Teclado | Recorrido con Tab | Pendiente |
| AC-003 | 2.4.7 Foco visible | `:focus-visible` de 3 px | Implementado en los componentes de Semana 10 |
| AC-004 | 3.3.1 Errores | `aria-invalid` + `aria-describedby` | Implementado en `FormField.vue` |

Los casos de la v1.0 sobre «Deshacer» y «sesiones restauradas» (HP-006 y HP-007) se eliminaron:
esa función no existe y contradice la revocación.
