# Semana 10 — Escenarios Móviles Detallados
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Flujo:** «Alta de usuario por tenant, autenticación segura y revocación de sesión»
**Versión:** 1.0 | **Fecha:** 2026-10-18

---

## Escenario 1: Médico de Guardia - Login Rápido + MFA en Movimiento

### Contexto del Usuario
| Atributo              | Valor                                                                                 |
|-----------------------|---------------------------------------------------------------------------------------|
| **Rol**               | MEDICO                                                                                |
| **Nombre**            | Dra. María González                                                                   |
| **Dispositivo**       | iPhone 13 (375px), iOS 17                                                             |
| **Contexto**          | Guardia nocturna, caminando entre habitaciones                                        |
| **Condiciones**       | Iluminación tenue, una mano libre (otra en historial/equipo), guantes médicos puestos |
| **Conectividad**      | WiFi hospitalario estable, pero movimiento entre zonas                                |
| **Tiempo disponible** | < 15 segundos para login                                                              |

### Flujo Optimizado (Happy Path)

```
1. Desbloqueo iPhone → FaceID → App abre en Login
   ↓
2. Tenant auto-seleccionado (solo 1 tenant) → Skip tenant selector
   ↓
3. Email: Autocomplete sugiere dra.gonzalez@hospital.com
   ↓
4. Password: FaceID autofill → Skip password entry manual
   ↓
5. MFA: NO requerido (rol MEDICO policy) → Skip MFA completely
   ↓
6. Redirect directo a Dashboard → Lista pacientes guardia
```

**Tiempo total objetivo: < 10 segundos**

### Decisiones de Diseño para este Escenario

| Punto de Decisión            | Decisión                                | Justificación                                    |
|------------------------------|-----------------------------------------|--------------------------------------------------|
| **Tenant Selector**          | **Oculto (auto-seleccionado)**          | Usuario solo pertenece a 1 tenant → elimina paso |
| **Password Entry**           | **FaceID Autofill**                     | Una mano ocupada, guantes puestos → zero typing  |
| **MFA**                      | **Skip completo**                       | Policy: MEDICO no requiere MFA por defecto       |
| **Biometría**                | FaceID → Login directo                  | iOS keychain + FaceID = zero friction            |
| **Tenant Selector Fallback** | Solo visible si usuario tiene >1 tenant | Progressive disclosure                           |

### Manejo de Errores Específicos

| Error                                  | Mensaje Mobile                                            | Acción de Recuperación                            |
|----------------------------------------|-----------------------------------------------------------|---------------------------------------------------|
| **FaceID falla**                       | "¿FaceID falló? Use contraseña" + botón "Usar contraseña" | Fallback a password + teclado numérico optimizado |
| **Red lenta**                          | Spinner + "Conectando..." + muestra dashboard cacheado    | Cache-first strategy con Service Worker           |
| **Interrupción urgente** (código azul) | App guarda estado automático → Resume al volver           | Session state persistence en localStorage         |
| **Batería baja**                       | Modo ahorro: desactiva animaciones, reduce polling        | Battery Status API integration                    |

### Wireframe Mobile (375px) - Login Optimizado

```
┌─────────────────────────────────────┐
│  🏥 SISTEMA HOSPITALARIO            │
│  ─────────────────────────────────  │
│                                     │
│   ┌───────────────────────────────┐  │
│   │  🔐 Iniciar Sesión            │  │
│   │                               │  │
│   │  ✅ FaceID detectado          │  │
│   │  dra.gonzalez@hospital.com    │  │
│   │  [ Usar FaceID ]              │  │
│   │                               │  │
│   │  ─────────────────────────────  │
│   │  ¿Problemas con FaceID?       │  │
│   │  [ Usar contraseña ]          │  │
│   │                               │  │
│   └───────────────────────────────┘  │
│                                     │
└─────────────────────────────────────┘

Auto-submit tras FaceID success → Redirect dashboard
```

---

## Escenario 2: Admin Regional - Force-Revoke en Tablet + Conectividad Inestable

