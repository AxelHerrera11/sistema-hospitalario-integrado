# Wireframes - Semana 08
## Índice de Wireframes
| # | Pantalla                   | Archivo                   | Estados Incluidos                                    |
|---|----------------------------|---------------------------|------------------------------------------------------|
| 1 | Login Form                 | `01-login-form.md`        | Vacío, Carga, Error, Éxito                           |
| 2 | MFA Challenge              | `02-mfa-challenge.md`     | TOTP, WebAuthn, Error, Bloqueo                       |
| 3 | Admin Alta Usuario         | `03-admin-create-user.md` | Vacío, Validando, Error Campo, Error Servidor, Éxito |
| 4 | Admin Lista + Force-Revoke | `04-admin-user-list.md`   | Lista, Menú Acciones, Modal Force-Revoke             |
| 5 | Auditoría + Detalle        | `05-audit-logs.md`        | Tabla Filtros, Fila Expandida, Detalle Crítico       |
| 6 | Paciente Portal            | `06-patient-portal.md`    | Dashboard Resumen, Seguridad                         |

## Instrucciones de Uso
### Importar a draw.io
1. Abrir draw.io (diagrams.net)
2. File → Import From → Device
3. Seleccionar archivo `.md` o copiar contenido Mermaid
4. Arrange → Layout → Horizontal Flow

### Importar a Figma
1. Plugin "Mermaid to Figma" o "Wireframe"
2. Pegar código Mermaid
3. Convertir a frames/components

### Exportar a PNG/SVG
```bash
npm install -g @mermaid-js/mermaid-cli
mmdc -i wireframe.md -o wireframe.png -w 1200
```
## Convenciones Visuales
| Elemento             | Representación ASCII                                            | Clase CSS Sugerida                |
|----------------------|-----------------------------------------------------------------|-----------------------------------|
| **Input**            | `[____________________]`                                        | `.form-input`                     |
| **Input Error**      | `[❌ valor_____________]`                                        | `.form-input.error`               |
| **Password**         | `[●●●●●●●●●●●●] [👁️]`                                          | `.password-field`                 |
| **Button Primary**   | `[ ACCIÓN PRINCIPAL ]`                                          | `.btn-primary`                    |
| **Button Secondary** | `[ Acción Secundaria ]`                                         | `.btn-secondary`                  |
| **Button Danger**    | `[ ACCIÓN PELIGROSA ]`                                          | `.btn-danger`                     |
| **Toast Success**    | `✅ Título - Mensaje`                                            | `.toast.success`                  |
| **Toast Error**      | `🔴 Título - Mensaje`                                           | `.toast.error`                    |
| **Toast Warning**    | `⚠️ Título - Mensaje`                                           | `.toast.warning`                  |
| **Toast Critical**   | `🛑 Título - Mensaje`                                           | `.toast.critical`                 |
| **Modal**            | `┌─────────────────┐` + `│ Contenido │` + `└─────────────────┘` | `.modal`                          |
| **Tabs**             | `[Tab1] [Tab2] [Tab3]`                                          | `.tab-list`                       |
| **Table**            | `┌──┬────┬────┐` + `│  │    │    │`                             | `.data-table`                     |
| **Card**             | `┌─────────────┐` + `│ Contenido │` + `└─────────────┘`         | `.card`                           |
| **Badge**            | `🟢 Activo` `🔴 Bloqueado` `🟡 MFA`                             | `.badge.success/.danger/.warning` |
| **Icon**             | `🔍 👁️ 🔐 ⚠️ 🛑 ✅ ❌`                                           | `.icon`                           |

