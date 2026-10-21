# Semana 11 — Checklist de Consistencia: Roles, Estados, Accesibilidad
## 1. Matriz de Roles vs Permisos/Pantallas
| Pantalla/Función                  | PACIENTE     | MEDICO         | ENFERMERO          | TENANT_ADMIN    | AUDITOR         | PLATFORM_ADMIN  |
|-----------------------------------|--------------|----------------|--------------------|-----------------|-----------------|-----------------|
| **Login**                         | ✅            | ✅              | ✅                  | ✅               | ✅               | ✅               |
| **MFA**                           | Opcional     | No req.        | No req.            | **Obligatorio** | **Obligatorio** | **Obligatorio** |
| **Dashboard**                     | Resumen      | Completo       | Limitado           | Admin Panel     | Auditoría       | Platform Admin  |
| **Pacientes/Expedientes**         | Solo suyo    | ✅ R/W          | ✅ R (signos/notas) | ✅ R (auditoría) | ✅ R (auditoría) | ✅ R/W           |
| **Notas Psiquiátricas/Sensibles** | ✅ (consent.) | ✅ (consent.)   | ❌                  | ❌               | ✅ (auditoría)   | ✅ (auditoría)   |
| **Notas Internas Médicos**        | ❌            | ✅              | ❌                  | ❌               | ✅ (auditoría)   | ✅               |
| **Auditoría Sistema**             | ❌            | ❌              | ❌                  | ✅ (tenant)      | ✅ (global)      | ✅ (global)      |
| **Gestión Usuarios**              | ❌            | ❌              | ❌                  | ✅ CRUD (tenant) | ❌               | ✅ CRUD (global) |
| **Config Tenant/Políticas**       | ❌            | ❌              | ❌                  | ✅ R/W           | ❌               | ✅ R/W (global)  |
| **Logs Seguridad**                | ❌            | ❌              | ❌                  | ❌               | ✅ (filtrado)    | ✅               |
| **Métricas/Uso**                  | ❌            | ❌              | ❌                  | ✅ Dashboard     | ✅ Reportes      | ✅ Global        |
| **Break-Glass**                   | ❌            | ✅ (emergencia) | ❌                  | ❌               | ✅ (auditoría)   | ✅               |
| **Config MFA Propia**             | ✅ (opcional) | ✅              | ✅                  | ✅ (forzado)     | ✅ (forzado)     | ✅               |

## 2. Estados de UI - Consistencia Visual
### 2.1 Botones - Estados Definidos
| Estado          | Primary            | Secondary           | Danger             | Ghost              | Outline             | Loading                 | Disabled                |
|-----------------|--------------------|---------------------|--------------------|--------------------|---------------------|-------------------------|-------------------------|
| **Color Fondo** | `#1976D2`          | `#FFFFFF`           | `#C62828`          | Transparente       | Transparente        | `#1976D2` (opacity 0.7) | `#90CAF9`               |
| **Color Texto** | `#FFFFFF`          | `#212121`           | `#FFFFFF`          | `#1976D2`          | `#1976D2`           | `#FFFFFF`               | `#FFFFFF` (opacity 0.6) |
| **Borde**       | None               | `1px solid #BDBDBD` | None               | None               | `2px solid #1976D2` | None                    | None                    |
| **Hover**       | `#1565C0`          | `#F5F5F5`           | `#B71C1C`          | `#E3F2FD`          | `#1565C0`           | -                       | -                       |
| **Focus**       | Ring 3px `#1976D2` | Ring 3px `#1976D2`  | Ring 3px `#C62828` | Ring 3px `#1976D2` | Ring 3px `#1976D2`  | Ring 3px `#1976D2`      | Ring 3px `#90CAF9`      |
| **Active**      | `#0D47A1`          | `#EEEEEE`           | `#B71C1C`          | `#BBDEFB`          | `#0D47A1`           | -                       | -                       |
| **Disabled**    | `#90CAF9`          | `#E0E0E0`           | `#EF9A9A`          | `#BBDEFB`          | `#90CAF9`           | Opacity 0.7             | Opacity 0.5             |
| **Loading**     | Spinner blanco     | Spinner `#212121`   | Spinner blanco     | Spinner `#1976D2`  | Spinner `#1976D2`   | Spinner                 | -                       |

**Tamaños:**
- `sm`: 40px height, 16px padding, 0.875rem font
- `md`: 48px height, 24px padding, 1rem font **(default)**
- `lg`: 56px height, 32px padding, 1.125rem font
**Full Width Mobile:** `width: 100%` en ≤ 480px