### Contexto del Usuario
| Atributo         | Valor                                                     |
|------------------|-----------------------------------------------------------|
| **Rol**          | PLATFORM_ADMIN                                            |
| **Nombre**       | Admin Regional Carlos Ruiz                                |
| **Dispositivo**  | iPad Pro 11" (834px), iPadOS 17                           |
| **Contexto**     | Visita hospitales regionales, escritorio temporal         |
| **Conectividad** | WiFi hospitalario inestable (cortes intermitentes)        |
| **Tarea**        | Revocar sesiones de admin comprometido en hospital remoto |
| **Urgencia**     | Alta - Credenciales filtradas en GitHub público           |

### Flujo Optimizado (Resilient)

```
1. Abre Admin Panel → Hamburger menu → Gestión Usuarios
   ↓
2. Busca "admin.comprometido@hospital-sur.com" 
   (búsqueda debounced 300ms + cache local)
   ↓
3. Tap fila → Menú [⋮] → "Revocar TODAS las sesiones"
   ↓
3. Paso 1: Confirmar intención → "Continuar →"
   ↓
3. Paso 2: Motivo "Sospecha compromiso" + comentario
   "Credenciales filtradas en GitHub público gist"
   ↓
4. Ejecutar → Toast "3 sesiones revocadas" + "Deshacer (5s)"
   ↓
5. OFFLINE → Queue action → Badge "Pendiente sync" en header
   ↓
6. RECONEXIÓN → Background sync → Toast "Sincronizado" + badge desaparece
```

### Decisiones de Diseño para Conectividad Inestable

| Punto de Decisión    | Decisión                                               | Justificación                        |
|----------------------|--------------------------------------------------------|--------------------------------------|
| **Layout**           | 2 columnas (sidebar + content)                         | Tablet 834px > 768px breakpoint      |
| **Búsqueda**         | Debounced 300ms + cache local (IndexedDB)              | Reduce requests en red inestable     |
| **Force-Revoke**     | 2 pasos + "Deshacer 5s" toast                          | Previene click accidental en tablet  |
| **Offline Support**  | Queue action → IndexedDB + Background Sync             | Red inestable hospitalaria           |
| **Feedback Offline** | Banner "Sin conexión. Acción en cola" + badge contador | No bloquea UI, informa estado        |
| **Sync Reconexión**  | Background sync silencioso + badge desaparece          | Silent recovery, no interrumpe flujo |

### Wireframe Tablet (834px) - Admin List + Force-Revoke

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  ☰ Admin Panel          Hospital Central (HCH-001)    👤 Carlos Ruiz [≡]   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  ┌─────────┬──────────────────────────────────────────────────────────────┐ │
│  │ SIDEBAR │  👥 Gestión Usuarios                          [+ Nuevo]     │ │
│  │         │  ──────────────────────────────────────────────────────────  │ │
│  │ 👥      │  🔍 [________________________________________] [▼ Filtros]   │ │
│  │ 🏥      │  📅 [Exportar CSV] [Exportar PDF] [Columnas] [Densidad: ▼]  │ │
│  │ 🔐      │  ──────────────────────────────────────────────────────────  │ │
│  │ ⚙️      │  ┌──┬────────────────┬────────────┬──────────┬────────┬────┐ │ │
│  │         │  │☑ │ Usuario          │ Email         │ Roles      │ Est  │ ⋮ │ │
│  │ 🚪      │  ├──┼────────────────┼────────────────┼────────────┼──────┼───┤ │ │
│  │         │  │☐ │ admin.sur@hosp   │ admin.sur@hosp  │ 👑 ADMIN   │ 🟢   │ ⋮ │ │
│  │         │  ├──┼────────────────┼────────────────┼────────────┼──────┼───┤ │ │
│  │         │  │☐ │ ...              │ ...           │ ...        │ ...  │ ⋮ │ │
│  │         │  └──┴────────────────┴────────────────┴──────────┴──────┴────┘ │ │
│  │         │                                                                    │ │
│  │         │  ◄ Anterior  1  [2]  [3]  [4]  [5]  ...  [15]  Siguiente ►     │ │
│  └─────────┴────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘

