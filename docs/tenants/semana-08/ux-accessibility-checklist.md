# Semana 08 — Checklist Accesibilidad WCAG 2.1 AA
## Checklist por Principio WCAG 2.1
### PERCEPIBLE (Perceivable)
#### 1.1 Contenido No Textual
- [ ] **1.1.1** Imágenes decorativas: `alt=""` o `role="presentation"`
- [ ] **1.1.1** Iconos funcionales: `aria-label` o `aria-labelledby` descriptivo
- [ ] **1.1.1** Logos: `alt="Sistema Hospitalario Integrado - Inicio"`
- [ ] **1.1.1** Gráficos/gráficos de fortaleza password: `role="img" aria-label="Fortaleza: 80% - Fuerte"`

#### 1.2 Medios Basados en Tiempo
- [ ] **1.2.1** Audio/Video: Subtítulos y transcripción (no aplica en auth flow)
- [ ] **1.2.3** Audio description: No aplica

#### 1.3 Adaptable
- [ ] **1.3.1** Estructura semántica: `<header>`, `<main>`, `<form>`, `<section>`, `<footer>`
- [ ] **1.3.1** Headings jerárquicos: `<h1>` → `<h2>` → `<h3>` (sin saltos)
- [ ] **1.3.2** Orden de lectura: Tab order lógico (Email → Password → Checkbox → Submit → Links)
- [ ] **1.3.3** Instrucciones sensoriales: No usar solo forma/color/posición ("el botón verde a la derecha")
- [ ] **1.3.4** Orientación: Portrait + Landscape sin pérdida funcionalidad
- [ ] **1.3.5** Identificar propósito inputs: `autocomplete="email"`, `autocomplete="current-password"`, `autocomplete="one-time-code"`

#### 1.4 Distinguible
- [ ] **1.4.1** Color no único medio información: Errores con icono + texto, no solo rojo
- [ ] **1.4.3** Contraste texto: 4.5:1 (normal), 3:1 (large text 18pt+/14pt+ bold)
- [ ] **1.4.3** Contraste UI components: 3:1 (bordes inputs, botones, focus indicators)
- [ ] **1.4.4** Resize texto 200%: Sin pérdida contenido/funcionalidad (responsive)
- [ ] **1.4.5** Imágenes de texto: Evitar (usar CSS text)
- [ ] **1.4.10** Reflow: 320px width sin scroll horizontal (mobile first)
- [ ] **1.4.11** Contraste no-texto: 3:1 (focus rings, bordes, iconos)
- [ ] **1.4.12** Espaciado texto: Line height 1.5x, paragraph 2x, letter 0.12x, word 0.16x
- [ ] **1.4.13** Contenido en hover/focus: Tooltips dismissibles, hoverable, persistent
- [ ] **1.4.13** Reducir movimiento: `@media (prefers-reduced-motion: reduce)` desactiva animaciones

###  OPERABLE (Operable)
#### 2.1 Teclado Accesible
- [ ] **2.1.1** Todo funcionalidad por teclado: Tab, Shift+Tab, Enter, Space, Escape, Arrow keys
- [ ] **2.1.2** Sin trampas teclado: Modales atrapan foco (Focus Trap), Escape cierra
- [ ] **2.1.4** Atajos teclado: Solo si `Ctrl/Cmd` + tecla, desactivables

#### 2.2 Tiempo Suficiente
- [ ] **2.2.1** Timeouts ajustables: MFA 30s configurable, sesión 15min configurable
- [ ] **2.2.2** Pausar/detener: Animaciones/Spinners pausables
- [ ] **2.2.2** MFA countdown: Pausable con tecla, extensible

#### 2.3 Convulsiones
- [ ] **2.3.1** Sin flash > 3Hz: No animaciones parpadeantes > 3 veces/seg

#### 2.4 Navegable
- [ ] **2.4.1** Skip links: "Saltar al contenido principal" (primer Tab)
- [ ] **2.4.2** Títulos página descriptivos: `<title>Login - Sistema Hospitalario</title>`
- [ ] **2.4.3** Orden foco lógico: Visual = DOM order
- [ ] **2.4.4** Propósito enlace en contexto: "¿Olvidó contraseña?" no "Click aquí"
- [ ] **2.4.5** Múltiples vías: Breadcrumbs, search, navigation menu
- [ ] **2.4.6** Headings/labels descriptivos: `<label for="email">Email institucional</label>`
- [ ] **2.4.7** Foco visible: `outline: 3px solid #1976D2; outline-offset: 2px;`
- [ ] **2.4.11** Foco no oculto: `scroll-margin-top: 100px;` (sticky headers)

