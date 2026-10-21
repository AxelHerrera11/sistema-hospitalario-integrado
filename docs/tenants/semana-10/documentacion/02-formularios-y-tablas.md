## 4. Formularios y Tablas Mobile

### 4.1 Formularios Responsive

#### Login Form (Mobile)

```

┌─────────────────────────────────────┐

│  🔐 Iniciar Sesión                  │

│  ─────────────────────────────────  │

│                                     │

│  Tenant: Hospital Central ▼  [×]    │  ← Dropdown nativo en mobile

│  ─────────────────────────────────  │

│                                     │

│  Email institucional          👤    │

│  [______________________________]   │  ← 100% width, 16px font

│                                     │

│  Contraseña                   👁️    │

│  [______________________________]   │  ← Toggle visibility

│                                     │

│  [☐ Recordarme 30 días]             │  ← Checkbox 44x44px

│                                     │

│  [ INICIAR SESIÓN ]                 │  ← 100% width, 48px height

│                                     │

│  ¿Olvidó? | ¿Problemas MFA?         │

│                                     │

└─────────────────────────────────────┘

```

**HTML Semántico:**

```html

<form novalidate>

  <div class="form-field">

    <label for="tenant">Organización</label>

    <select id="tenant" name="tenant" class="native-select">

      <option value="hch-001">Hospital Central (HCH-001)</option>

    </select>

  </div>

  <div class="form-field">

    <label for="email">Email institucional</label>

    <input 

      type="email" 

      id="email" 

      name="email" 

      autocomplete="email"

      inputmode="email"

      required

      aria-describedby="email-hint"

    >

    <span id="email-hint">Use @hospital.com</span>

  </div>

  <div class="form-field password-field">

    <label for="password">Contraseña</label>

    <div class="password-wrapper">

      <input 

        type="password" 

        id="password" 

        name="password" 

        autocomplete="current-password"

        required

        aria-describedby="pwd-hint"

      >

      <button 

        type="button" 

        class="toggle-visibility" 

        aria-label="Mostrar contraseña"

        aria-pressed="false"

      >

        👁️

      </button>

    </div>

    <span id="pwd-hint">Mín 12 chars, mayús, minús, num, símbolo</span>

  </div>

  <button type="submit" class="btn-primary full-width">

    <span class="btn-text">Iniciar Sesión</span>

    <span class="spinner" hidden></span>

  </button>

</form>

```

#### Admin Create User (Mobile - Stepper)

```

Paso 1/3: Datos Básicos

┌─────────────────────────────────────┐

│  ➕ Nuevo Usuario           [×]     │

│  ─────────────────────────────────  │

│                                     │

│  Email \*                            │

│  [______________________________]   │

│                                     │

│  Nombre \*                           │

│  [______________________________]   │

│                                     │

│  [ SIGUIENTE ]                      │

│                                     │

└─────────────────────────────────────┘

Paso 2/3: Credenciales

┌─────────────────────────────────────┐

│  ➕ Nuevo Usuario (2/3)       [×]   │

│  ─────────────────────────────────  │

│                                     │

│  Contraseña \*               👁️      │

│  [______________________________]   │

│  Fortaleza: 🟢🟢🟢🟢⚪  FUERTE       │

│                                     │

│  [ ANTERIOR ]        [ SIGUIENTE ]  │

│                                     │

└─────────────────────────────────────┘

```

### 4.2 Tablas Responsive

#### Admin User List - Mobile Cards

```

┌─────────────────────────────────────┐

│  👥 Usuarios          [+] [☰]       │

│  ─────────────────────────────────  │

│  🔍 [___________________] [▼]       │

│  ─────────────────────────────────  │

│                                     │

│  ┌───────────────────────────────┐  │

│  │ 👤 Dra. González              │  │

│  │ 📧 dra.g@hch.com              │  │

│  │ 🏷️ MEDICO        🟢 Activo    │  │

│  │ ─────────────────────────     │  │

│  │ [⋮] Acciones                  │  │

│  └───────────────────────────────┘  │

│                                     │

│  ┌───────────────────────────────┐  │

│  │ 👤 Lic. Ruiz                  │  │

│  │ 📧 lic.r@hch.com              │  │

│  │ 🏷️ ENFERMERO      🟢 Activo   │  │

│  │ ─────────────────────────     │  │

│  │ [⋮] Acciones                  │  │

│  └───────────────────────────────┘  │

│                                     │

│  ◄ 1 [2] [3] [4] [5] ►   20/pág    │

└─────────────────────────────────────┘

```

**CSS Card Layout:**

```css

@media (max-width: 767px) {

  .user-table { display: none; }

  .user-cards { display: block; }

  .user-card {

    background: #FFFFFF;

    border: 1px solid #E0E0E0;

    border-radius: 8px;

    padding: 16px;

    margin-bottom: 12px;

    box-shadow: 0 1px 3px rgba(0,0,0,0.08);

  }

  .user-card-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-start;

    margin-bottom: 12px;

  }

  .user-card-actions {

    display: flex;

    gap: 8px;

    margin-top: 12px;

    padding-top: 12px;

    border-top: 1px solid #F5F5F5;

  }

}

```

#### Audit Logs - Mobile Cards

```

┌─────────────────────────────────────┐

│  📋 Auditoría              [⚙️]      │

│  ─────────────────────────────────  │

│  🔍 [Filtros ▼]  [Exportar]         │

│  ─────────────────────────────────  │

│                                     │

│  ┌───────────────────────────────┐  │

│  │ 🔴 REFRESH_REUSE_DETECTED     │  │

│  │ ─────────────────────────────  │  │

│  │ 🕐 2026-10-18 10:45:00        │  │

│  │ 👤 sys (service-account)      │  │

│  │ 🏷️ CRÍTICO                     │  │

│  │ 🔍 Ver detalle ▶              │  │

│  └───────────────────────────────┘  │

│                                     │

│  ┌───────────────────────────────┐  │

│  │ 🟢 LOGIN_SUCCESS              │  │

│  │ ─────────────────────────────  │  │

│  │ 🕐 2026-10-18 11:20:05        │  │

│  │ 👤 dra.gonzalez                │  │

│  │ 🏷️ INFO                        │  │

│  │ 🔍 Ver detalle ▶              │  │

│  └───────────────────────────────┘  │

│                                     │

│  ◄ 1 [2] [3] [4] [5] ►   20/pág    │

└─────────────────────────────────────┘

```

---