Click [⋮] → Dropdown:
┌─────────────────────────────────────────────────────────────────────────────┐
│  [⋮] Acciones - admin.sur@hospital-sur.com              [×] (ESC)          │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  ✏️  Editar usuario                                                         │
│  ─────────────────────────────────────────────────────────────────────     │
│  🔑  Forzar MFA                                                            │
│  ─────────────────────────────────────────────────────────────────────     │
│  🔒  Bloquear usuario                                                      │
│  ─────────────────────────────────────────────────────────────────────     │
│  🔴  Revocar TODAS las sesiones    ← .btn-danger                          │
│  ─────────────────────────────────────────────────────────────────────     │
│  📋  Ver auditoría                                                         │
│  ─────────────────────────────────────────────────────────────────────     │
│  🗑️  Eliminar usuario (soft delete)                                       │
│  ─────────────────────────────────────────────────────────────────────     │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Modal Force-Revoke (2-Paso Tablet)

```
Paso 1: Confirmar Intención
┌─────────────────────────────────────────────────────────────────────────────┐
│  ⚠️ Confirmar Revocación                                              [×] │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  ¿Revocar TODAS las sesiones de <strong>admin.sur@hospital-sur.com</strong>?│
│                                                                             │
│  Se cerrarán sesión en TODOS los dispositivos (3 activos):                │
│    📱 iPhone 15 Pro (iOS) - Chrome 119  -  Hoy 08:30                     │
│    💻 MacBook Pro (macOS) - Safari 17  -  Ayer 14:22                     │
│    🔐 YubiKey 5C NFC (Passkey) -  Hace 2 horas                            │
│                                                                             │
│  El usuario deberá iniciar sesión nuevamente en todos los                  │
│  dispositivos. Los tokens de acceso serán invalidados inmediatamente      │
│  y los refresh tokens eliminados.                                         │
│                                                                             │
│  [ Cancelar ]                    [ Continuar → ]                          │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘

Paso 2: Ejecutar con Motivo
┌─────────────────────────────────────────────────────────────────────────────┐
│  🔴 Ejecutar Revocación                                               [×] │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  Motivo *:  ▼ [ Sospecha compromiso ▼ ]  [ Cambio de rol ]  [ Seguridad ] │
│                                                                             │
│  Comentario (opcional):                                                    │
│  [ Credenciales filtradas en GitHub gist público - Acción inmediata_____ ] │
│                                                                             │
│  ⚠️ Deshacer disponible 5 segundos tras ejecutar                          │
│                                                                             │
│  [ ← Volver ]                    [ REVOCAR AHORA ]  ← .btn-danger        │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Manejo Offline/Reconexión

```
Estado OFFLINE durante ejecución:
┌─────────────────────────────────────────────────────────────────────────────┐
│  ☁️ Sin conexión                                                           │
│  Acción en cola. Se ejecutará al reconectar.                               │
│  [ Ver acciones pendientes ]                                                │
└─────────────────────────────────────────────────────────────────────────────┘
← Banner superior, no bloqueante, dismissible

Panel "Acciones Pendientes" (accesible desde header/badge):
┌─────────────────────────────────────────────────────────────────────────────┐
│  ⚠️ Acciones pendientes (1)                                              [×] │
│  ─────────────────────────────────────────────────────────────────────────  │
│                                                                             │
│  🔴 Revocar sesiones - admin.sur@hospital-sur.com                         │
│  Estado: En cola (sin conexión)                                            │
│  [ Reintentar ahora ]  [ Cancelar ]                                        │
│                                                                             │
│  🔄 Se ejecutará automáticamente al reconectar                            │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Background Sync Flow (Reconexión)

```
1. Dispositivo reconecta a WiFi
   ↓
2. Service Worker detecta 'online' event
   ↓
3. Background Sync registrado ('pending-actions') se dispara
   ↓
3. Service Worker procesa cola IndexedDB:
   - POST /api/admin/users/{id}/sessions (DELETE)
   - Headers: Authorization, Content-Type
   - Body: { reason: "compromise", comment: "GitHub leak" }
   ↓
4. Respuesta 200 OK:
   - Elimina de IndexedDB
   - Dispara evento 'sync-complete'
   ↓
4. App recibe mensaje → Toast "✅ 3 sesiones revocadas. Sincronizado"
   Badge contador decrementa → 0
   ↓
5. Auditoría actualizada en background (fire-and-forget)
```

