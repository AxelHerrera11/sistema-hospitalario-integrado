## 5. Admin - Lista Usuarios + Force-Revoke (Mobile)
### Lista - Cards Apiladas
```
┌─────────────────────────────────────┐
│  👥 Usuarios HCH        [+] [☰]     │
├─────────────────────────────────────┤
|  🔍 [___________________] [▼]       |
├─────────────────────────────────────┤
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 👤 Dra. González              │  |
|  │ 📧 dra.g@hch.com              │  |
|  │ 🏷️ MEDICO        🟢 Activo    │  |
|  │ ─────────────────────────     │  |
|  │ [⋮] Acciones                  │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 👤 Lic. Ruiz                  │  |
|  │ 📧 lic.r@hch.com              │  |
|  │ 🏷️ ENFERMERO       🟢 Activo  │  |
|  │ ─────────────────────────     │  |
|  │ [⋮] Acciones                  │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 👤 Dr. Silva                  │  |
|  │ 📧 dr.s@hch.com               │  |
|  │ 🏷️ AUDITOR        🟡 MFA      │  |
|  │ ─────────────────────────     │  |
|  │ [⋮] Acciones                  │  |
|  └───────────────────────────────┘  |
|                                     |
|  ◄ 1 [2] [3] [4] [5] ►   20/pág    |
└─────────────────────────────────────┘
```

### Menú Acciones [⋮] - Touch Friendly
```
┌─────────────────────────────────────┐
|  [⋮] Acciones - Dra. Torres       [×] │
├─────────────────────────────────────┤
|                                     |
|  ✏️  Editar usuario                |
|  ─────────────────────────────────  |
|  🔑  Forzar MFA                    |
|  ─────────────────────────────────  |
|  🔒  Bloquear usuario              |
|  ─────────────────────────────────  |
|  🔴  Revocar TODAS las sesiones    │  ← .btn-danger
|                                    |
└─────────────────────────────────────┘
```

### Modal Force-Revoke (Mobile Full-Screen)
```
┌─────────────────────────────────────┐
|  🔴 Revocar Sesiones - Dra. Torres [×] │
├─────────────────────────────────────┤
|                                     |
|  ⚠️ Revocar TODAS las sesiones de   |
|  Dra. Torres                        |
|                                     |
|  Se cerrarán sesión en TODOS los    |
|  dispositivos (3 activos):          |
|  📱 iPhone 15 Pro - Chrome 119 - Hoy│
|  💻 MacBook Pro - Safari 17 - Ayer  |
|  🔐 YubiKey 5C NFC - Hace 2 horas   |
|                                     |
|  El usuario deberá iniciar sesión   |
|  nuevamente en todos los dispositivos.|
|                                     |
|  Motivo *:                          |
|  ▼ [ Seguridad ▼ ] [ Cambio rol ]   |
|  [ Sospecha compromiso ] [ Otro... ] |
|                                     |
|  Comentario (opcional):             |
|  [________________________________] |
|                                     |
|  ⚠️ IRREVERSIBLE. Se auditará.      |
|                                     |
|  [ Cancelar ]      [ REVOCAR ]      │  ← 100% width, 48px height
|                                     |
└─────────────────────────────────────┘
```

### Toast Éxito Mobile
```
┌─────────────────────────────────────┐
|  ✅ 3 sesiones revocadas para       │
|  Dra. Torres                        |
|  Motivo: Seguridad                  |
|  [ Ver en Auditoría ] [ Deshacer ]  │
└─────────────────────────────────────┘
← Bottom fixed, 100% width, safe-area
```

---

## 7. Auditoría Mobile (Cards)

```
┌─────────────────────────────────────┐
|  📋 Auditoría - HCH          [⚙️]   │
├─────────────────────────────────────┤
|  🔍 [Filtros ▼]  [Exportar]         |
├─────────────────────────────────────┤
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 🔴 REFRESH_REUSE_DETECTED     │  |
|  │ ─────────────────────────────  │  |
|  │ 🕐 2026-10-18 10:45:00        │  |
|  │ 👤 sys (service-account)      │  |
|  │ 🏷️ CRÍTICO                     │  |
|  │ 🔍 Ver detalle ▶              │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 🟢 LOGIN_SUCCESS              │  |
|  │ ─────────────────────────────  │  |
|  │ 🕐 2026-10-18 11:20:05        │  |
|  │ 👤 dra.gonzalez               │  |
|  │ 🏷️ INFO                        │  |
|  │ 🔍 Ver detalle ▶              │  |
|  └───────────────────────────────┘  |
|                                     |
|  ◄ 1 [2] [3] [4] [5] ►   20/pág    |
└─────────────────────────────────────┘

Touch: Tap card → Expande detalle (slide-up)
Swipe left → Acciones rápidas [Ver en SIEM] [Exportar]
Long press → Selección múltiple
Pull to refresh → Actualiza lista
```