#### 2.5 Modalidades Entrada
- [ ] **2.5.1** Gestos complejos: Alternativa simple (click vs drag-drop)
- [ ] **2.5.2** Cancelación puntero: Down-event no ejecuta acción (up-event sí)
- [ ] **2.5.3** Etiqueta en nombre: `aria-label` = texto visible botón
- [ ] **2.5.4** Activación por movimiento: Alternativa UI (no solo shake/tilt)

### COMPRENSIBLE (Understandable)
#### 3.1 Legible
- [ ] **3.1.1** Idioma página: `<html lang="es">`
- [ ] **3.1.2** Partes en otro idioma: `lang="en"` en términos técnicos

#### 3.2 Predecible
- [ ] **3.2.1** En foco: No cambios contexto (no auto-submit en focus)
- [ ] **3.2.2** En entrada: No auto-submit en cambio valor (solo Enter/Submit)
- [ ] **3.2.3** Navegación consistente: Header, footer, navigation mismo orden
- [ ] **3.2.4** Identificación consistente: Mismo icono/label = misma función

#### 3.3 Asistencia Entrada
- [ ] **3.3.1** Identificación errores: `aria-invalid="true"` + `aria-describedby="error-id"`
- [ ] **3.3.2** Labels/instrucciones: `<label for="email">`, `aria-describedby="hint-id"`
- [ ] **3.3.3** Sugerencia corrección: "Email formato inválido. Use @hospital.com"
- [ ] **3.3.4** Prevención errores (legal/financiero): Confirmación force-revoke, MFA setup
- [ ] **3.3.4** Reversible: Force-revoke confirma, MFA setup editable
- [ ] **3.3.4** Verificado: Resumen antes force-revoke "Revocar 3 sesiones de Dra. Torres"
- [ ] **3.3.4** Confirmación: Modal "¿Revocar TODAS las sesiones de Dra. Torres?"

### ROBUSTO (Robust)
#### 4.1 Compatible
- [ ] **4.1.1** Parsing: HTML válido, IDs únicos, tags cerrados, atributos válidos
- [ ] **4.1.2** Nombre/Rol/Valor: Custom components (password toggle, strength meter) con ARIA
- [ ] **4.1.3** Mensajes estado: `aria-live="polite"` (toast), `aria-live="assertive"` (errores críticos)

## Checklist por Pantalla (Detallado)
### Pantalla: Login Form
| Criterio                    | Implementación                                              | Test                      |
|-----------------------------|-------------------------------------------------------------|---------------------------|
| **1.1.1** Iconos            | `aria-label="Mostrar contraseña"` en toggle                 | Screen reader anuncia     |
| **1.3.1** Estructura        | `<form>`, `<fieldset>` opcional, `<legend>`                 | HTML validator            |
| **1.3.2** Tab Order         | Email → Password → Checkbox → Submit → Forgot               | Tab navigation            |
| **1.3.5** Autocomplete      | `autocomplete="email"`, `autocomplete="current-password"`   | Form inspector            |
| **1.4.1** Color + Icono     | Error:  + "Credenciales inválidas" + borde rojo             | Color blindness simulator |
| **1.4.3** Contraste Texto   | #212121 on #FFFFFF = 16:1                                   | axe-core                  |
| **1.4.3** Contraste UI      | Borde input #757575 on #FFF = 4.5:1                         | axe-core                  |
| **1.4.3** Focus Ring        | `outline: 3px solid #1976D2; outline-offset: 2px;`          | Keyboard test             |
| **1.4.4** Resize 200%       | Zoom 200% → Sin scroll horizontal, inputs apilados          | Browser zoom              |
| **2.1.1** Teclado           | Tab, Enter submit, Escape limpia, Space checkbox            | Keyboard only test        |
| **2.1.2** No Trap           | Modal MFA: Focus trap, Escape cierra                        | Keyboard test             |
| **2.4.1** Skip Link         | `<a href="#main" class="skip-link">Saltar al contenido</a>` | Tab first                 |
| **2.4.2** Page Title        | `<title>Iniciar Sesión - Sistema Hospitalario</title>`      | Browser tab               |
| **2.4.3** Focus Order       | Visual = DOM: Email → Password → Checkbox → Button          | Tab test                  |
| **2.4.4** Link Purpose      | "¿Olvidó su contraseña?" no "Click aquí"                    | Screen reader             |
| **2.4.7** Focus Visible     | `outline: 3px solid #1976D2;` en todos elementos            | Keyboard test             |
| **3.2.1** On Focus          | No auto-submit, no redirect en focus                        | Keyboard test             |
| **3.2.2** On Input          | No submit en cambio input, solo Enter/Click                 | Form test                 |
| **3.3.1** Error ID          | `aria-invalid="true" aria-describedby="email-error"`        | axe-core                  |
| **3.3.2** Labels            | `<label for="email">Email institucional</label>`            | HTML validator            |
| **3.3.2** Instrucciones     | `aria-describedby="pwd-hint"` en password                   | Screen reader             |
| **3.3.3** Error Suggestion  | "Email formato inválido. Use @hospital.com"                 | Screen reader             |
| **4.1.1** Parsing           | HTML5 válido, IDs únicos                                    | W3C validator             |
| **4.1.2** Custom Components | Password toggle: `role="button" aria-pressed`               | axe-core                  |
| **4.1.3** Status Messages   | Toast: `aria-live="polite"`, Error: `aria-live="assertive"` | Screen reader             |