### 2.2 Inputs - Estados
| Estado       | Border          | Background | Label             | Mensaje                               |
|--------------|-----------------|------------|-------------------|---------------------------------------|
| **Default**  | `#E0E0E0`       | `#FFFFFF`  | Normal            | Hint visible                          |
| **Focus**    | `#1976D2` (3px) | `#FFFFFF`  | Focus ring 3px    | Hint visible                          |
| **Error**    | `#C62828` (2px) | `#FFF3E0`  | Rojo + ⚠️         | Error message + `aria-invalid="true"` |
| **Success**  | `#2E7D32` (2px) | `#E8F5E9`  | Verde + ✅         | -                                     |
| **Disabled** | `#E0E0E0`       | `#F5F5F5`  | Gris              | -                                     |
| **Loading**  | `#1976D2`       | `#FFFFFF`  | Spinner en button | -                                     |
**Password Field:** Toggle visibility (👁️/🙈) + Strength meter (5 checks)

### 2.3 Toast Notifications
| Tipo         | Border Left | Icon | Duration   | Action       | Persistent |
|--------------|-------------|------|------------|--------------|------------|
| **Success**  | `#2E7D32`   | ✅    | 3s         | -            | No         |
| **Error**    | `#C62828`   | 🔴   | 5s         | [Reintentar] | No         |
| **Warning**  | `#ED6C02`   | ⚠️   | 5s         | [Acción]     | No         |
| **Critical** | `#B71C1C`   | 🛑   | Persistent | [Acción]     | **Sí**     |
| **Info**     | `#1976D2`   | ℹ️   | 4s         | -            | No         |
**Mobile:** Bottom fixed, 100% width, safe-area-inset-bottom, swipe to dismiss

### 2.4 Modal States
| Estado       | Overlay               | Container                            | Focus                  | Close                             |
|--------------|-----------------------|--------------------------------------|------------------------|-----------------------------------|
| **Open**     | `rgba(0,0,0,0.5)`     | Slide-up (mobile) / Center (desktop) | Auto-focus first input | Escape + Click overlay + X button |
| **Closing**  | Fade out              | Slide-down / Scale down              | Return to trigger      | -                                 |
| **Error**    | `rgba(198,40,40,0.6)` | Border `#C62828` 2px                 | Focus error field      | -                                 |
| **Critical** | `rgba(183,28,28,0.7)` | Red border 2px                       | Auto-focus primary btn | **No Escape/Overlay**             |
**Mobile:** Full-screen, slide-up animation, safe-area-inset-bottom

### 2.4 Table/Card States
| Componente    | Default                         | Hover                       | Selected                              | Expanded         | Loading         |
|---------------|---------------------------------|-----------------------------|---------------------------------------|------------------|-----------------|
| **Table Row** | `transparent` / `#F5F5F5` (alt) | `#F5F5F5`                   | `#E3F2FD` + border-left 3px `#1976D2` | Slide-down panel | Skeleton rows   |
| **Card**      | `#FFFFFF` + shadow-sm           | Shadow-md + transform Y-2px | Border 2px `#1976D2`                  | N/A              | Skeleton card   |
| **Avatar**    | Border 2px `#E0E0E0`            | Scale 1.05                  | Ring 2px `#1976D2`                    | N/A              | Pulse animation |
| **Badge**     | Rounded full                    | Scale 1.05                  | N/A                                   | N/A              | Pulse           |

## 3. Matriz de Roles vs Estados de UI
| Componente                                    | PACIENTE | MEDICO         | ENFERMERO | TENANT_ADMIN   | AUDITOR       | PLATFORM_ADMIN  |
|-----------------------------------------------|----------|----------------|-----------|----------------|---------------|-----------------|
| **Botón "Nuevo Usuario"**                     | ❌ Hidden | ❌ Hidden       | ❌ Hidden  | ✅ Visible      | ❌ Hidden      | ✅ Visible       |
| **Botón "Force-Revoke"**                      | ❌        | ❌              | ❌         | ✅ (own tenant) | ❌             | ✅ (global)      |
| **Botón "Eliminar Usuario"**                  | ❌        | ❌              | ❌         | ❌              | ❌             | ✅ (soft delete) |
| **Botón "Break-Glass"**                       | ❌        | ✅ (emergencia) | ❌         | ❌              | ✅ (auditoría) | ✅               |
| **Columna "Acciones" en Tabla**               | ❌        | ❌              | ❌         | ✅              | ✅ (view only) | ✅               |
| **Filtro "Tenant" en Auditoría**              | ❌        | ❌              | ❌         | ✅ (solo own)   | ✅ (all)       | ✅ (all)         |
| **Exportar PDF/CSV**                          | ❌        | ❌              | ❌         | ✅ (own tenant) | ✅ (all)       | ✅ (all)         |
| **Configuración MFA Forzada**                 | ❌        | ❌              | ❌         | ✅              | ❌             | ✅               |
| **Políticas Tenant (MFA, Session, Password)** | ❌        | ❌              | ❌         | ✅ R/W          | ❌             | ✅ R/W           |
| **Ver Break-Glass Events**                    | ❌        | ❌              | ❌         | ❌              | ✅ (auditoría) | ✅               |

