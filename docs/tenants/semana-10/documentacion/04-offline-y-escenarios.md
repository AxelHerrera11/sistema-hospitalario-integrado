## 6. Conectividad Limitada / Offline

### 6.1 Estrategia Offline-First

| Funcionalidad       | Online     | Offline            | Sync Strategy   |

|---------------------|------------|--------------------|-----------------|

| **Login**           | ✅ Completo | ❌ Requiere red     | -               |

| **MFA TOTP**        | ✅ Verifica | ✅ Funciona (local) | -               |

| **MFA WebAuthn**    | ✅ Verifica | ✅ Funciona (local) | -               |

| **Refresh Token**   | ✅ Rotación | ❌ Requiere red     | Queue + retry   |

| **Force-Revoke**    | ✅ Ejecuta  | ❌ Requiere red     | Queue + retry   |

| **Paciente Portal** | ✅ Completo | ✅ Cache (SW)       | Background sync |

| **Auditoría**       | ✅ Completo | ✅ Read cache       | Background sync |

### 6.2 Service Worker Strategy

```javascript

*// sw\.js - Service Worker para Paciente Portal*

const CACHE_NAME = 'patient-portal-v1';

const STATIC_ASSETS = [

  '/',

  '/patient/dashboard',

  '/patient/appointments',

  '/patient/medications',

  '/patient/records',

  '/offline.html'

];

*// Install - Cache estático*

self.addEventListener('install', (event) => {

  event.waitUntil(

    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))

  );

});

*// Fetch - Network First para API, Cache First para estáticos*

self.addEventListener('fetch', (event) => {

  const url = new URL(event.request.url);

  *// API calls - Network First con fallback a cache*

  if (url.pathname.startsWith('/api/')) {

    event.respondWith(

      fetch(event.request)

        .then((response) => {

          const clone = response.clone();

          caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));

          return response;

        })

        .catch(() => caches.match(event.request))

    );

    return;

  }

  *// Estáticos - Cache First*

  event.respondWith(

    caches.match(event.request).then((response) => {

      return response || fetch(event.request);

    })

  );

});

*// Background Sync para acciones pendientes*

self.addEventListener('sync', (event) => {

  if (event.tag === 'pending-actions') {

    event.waitUntil(processPendingActions());

  }

});

```

### 6.3 UI Offline Indicators

```

┌─────────────────────────────────────┐

│  ☁️ Sin conexión                  │

│  Los cambios se sincronizarán al    │

│  reconectar. Datos en caché visibles│

│  [ Reintentar conexión ]            │

└─────────────────────────────────────┘

← Banner superior, no bloqueante

```

```

┌─────────────────────────────────────┐

│  📱 Modo Offline                   │

│  ─────────────────────────────────  │

│  ✅ Citas cargadas (caché)          │

│  ✅ Medicación cargada (caché)      │

│  ⚠️ Resultados pendientes de sync   │

│  ⚠️ Cambios de seguridad pendientes │

│                                     │

│  Última sincronización: 2h atrás    │

│  [ Sincronizar ahora ]              │

└─────────────────────────────────────┘

```

---

## 7. Escenarios Móviles (2 Requeridos)

### 7.1 Escenario 1: Médico de Guardia - Login Rápido + MFA en Movimiento

**Contexto:**

\- **Usuario**: Dr. González (MEDICO)

\- **Contexto**: Guardia nocturna, caminando entre habitaciones, iPhone 13 (375px), una mano, guantes médicos

\- **Condiciones**: Iluminación tenue, una mano libre, posible interrupción urgente

**Flujo Optimizado:**

```

1\. Abre app → FaceID desbloquea → App abre en Login

2\. Tenant auto-seleccionado (solo 1) → Skip tenant selector

3\. Email: Autocomplete sugiere dra.gonzalez@hospital.com

3\. Password: FaceID autofill → Skip password entry

4\. MFA: NO requerido (rol MEDICO) → Skip MFA

5\. Redirect directo a Dashboard → Lista pacientes guardia

```

**Decisiones de Contenido/Error:**

| Punto              | Decisión                 | Justificación                    |

|--------------------|--------------------------|----------------------------------|

| Tenant selector    | Oculto (auto)            | Solo 1 tenant → reduce fricción  |

| Password           | Autofill FaceID          | Una mano, guantes → no typing    |

| MFA                | Skip (rol MEDICO)        | Política: MEDICO no requiere MFA |

| Error credenciales | Toast + "¿FaceID falló?" | Contexto: guantes, luz baja      |

| Sesión             | 8h extendida (guardia)   | Reduce re-logins                 |

**Error Handling:**

\- **FaceID falla** → Fallback a password + "¿Manos ocupadas? Use contraseña"

\- **Red lenta** → Spinner + "Conectando..." + cache dashboard previo

\- **Interrupción** (código azul) → App guarda estado → Resume al volver

### 7.2 Escenario 2: Admin Regional - Force-Revoke en Tablet + Conectividad Inestable

**Contexto:**

\- **Usuario**: Admin Regional (PLATFORM_ADMIN)

\- **Contexto**: Visita hospitales regionales, iPad Pro 11" (834px), WiFi hospitalario inestable, sentado en escritorio temporal

\- **Tarea**: Revocar sesiones de admin comprometido en hospital remoto

**Flujo Optimizado:**

```

1\. Abre Admin Panel → Hamburger menu → Gestión Usuarios

2\. Busca "admin.comprometido@hospital-sur.com" (búsqueda + filtros)

2\. Tap fila → Menú [⋮] → "Revocar TODAS las sesiones"

3\. Paso 1: Confirmar → "Continuar →"

3\. Paso 2: Motivo "Sospecha compromiso" + comentario "Credenciales filtradas en GitHub"

3\. Ejecutar → Toast "3 sesiones revocadas" + "Deshacer (5s)"

4\. Offline → Queue action → Sync al reconectar

```

**Decisiones de Contenido/Error:**

| Punto        | Decisión                                      | Justificación                    |

|--------------|-----------------------------------------------|----------------------------------|

| Layout       | 2 columnas (sidebar + content)                | Tablet 834px > 768px breakpoint  |

| Búsqueda     | Debounced 300ms + cache local                 | Reduce requests en red inestable |

| Force-Revoke | 2 pasos + "Deshacer 5s"                       | Previene click accidental tablet |

| Offline      | Queue + badge "Pendiente sync"                | Red inestable hospitalaria       |

| Error red    | Toast "Sin conexión. Acción en cola." + badge | No bloquea UI                    |

| Sync         | Background al reconectar                      | Silent, badge desaparece         |

**Error Handling Offline:**

```

┌─────────────────────────────────────┐

│  ☁️ Sin conexión                   │

│  Acción en cola. Se ejecutará al    │

│  reconectar.                        │

│  [ Ver acciones pendientes ]        │

└─────────────────────────────────────┘

← Banner superior, no bloqueante

┌─────────────────────────────────────┐

│  ⚠️ Acción pendiente (1)          │

│  ─────────────────────────────────  │

│  🔴 Revocar sesiones usuario X      │

│  Estado: En cola (sin conexión)     │

│  [ Reintentar ahora ]  [ Cancelar ] │

└─────────────────────────────────────┘

← Panel lateral / Modal "Acciones pendientes"

```