###  Pantalla: MFA Challenge (TOTP + WebAuthn)
| Criterio   | Implementación                                                                   | Test               |
|------------|----------------------------------------------------------------------------------|--------------------|
| **1.3.1**  | `<h2>Autenticación Doble Factor</h2>` + `<section aria-labelledby="mfa-method">` | HTML structure     |
| **1.3.5**  | `autocomplete="one-time-code"` en input TOTP                                     | Form inspector     |
| **1.4.1**  | Cuenta regresiva: Texto "25s" + barra progreso + color                           | Color blindness    |
| **2.1.1**  | Input numérico: Flechas arriba/abajo, Enter verifica                             | Keyboard           |
| **1.4.13** | `prefers-reduced-motion`: Pausa countdown animación                              | DevTools emulation |
| **2.2.1**  | Timeout 30s configurable, aviso 10s antes                                        | Manual test        |
| **2.4.7**  | Focus visible en input código + botón Verificar                                  | Keyboard           |
| **3.3.1**  | `aria-invalid` en código, `aria-describedby="mfa-error"`                         | axe-core           |
| **3.3.3**  | "Código inválido. Intento 2 de 3. ¿Reenviar?"                                    | Screen reader      |
| **4.1.2**  | WebAuthn button: `role="button" aria-label="Autenticar con FaceID/TouchID"`      | axe-core           |

###  Pantalla: Admin - Alta Usuario (Modal)
| Criterio  | Implementación                                                        | Test           |
|-----------|-----------------------------------------------------------------------|----------------|
| **1.3.1** | `<dialog aria-modal="true" aria-labelledby="modal-title">`            | HTML structure |
| **2.1.2** | Focus trap: Primer input (Email), Escape cierra, Tab cicla            | Keyboard       |
| **2.4.3** | Focus order: Email → Nombre → Roles (checkboxes) → MFA → Cancel/Crear | Tab test       |
| **2.4.7** | Focus visible en todos inputs, checkboxes, botones                    | Keyboard       |
| **3.3.1** | `aria-invalid="true"` en campos error + `aria-describedby`            | axe-core       |
| **3.3.2** | `<label for="roles">Roles</label>` + `aria-describedby="roles-hint"`  | Screen reader  |
| **3.3.3** | Errores password: Lista con checks  + "Use 12+ chars"                 | Screen reader  |
| **3.3.4** | Confirmación: "¿Crear usuario dra.nueva@hospital.com?"                | Manual         |
| **4.1.2** | Multiselect roles: `role="listbox" aria-multiselectable="true"`       | axe-core       |

###  Pantalla: Admin - Lista Usuarios + Force-Revoke
| Criterio  | Implementación                                                           | Test           |
|-----------|--------------------------------------------------------------------------|----------------|
| **1.3.1** | `<table>` con `<thead>`, `<th scope="col">`, `<tbody>`                   | HTML structure |
| **1.3.2** | Tab: Fila → Checkbox → Acciones [⋮] → Siguiente fila                     | Keyboard       |
| **2.1.1** | Enter en [⋮] abre menú, Escape cierra, Tab navega items                  | Keyboard       |
| **2.4.3** | Tab order lógico: Header → Filas → Paginación                            | Tab test       |
| **2.4.7** | Focus visible en [⋮], menú items, botones Danger                         | Keyboard       |
| **3.3.4** | Force-Revoke: Modal confirmación con resumen "3 sesiones de Dra. Torres" | Manual         |

###  Pantalla: Auditoría (Tabla + Detalle)
| Criterio  | Implementación                                                             | Test        |
|-----------|----------------------------------------------------------------------------|-------------|
| **1.3.1** | `<table>` con headers, `<tr>` clickable → panel detalle `aria-expanded`    | HTML        |
| **1.4.1** | Severidad:  CRIT + "CRÍTICO",  WARN + "ADVERTENCIA",  INFO + "INFORMACIÓN" | Color blind |
| **2.1.1** | Tab: Fila → Enter abre detalle → Tab en detalle → Escape cierra            | Keyboard    |
| **2.4.3** | Tab: Filtros → Tabla → Detalle → Exportar                                  | Tab test    |
| **3.2.3** | Navegación consistente: Header → Filtros → Tabla → Footer                  | Visual      |