---

## Decisiones de Contenido/Error por Escenario

### Matriz de Decisiones Comunes

| Componente          | Escenario 1 (Médico Guardia) | Escenario 2 (Admin Tablet) |
|---------------------|------------------------------|----------------------------|
| **Tenant Selector** | Oculto (auto)                | Visible, búsqueda + chips  |
| **MFA**             | Skip (policy MEDICO)         | Requerido (policy ADMIN)   |
| **Biometría**       | FaceID obligatorio           | TouchID opcional + Passkey |
| **Error Red**       | Toast + cache dashboard      | Banner + queue + retry     |
| **Timeout**         | 30s (urgencia médica)        | 60s (admin deliberado)     |
| **Feedback**        | Toast + haptic               | Toast + badge + banner     |
| **Offline**         | Cache dashboard              | Queue + background sync    |
| **Error Crítico**   | Modal full-screen            | Modal + banner persistente |

---

## Matriz de Decisiones por Tipo de Error

| Tipo Error                 | Médico (Mobile)             | Admin (Tablet)                   | Paciente (Mobile)             |
|----------------------------|-----------------------------|----------------------------------|-------------------------------|
| **Credenciales inválidas** | Toast + "¿FaceID falló?"    | Toast + contador intentos        | Toast + "¿Olvidó?"            |
| **MFA fallido**            | N/A (skip)                  | Inline + "Reenviar" + countdown  | Inline + "Configurar Passkey" |
| **Cuenta bloqueada**       | Modal 15min + "Contactar"   | Modal + "Contactar Admin"        | Modal + "Desbloqueo auto"     |
| **Reuse Detection**        | N/A (no refresh en guardia) | Modal crítico + revocación total | Modal crítico + re-login      |
| **Red caída**              | Cache dashboard             | Queue + badge + banner           | Cache datos + banner          |
| **Mantenimiento**          | Página mantenimiento        | Página + "Vuelva luego"          | Página + "Vuelva luego"       |

---

## Checklist de Validación por Escenario

### ✅ Escenario 1: Médico Guardia (Mobile)
- [ ] Login < 10s con FaceID
- [ ] Skip tenant selector (solo 1)
- [ ] Skip MFA (policy MEDICO)
- [ ] FaceID fallback a password
- [ ] Cache dashboard offline
- [ ] Interrupción → state persistence
- [ ] Touch targets ≥ 44px
- [ ] Font size ≥ 16px (no zoom iOS)
- [ ] Haptic feedback en acciones críticas

### ✅ Escenario 2: Admin Regional (Tablet + Offline)
- [ ] Layout 2 columnas (sidebar fija)
- [ ] Búsqueda debounced + cache local
- [ ] Force-Revoke 2 pasos + "Deshacer 5s"
- [ ] Touch targets ≥ 48px (tablet)
- [ ] Offline queue + IndexedDB
- [ ] Background sync al reconectar
- [ ] Toast "Deshacer" 5s
- [ ] Badge contador acciones pendientes
- [ ] Sync silencioso en background
- [ ] Auditoría actualizada post-sync

---

## Métricas de Validación por Escenario

| Métrica                    | Escenario 1 (Médico) | Escenario 2 (Admin)  | Objetivo      |
|----------------------------|----------------------|----------------------|---------------|
| **Tiempo Login**           | < 10s                | < 30s                | < 15s / < 45s |
| **Tasa Éxito 1er Intento** | > 99%                | > 95%                | > 95%         |
| **Errores Críticos**       | 0%                   | < 1%                 | < 1%          |
| **Funcionamiento Offline** | Dashboard cache      | Queue + Sync         | Cache datos   |
| **Recuperación Error**     | < 5s                 | < 10s (con Deshacer) | < 15s         |
| **Satisfacción (NPS)**     | > 70                 | > 60                 | > 60          |