---

## 8. Paciente Portal Mobile

### Dashboard Apilado
```
┌─────────────────────────────────────┐
│  🏥 Mi Portal - María Paz    👤 [≡] │
├─────────────────────────────────────┤
|  Hola, María              [🔔 3]    |
├─────────────────────────────────────┤
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 📅 PRÓXIMAS CITAS             │  |
|  │ ─────────────────────────────  │  |
|  │ 📅 2026-10-20 10:00           │  |
|  │ 🏥 Cardiología - Dr. González │  |
|  │ [ Ver ] [ 🗓️ ]               │  |
|  ├─────────────────────────────┤  |
|  │ 📅 2026-10-25 09:30          │  |
|  │ 🏥 Endocrinología - Dra. Torres│ │
|  │ [ Ver ] [ 🗓️ ]               │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 💊 MEDICACIÓN ACTUAL          │  |
|  │ ─────────────────────────────  │  |
|  │ 💊 Losartán 50mg              │  |
|  │ 1 comp/día - Desayuno         │  |
|  ├─────────────────────────────┤  |
|  │ 💊 Metformina 850mg           │  |
|  │ 2 comp/día - Comida/Cena      │  |
|  └───────────────────────────────┘  |
|                                     |
|  ┌───────────────────────────────┐  |
|  │ 📋 RESULTADOS RECIENTES       │  |
|  │ ─────────────────────────────  │  |
|  │ 📄 Hemograma 2026-10-15      │  |
|  │ [ Ver ]                       │  |
|  ├─────────────────────────────┤  |
|  │ 📄 Radiografía 2026-10-10   │  |
|  │ [ Ver ]                      │  |
|  └───────────────────────────────┘  |
|                                     |
|  ─────────────────────────────────  |
|  📄 Expediente | 📅 Citas | 💊 Meds │
|  ─────────────────────────────────  |
|  🔒 Último: 17/10 19:30             |
|  🔐 MFA: No configurado [Configurar]│
└─────────────────────────────────────┘

Bottom Nav (Fixed):
┌─────────────────────────────────────┐
| 📄 Expediente  📅 Citas  💊 Meds  ⚙️ │
└─────────────────────────────────────┘
```

---

## 9. Touch Targets & Accesibilidad Mobile

| Elemento            | Tamaño Mínimo | Implementación                            |
|---------------------|---------------|-------------------------------------------|
| Botones Primarios   | 48×48px       | `min-height: 48px; min-width: 48px;`      |
| Botones Secundarios | 44×44px       | `min-height: 44px; min-width: 44px;`      |
| Inputs              | 48px height   | `height: 48px; font-size: 16px;`          |
| Checkboxes/Radios   | 24×24px       | `width: 24px; height: 24px; accent-color` |
| Toggle Switches     | 52×28px       | Custom CSS                                |
| Dropdown Items      | 48px height   | `padding: 12px 16px; min-height: 48px`    |
| Modal Buttons       | 48px height   | `min-height: 48px; width: 100%`           |
| Toast Actions       | 44px height   | `padding: 12px 16px; min-height: 44px`    |

### Safe Area Support
```css
/* iPhone notch / Dynamic Island */
.bottom-nav {
  padding-bottom: env(safe-area-inset-bottom, 0);
}

.modal-content {
  padding-bottom: env(safe-area-inset-bottom, 16px);
}

.fixed-footer {
  padding-bottom: env(safe-area-inset-bottom, 0);
}
```

### Haptic Feedback (iOS)
```javascript
// En botones de acción crítica
button.addEventListener('click', () => {
  if (navigator.vibrate) {
    navigator.vibrate(10); // Light tap
  }
});

// En error crítico
button.addEventListener('click', () => {
  if (navigator.vibrate) {
    navigator.vibrate([50, 50, 50]); // Triple pulse
  }
});
```

---

## 10. Testing Matrix Mobile

| Test                  | Dispositivo                             | Navegador               | Checklist                 |
|-----------------------|-----------------------------------------|-------------------------|---------------------------|
| **Visual Regression** | iPhone SE, 13, 14, 15, iPad, Galaxy S23 | Chrome, Safari, Firefox | Percy/Chromatic           |
| **Accessibility**     | NVDA, VoiceOver, TalkBack               | Chrome, Safari          | axe-core + manual         |
| **Performance**       | iPhone SE (throttled 3G)                | Chrome DevTools         | Lighthouse ≥ 90           |
| **Touch Testing**     | iPhone 13, iPad, Galaxy Tab             | Safari, Chrome          | Touch targets, gestures   |
| **Offline**           | Flight mode + throttled                 | Chrome DevTools         | Service Worker, Queue     |
| **Orientation**       | Portrait + Landscape                    | Todos                   | Lock/Unlock, layout shift |