###  Pantalla: Paciente Portal (Resumen)
| Criterio  | Implementación                                                               | Test          |
|-----------|------------------------------------------------------------------------------|---------------|
| **1.3.1** | `<section aria-labelledby="appointments-heading">` cards semánticas          | HTML          |
| **1.4.3** | Cards: Contraste 4.5:1, bordes 3:1                                           | axe-core      |
| **1.3.3** | Info sensible: `aria-hidden="true"` en notas psiquiátricas (si renderizadas) | Screen reader |
| **2.4.3** | Tab: Navegación → Cards → Detalles → Configuración                           | Tab test      |
| **3.3.2** | Labels en configuración MFA, notificaciones                                  | Screen reader |

## Herramientas de Testing Automatizado
### CI Pipeline (axe-core + jest-axe)
```javascript
// jest.config.js
module.exports = {
  testEnvironment: 'jsdom',
  setupFilesAfterEnv: ['@testing-library/jest-dom/extend-expect'],
};

// tests/accessibility/login.a11y.test.tsx
import { render, screen } from '@testing-library/react';
import { axe, toHaveNoViolations } from 'jest-axe';
import LoginForm from '@/components/LoginForm';

expect.extend(toHaveNoViolations);

describe('LoginForm Accessibility', () => {
  it('should have no WCAG 2.1 AA violations', async () => {
    const { container } = render(<LoginForm />);
    const results = await axe(container);
    expect(results).toHaveNoViolations();
  });

  it('should have proper focus management', async () => {
    render(<LoginForm />);
    const emailInput = screen.getByLabelText('Email institucional');
    emailInput.focus();
    expect(emailInput).toHaveFocus();
    expect(emailInput).toHaveAttribute('aria-invalid', 'false');
  });

  it('should announce errors via aria-live', async () => {
    render(<LoginForm />);
    const submit = screen.getByRole('button', { name: /iniciar sesión/i });
    fireEvent.click(submit);
    const error = await screen.findByRole('alert');
    expect(error).toHaveTextContent(/credenciales inválidas/i);
  });
});
```
### Configuración axe-core
```javascript
// .axerc.json
{
  "rules": {
    "color-contrast": { "enabled": true },
    "keyboard": { "enabled": true },
    "aria-allowed-attr": { "enabled": true },
    "aria-required-attr": { "enabled": true },
    "label": { "enabled": true },
    "button-name": { "enabled": true },
    "link-name": { "enabled": true },
    "image-alt": { "enabled": true },
    "document-title": { "enabled": true },
    "html-has-lang": { "enabled": true },
    "landmark-one-main": { "enabled": true },
    "region": { "enabled": true },
    "skip-link": { "enabled": true }
  },
  "tags": ["wcag2a", "wcag2aa", "wcag21aa", "best-practice"]
}
```
## Testing Manual Requerido
| Test                        | Herramienta                                               | Frecuencia   |
|-----------------------------|-----------------------------------------------------------|--------------|
| **Navegación solo teclado** | Teclado físico                                            | Cada PR      |
| **Screen Reader**           | NVDA (Windows) / VoiceOver (Mac)                          | Cada Release |
| **Zoom 200%**               | Browser zoom + Responsive mode                            | Cada PR      |
| **High Contrast Mode**      | Windows High Contrast / macOS Increase Contrast           | Cada Release |
| **Reduced Motion**          | `@media (prefers-reduced-motion: reduce)`                 | Cada PR      |
| **Color Blindness**         | Chrome DevTools → Rendering → Emulate vision deficiencies | Cada Release |
| **axe-core CI**             | GitHub Actions / GitLab CI                                | Cada Push    |

---

## Matriz de Cumplimiento por Componente
| Componente           | 1.1.1 | 1.3.1 | 1.3.2 | 1.3.5 | 1.4.1 | 1.4.3 | 1.4.4 | 2.1.1 | 2.1.2 | 2.4.3 | 2.4.7 | 3.2.1 | 3.3.1 | 3.3.2 | 3.3.3 | 4.1.2 |
|----------------------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|-------|
| Login Form           |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |
| MFA Challenge        |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |
| Admin Alta Usuario   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |
| Admin Lista + Revoke |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |
| Auditoría Tabla      |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |
| Paciente Portal      |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |   X   |