## Responsive Breakpoints
| Breakpoint  | Ancho      | Layout Login                   | Layout Admin                   | Layout Auditoría                   | Layout Paciente              |
|-------------|------------|--------------------------------|--------------------------------|------------------------------------|------------------------------|
| **Mobile**  | 320-480px  | Stack 100%, botones full-width | Cards apiladas, hamburger menu | Tabla → Cards, filtros colapsables | Stack vertical, nav inferior |
| **Tablet**  | 481-768px  | Centrado 400px                 | 2 columnas, sidebar colapsable | Tabla responsive (scroll H)        | 2 columnas grid              |
| **Desktop** | 769-1024px | Centrado 400px max             | Layout completo sidebar        | Tabla completa, filtros visibles   | 3-4 columnas grid            |
| **Wide**    | 1025px+    | Centrado 400px max             | Dashboard completo paneles     | Tabla ancha, panel detalle         | 4 columnas, sidebar fija     |

## Paleta de Colores (Accesible WCAG 2.1 AA)
| Uso                 | Hex       | RGB         | Contraste vs White | Uso                                           |
|---------------------|-----------|-------------|--------------------|-----------------------------------------------|
| **Primary**         | `#1976D2` | 25,118,210  | 4.5:1 ✅            | Botones primarios, links, focus ring          |
| **Primary Dark**    | `#1565C0` | 21,101,192  | 5.2:1 ✅            | Hover primary                                 |
| **Success**         | `#2E7D32` | 46,125,50   | 5.4:1 ✅            | Toast success, badges activo                  |
| **Warning**         | `#ED6C02` | 237,108,2   | 3.5:1 ✅            | Toast warning, badges pendiente               |
| **Danger**          | `#C62828` | 198,40,40   | 4.5:1 ✅            | Toast error, botones danger, badges bloqueado |
| **Critical**        | `#B71C1C` | 183,28,28   | 5.1:1 ✅            | Modal crítico, reuse detection                |
| **Surface**         | `#FFFFFF` | 255,255,255 | -                  | Backgrounds, cards                            |
| **Surface Variant** | `#F5F5F5` | 245,245,245 | -                  | Hover rows, disabled inputs                   |
| **Text Primary**    | `#212121` | 33,33,33    | 16:1 ✅             | Texto principal                               |
| **Text Secondary**  | `#757575` | 117,117,117 | 4.5:1 ✅            | Hints, placeholders, timestamps               |
| **Border**          | `#E0E0E0` | 224,224,224 | 3:1 ✅              | Input borders, table borders                  |
| **Error Border**    | `#C62828` | 198,40,40   | 4.5:1 ✅            | Input error, focus error                      |
| **Focus Ring**      | `#1976D2` | 25,118,210  | 4.5:1 ✅            | `outline: 3px solid #1976D2`                  |

## Espaciado y Tipografía (Design Tokens)
| Token                  | Valor           | Uso                               |
|------------------------|-----------------|-----------------------------------|
| **Space XS**           | 4px             | Icon gaps, inline elements        |
| **Space SM**           | 8px             | Input padding, button padding Y   |
| **Space MD**           | 16px            | Form field gap, card padding      |
| **Space LG**           | 24px            | Section gap, modal padding        |
| **Space XL**           | 32px            | Page margins, section separation  |
| **Radius SM**          | 4px             | Inputs, buttons, badges           |
| **Radius MD**          | 8px             | Cards, modals, dropdowns          |
| **Radius LG**          | 16px            | Large containers                  |
| **Font Size XS**       | 12px / 0.75rem  | Timestamps, hints, badges         |
| **Font Size SM**       | 14px / 0.875rem | Labels, help text, toast messages |
| **Font Size Base**     | 16px / 1rem     | Body text, inputs, buttons        |
| **Font Size LG**       | 18px / 1.125rem | Subheadings, card titles          |
| **Font Size XL**       | 24px / 1.5rem   | Page titles, modal titles         |
| **Font Weight Normal** | 400             | Body text                         |
| **Font Weight Medium** | 500             | Labels, button text               |
| **Font Weight Bold**   | 600             | Headings, emphasis                |
| **Line Height Base**   | 1.5             | Body text                         |
| **Line Height Tight**  | 1.25            | Headings, buttons                 |