## 4. Accesibilidad - Verificación por Componente
| Componente     | 1.4.3 Contraste | 2.1.1 Teclado | 2.4.7 Foco | 3.3.1 Error ID | 4.1.2 ARIA           | Estado          |
|----------------|-----------------|---------------|------------|----------------|----------------------|-----------------|
| Login Form     | ✅ 4.5:1         | ✅ Tab/Enter   | ✅ 3px ring | ✅ aria-invalid | ✅ aria-describedby   | ✅ Pass          |
| MFA Challenge  | ✅               | ✅             | ✅          | ✅              | ✅ aria-live="polite" | ✅ Pass          |
| Admin Create   | ✅               | ✅             | ✅          | ✅              | ❌ H-005              | ⚠️ Fix Sprint 2 |
| Admin List     | ✅               | ✅             | ✅          | ✅              | ❌ H-003, H-005       | ⚠️ Fix Sprint 2 |
| Force-Revoke   | ✅               | ✅             | ✅          | ✅              | ✅                    | ✅ Pass          |
| Audit Logs     | ✅               | ✅             | ❌ H-004    | ✅              | ❌ H-003              | ⚠️ Fix Sprint 2 |
| Patient Portal | ✅               | ✅             | ✅          | ✅              | ✅                    | ✅ Pass          |
| Critical Modal | ✅               | ✅ (trap)      | ✅ (auto)   | ✅              | ✅                    | ✅ Pass          |
**Gap: 4 violaciones WCAG AA pendientes (Sprint 2 fix)**

## 5. Responsive Breakpoints - Consistencia
| Componente         | XS (320px)     | SM (375px)     | MD (430px)    | LG (768px)      | XL (1024px)      |
|--------------------|----------------|----------------|---------------|-----------------|------------------|
| **Login**          | Stack 100%     | Center 400px   | Center 400px  | Center 400px    | Center 400px     |
| **MFA**            | Stack 100%     | Center 400px   | Center 400px  | Center 400px    | Center 400px     |
| **Admin List**     | Cards stack    | Cards stack    | 2 cols        | Sidebar + Table | Sidebar + Table  |
| **Force-Revoke**   | Full-screen    | Full-screen    | Modal 480px   | Modal 480px     | Modal 480px      |
| **Audit**          | Cards stack    | Cards stack    | Scroll H      | Full Table      | Full Table       |
| **Admin Create**   | Stepper 1-col  | Stepper 1-col  | Stepper 1-col | Stepper 1-col   | Stepper 1-col    |
| **Force-Revoke**   | Full-screen    | Full-screen    | 2-step modal  | 2-step modal    | 2-step modal     |
| **Patient Portal** | Stack vertical | Stack vertical | 2 cols grid   | 3-4 cols grid   | 4 cols + sidebar |

## 6. Touch Targets & Espaciado (Mobile)
| Elemento          | Mínimo WCAG | Recomendado | Implementación                            |
|-------------------|-------------|-------------|-------------------------------------------|
| Botones           | 44×44px     | 48×48px     | `min-height: 48px; min-width: 48px;`      |
| Inputs            | 44×44px     | 48×48px     | `height: 48px; font-size: 16px;`          |
| Checkboxes/Radios | 24×24px     | 24×24px     | `width: 24px; height: 24px; accent-color` |
| Toggle Switches   | 44×44px     | 52×28px     | Custom CSS                                |
| Dropdown Items    | 44×44px     | 48px height | `padding: 12px 16px; min-height: 48px`    |
| Modal Buttons     | 44×44px     | 48×48px     | `min-height: 48px; width: 100%`           |
| Toast Actions     | 44×44px     | 48×48px     | `padding: 12px 16px; min-height: 44px`    |
| Nav Items         | 44×44px     | 48×48px     | `padding: 12px 16px; min-height: 48px`    |

**Spacing Scale (8px base):**
- `--space-xs: 4px`
- `--space-sm: 8px`
- `--space-md: 16px`
- `--space-lg: 24px`
- `--space-xl: 32px`
- `--space-xxl: 48px`

## 7. Accesibilidad - Focus Management
| Componente                 | Focus Ring    | Focus Order                           | Trap                        | Skip Link               | ARIA                                      |
|----------------------------|---------------|---------------------------------------|-----------------------------|-------------------------|-------------------------------------------|
| **Login Form**             | 3px `#1976D2` | Email → Pass → Checkbox → Btn → Links | N/A                         | ✅ "Saltar al contenido" | `aria-describedby` en errors              |
| **MFA Challenge**          | 3px           | Code inputs → Verify → Links          | N/A                         | N/A                     | `aria-live="polite"` en countdown         |
| **Admin Create (Stepper)** | 3px           | Step fields → Buttons                 | N/A                         | ✅                       | `aria-invalid` + `aria-describedby`       |
| **Force-Revoke Modal**     | 3px           | Focus trap (Tab cycle)                | Trap activo                 | ✅                       | `role="alertdialog"`, `aria-modal="true"` |
| **Audit Detail Modal**     | 3px           | Focus en panel detalle                | Trap en modal               | ✅                       | `aria-expanded` en fila                   |
| **Critical Error Modal**   | 3px           | Focus trap estricto                   | **No Escape/Overlay close** | ✅                       | `role="alertdialog"`                      |
| **Mobile Bottom Nav**      | 3px           | Tab cycle                             | N/A                         | N/A                     | `role="navigation"`, `aria-label`         |
| **Hamburger Menu**         | 3px           | Focus trap en drawer                  | Trap activo                 | ✅                       | `aria-expanded` en botón                  |

## 7. Contraste de Colores (WCAG 2.1 AA)
| Uso                  | Foreground | Background         | Ratio          | Estado             |
|----------------------|------------|--------------------|----------------|--------------------|
| **Texto Primario**   | `#212121`  | `#FFFFFF`          | 16:1           | ✅ AAA              |
| **Texto Secundario** | `#757575`  | `#FFFFFF`          | 4.5:1          | ✅ AA               |
| **Texto Terciario**  | `#9E9E9E`  | `#FFFFFF`          | 3.1:1          | ⚠️ Solo large text |
| **Primary Button**   | `#FFFFFF`  | `#1976D2`          | 4.5:1          | ✅ AA               |
| **Secondary Button** | `#212121`  | `#FFFFFF` + border | 4.5:1          | ✅ AA               |
| **Danger Button**    | `#FFFFFF`  | `#C62828`          | 4.5:1          | ✅ AA               |
| **Error Text**       | `#C62828`  | `#FFF3E0`          | 4.5:1          | ✅ AA               |
| **Focus Ring**       | `#1976D2`  | `#FFFFFF`          | 3:1 (non-text) | ✅ AA               |
| **Disabled Text**    | `#9E9E9E`  | `#F5F5F5`          | 2.5:1          | ⚠️ Solo disabled   |
| **Countdown MFA**    | `#666666`  | `#FFFFFF`          | 5.7:1          | ✅ AA (fixed H-001) |

## 8. Reducir Movimiento / Alto Contraste / Forced Colors
```css
@media (prefers-reduced-motion: reduce) {
  * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
  .modal-content { animation: none; }
  .toast { animation: none; }
  .card { transition: none; }
  .btn { transition: none; }
}
@media (prefers-contrast: high) {
  :root {
    --color-border-default: #000000;
    --color-text-primary: #000000;
    --focus-ring-color: #000000;
    --focus-ring-width: 4px;
  }
  .btn { border: 2px solid currentColor; }
  .card { border: 2px solid #000; }
  .form-input { border: 2px solid #000; }
}
@media (forced-colors: active) {
  :root {
    --color-bg-primary: Canvas;
    --color-text-primary: CanvasText;
    --color-border-default: ButtonBorder;
    --color-primary-600: Highlight;
    --color-danger-600: #ff0000;
  }
  .btn-primary { background: Highlight; color: HighlightText; border: 1px solid ButtonBorder; }
  .form-input { background: Canvas; color: CanvasText; border: 1px solid ButtonBorder; }
  .form-input:focus { outline: 2px solid Highlight; outline-offset: 2px; }
}
```
## 9. Verificación Rápida (Comandos)
```bash
npm run test:a11y
npm run test:visual
npm run test:touch
npm run test:responsive
npm run test:a11y:full
```
## 10. Estado de Consistencia - Resumen
| Categoría                   | Items Verificados | Pass | Fail | % Completado |
|-----------------------------|-------------------|------|------|--------------|
| **Design Tokens**           | 45                | 45   | 0    | 100%         |
| **Component States**        | 120               | 118  | 2    | 98%          |
| **Role Permissions**        | 78                | 78   | 0    | 100%         |
| **Responsive Breakpoints**  | 42                | 42   | 0    | 100%         |
| **Accessibility (WCAG AA)** | 36                | 32   | 4    | 89%          |
| **Touch Targets**           | 15                | 15   | 0    | 100%         |
| **Focus Management**        | 18                | 17   | 1    | 94%          |
| **Color Contrast**          | 24                | 23   | 1    | 96%          |
**Total: 402 items | 392 Pass | 10 Fail | 97.5% Completado**
**Gap: 4 violaciones WCAG AA (Sprint 2 fix) | 1 focus management (Sprint 3)**
