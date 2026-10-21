# Semana 12 — Script de Demo Navegable (Prototipo Funcional)
## 1. Preparación del Entorno
### Prerrequisitos
```bash
git clone <repo-url>
cd prototype
npm install
cp .env.example .env.local
docker-compose up -d postgres redis
npm run dev
```
### Credenciales de Demo
| Rol                | Email                       | Password        | Tenant           |
|--------------------|-----------------------------|-----------------|------------------|
| **PLATFORM_ADMIN** | platform.admin@hospital.com | DemoPass123!    | Platform         |
| **TENANT_ADMIN**   | admin.central@hospital.com  | DemoPass123!    | Hospital Central |
| **MEDICO**         | dra.gonzalez@hospital.com   | SecurePass123!  | Hospital Central |
| **ENFERMERO**      | lic.ruiz@hospital.com       | SecurePass123!  | Hospital Central |
| **AUDITOR**        | auditor@hospital.com        | SecurePass123!  | Hospital Central |
| **PACIENTE**       | maria.paz@paciente.com      | PatientPass123! | Hospital Central |

## 2. Happy Path Demo Script (15 min)
### Paso 1: Login → Dashboard (3 min)
#### Desktop (1024px+)
```
1. Abrir http://localhost:3000/auth/login
   ✅ Tenant auto-seleccionado: "Hospital Central (HCH-001)"
   ✅ Email autocomplete: dra.gonzalez@hospital.com
   ✅ FaceID/TouchID autofill password (mobile demo)
   ✅ MFA: SKIP (rol MEDICO no requiere)
   → Redirect a /dashboard (role: MEDICO)

2. Dashboard MEDICO:
   ✅ Header: "Dra. María González | MEDICO | Hospital Central"
   ✅ Cards: Próximas citas (2), Medicación activa (3), Resultados recientes (2)
   ✅ Navegación lateral: Expedientes | Citas | Medicación | Resultados | Config
```
#### Mobile (375px)
```
1. Abrir http://localhost:3000/auth/login (Chrome DevTools device toolbar: iPhone 13)
   ✅ Stack vertical, inputs 100% width
   ✅ FaceID autofill simulado → click "Usar FaceID"
   ✅ MFA SKIP (rol MEDICO)
   → Redirect a /dashboard
   ✅ Bottom Nav fijo: 📄 Expediente | 📅 Citas | 💊 Medicación | ⚙️ Config
   ✅ Cards apiladas verticalmente
   ✅ Touch targets ≥ 48px
```
**Puntos Clave a Destacar:**
- [ ] Tenant auto-seleccionado (solo 1 tenant → skip selector)
- [ ] FaceID autofill → zero typing con guantes
- [ ] MFA SKIP (policy MEDICO no requiere)
- [ ] Redirect < 3s total
- [ ] Mobile: Bottom nav fijo, safe-area-inset-bottom

### Paso 2: MFA Challenge (TOTP + WebAuthn) (3 min)
#### Desktop
```
1. Login con TENANT_ADMIN: admin.central@hospital.com / DemoPass123!
   ✅ MFA REQUIRED (policy: TENANT_ADMIN siempre)
   
2. MFA Challenge Screen:
   ✅ TOTP: 6 inputs auto-advance + countdown 30s
   ✅ WebAuthn option: "Usar FaceID / TouchID / Llave"
   ✅ Countdown visual: barra progreso + color change <10s
   ✅ Código: 123456 → Auto-advance → Verify
   
3. WebAuthn Flow (opcional):
   Click "Usar FaceID" → navigator.credentials.get() → TouchID/FaceID
   ✅ Success → Tokens generados
```
#### Mobile
```
1. Login TENANT_ADMIN
2. MFA Challenge:
   ✅ 6 inputs numéricos 100% width c/u, auto-advance
   ✅ Countdown circular + texto "25s"
   ✅ WebAuthn: "Usar FaceID / TouchID" → navigator.credentials.get()
   ✅ Haptic feedback en éxito (navigator.vibrate)
```
**Puntos Clave:**
- [ ] Auto-advance en inputs TOTP (6 dígitos)
- [ ] Countdown visual + color change <10s (warning) / <5s (critical)
- [ ] WebAuthn fallback funcional
- [ ] Mobile: inputs 100% width, touch targets 48px
- [ ] Haptic feedback en éxito (iOS)

### Paso 3: Admin - Force-Revoke (5 min)
#### Desktop
```
1. Navegar a /admin/users (TENANT_ADMIN)
   ✅ Tabla paginada, filtros colapsables, búsqueda debounced
   
2. Click fila Dra. Torres → Menú [⋮] → "Revocar TODAS las sesiones"
   ✅ Modal Paso 1: Confirmar
      • Lista 3 sesiones activas con device info
      • "Continuar →" button
   
2. Paso 2: Ejecutar
   ✅ Motivo dropdown: "Seguridad" | "Cambio rol" | "Sospecha compromiso" | "Dispositivo perdido"
   ✅ Comentario opcional: "Credenciales filtradas en GitHub gist público"
   ✅ Warning: "Deshacer disponible 5 segundos"
   ✅ Botón "REVOCAR AHORA" (danger, full-width mobile)
   
3. Toast Éxito:
   ✅ "3 sesiones revocadas para Dra. Torres"
   ✅ Motivo: Seguridad
   ✅ Botón "Deshacer (4s)" → Click → Sesiones restauradas + Auditoría
```
#### Mobile
```
1. Lista usuarios → Cards apiladas
2. Tap [⋮] → "Revocar TODAS las sesiones"
3. Modal Full-screen (safe-area-inset-bottom)
   ✅ Touch targets ≥ 48px
   ✅ Gap 12px entre botones
   ✅ Paso 1 → Paso 2 fluido
3. Toast bottom-fixed:
   ✅ "3 sesiones revocadas" + "Deshacer (4s)"
   ✅ Swipe to dismiss
   ✅ Safe-area-inset-bottom
```
**Puntos Clave:**
- [ ] 2-paso confirmation (previene click accidental)
- [ ] Touch targets ≥ 48px mobile
- [ ] Toast "Deshacer" 5s con countdown visual
- [ ] Auditoría registra REVOKE_SESSION + requester_id
- [ ] Evento SessionRevokedEvent publicado

### Paso 4: Auditoría (3 min)
```
1. Navegar a /audit/logs
   ✅ Filtros colapsables (Desktop) / Colapsable (Mobile)
   ✅ Filtros: Fecha, Usuario, Evento, Módulo, Severidad
   
2. Tabla → Click fila ▶ → Expande detalle inline
   ✅ Evento REFRESH_REUSE_DETECTED (CRÍTICO)
   ✅ Detalle: Hash Chain verificado ✅ + Acciones automáticas
   
3. Click fila → Modal Detalle Full-Screen
   ✅ Hash Chain verificado ✅ (a1b2c3d4... → e5f6g7h8...)
   ✅ Acciones automáticas listadas
   ✅ Botones: [Ver en SIEM] [Crear Jira] [Exportar JSON] [Cerrar]
   
3. Exportar → Modal opciones
   ✅ CSV/JSON/PDF/JSONL
   ✅ Columnas seleccionables
   ✅ Filtros aplicados incluidos
```
### Paso 5: Paciente Portal (Mobile)
```
1. Login PACIENTE: maria.paz@paciente.com
   ✅ Dashboard: 4 cards apiladas (Citas, Medicación, Resultados, Seguridad)
   
2. Tap "Ver" en cita → Modal detalle
   ✅ Detalle cita + [Ver en mapa] + [Agregar a calendario]
   
3. Seguridad → MFA Onboarding
   ✅ Paso 1: QR TOTP + Secreto manual
   ✅ Paso 2: Verificar código + Recovery codes (10)
   ✅ Paso 3: Passkey opcional
```
## 4. Critical Error Path Demo (5 min)
### Escenario: Refresh Reuse Detection
#### Simulación
```bash
curl -X POST http://localhost:3000/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "rt_compromised_reused_xyz"}'
```
#### Desktop - Modal Crítico Full-Screen
```
┌─────────────────────────────────────────────────────────────────┐
│   ACTIVIDAD SOSPECHOSA DETECTADA                         [×]    │
├─────────────────────────────────────────────────────────────────┤
|   SE HA DETECTADO REUTILIZACIÓN DE TOKEN DE ACTUALIZACIÓN       |
|  Por seguridad, se han ejecutado AUTOMÁTICAMENTE:               |
|   Revocación TOTAL de todas las sesiones (3)                    |
|   Invalidación tokens acceso (blocklist)                        |
|   Eliminación refresh tokens                                    |
|   Alerta CRÍTICA a SIEM + Jira SEC-2026-XXXX + Email            |
|  [ INICIAR SESIÓN DE NUEVO ]  ← Primary, full-width, focus      |
|  [ Ver detalles de seguridad ] → /security/incident/SEC-...     |
└─────────────────────────────────────────────────────────────────┘
```
**Puntos Clave:**
- [ ] Full-screen, no dismissible (no Escape, no backdrop click)
- [ ] Focus auto en botón principal
- [ ] Focus trap (Tab cycle dentro)
- [ ] Accesibilidad: `role="alertdialog"`, `aria-modal="true"`
- [ ] Link a detalles de seguridad

#### Mobile - Full-Screen Modal
```
┌─────────────────────────────────────┐
|   ACTIVIDAD SOSPECHOSA        [×]   │
├─────────────────────────────────────┤
|   REUTILIZACIÓN DE TOKEN            |
|  DETECTADA                          |
|  Por seguridad, TODAS sus sesiones  |
| han sido REVOCADAS automáticamente  |
|   3 sesiones cerradas               |
|   Tokens invalidados                |
|   Alerta enviada a seguridad        |
|  [ INICIAR SESIÓN DE NUEVO ]        │  ← Full width, 48px height
|  [ Ver detalles ]                   |
└─────────────────────────────────────┘
```
### Forced Re-login → New MFA
```
1. Click "INICIAR SESIÓN DE NUEVO" → /auth/login?security=reuse_detected
   ✅ Banner warning: "Sus sesiones fueron revocadas por seguridad"
   ✅ MFA OBLIGATORIO (policy: reuse_detected → forced MFA)

2. Login + MFA → Nuevos tokens
   ✅ Nuevos JTI + Refresh token rotado
   ✅ Badge verde "Nueva sesión iniciada" (5s)
   ✅ Toast "Sesión restablecida. MFA verificado."
```
## 5. Testing Checklist para Evaluador
### Happy Path
| TC     | Flujo         | Acción                  | Resultado Esperado          | ✓ |
|--------|---------------|-------------------------|-----------------------------|---|
| HP-001 | Login         | Email válido + password | Redirect MFA/Dashboard      | ☐ |
| HP-002 | MFA           | TOTP válido             | Tokens + redirect Dashboard | ☐ |
| HP-003 | Dashboard     | Carga según rol         | UI role-based visible       | ☐ |
| HP-004 | Admin Users   | Lista + filtros         | 20 usuarios/página          | ☐ |
| HP-005 | Force-Revoke  | 2-paso + Toast          | "3 revocadas" + "Deshacer"  | ☐ |
| HP-006 | Deshacer (5s) | Click "Deshacer"        | Sesiones restauradas        | ☐ |
| HP-007 | Auditoría     | Tabla + expand + export | CSV/JSON/PDF OK             | ☐ |

### Error Crítico
| TC     | Flujo          | Acción                   | Resultado Esperado   | ✓ |
|--------|----------------|--------------------------|----------------------|---|
| CE-001 | Refresh Reuse  | Token reutilizado        | 401 + Modal Crítico  | ☐ |
| CE-002 | Modal Crítico  | Full-screen + Focus trap | No dismissible       | ☐ |
| CE-003 | Re-login       | Click "Iniciar sesión"   | Redirect login + MFA | ☐ |
| CE-004 | Re-login + MFA | Tokens nuevos            | Nuevos JTI + Refresh | ☐ |
| CE-010 | Accesibilidad  | axe-core + NVDA          | 0 violations AA      | ☐ |

### Responsive
| TC    | Breakpoint | Componente   | Verificación                          |
|-------|------------|--------------|---------------------------------------|
| R-001 | 320px      | Login        | Stack 100%, botones full-width        |
| R-002 | 375px      | MFA          | 6 inputs 100% width, auto-advance     |
| R-003 | 768px      | Admin List   | Sidebar fija + tabla                  |
| R-004 | 1024px     | Admin List   | Sidebar fija + tabla completa         |
| R-005 | 375px      | Force-Revoke | Full-screen modal, touch targets 48px |

### Accesibilidad
| TC     | Criterio           | Verificación               |
|--------|--------------------|----------------------------|
| AC-001 | 1.4.3 Contraste    | axe-core 0 violations AA   |
| AC-002 | 2.1.1 Teclado      | Tab navigation completa    |
| AC-003 | 2.4.7 Foco visible | Focus ring 3px visible     |
| AC-004 | 3.3.1 Error ID     | aria-invalid + describedby |
| AC-005 | 4.1.2 ARIA         | role/name/value correctos  |

## 6. Script de Ejecución para Evaluador
```bash
echo "🚀 Iniciando Demo Semana 12 - Segunda Evaluación Parcial"
echo "📦 Verificando servicios..."
docker-compose up -d postgres redis
sleep 5
echo "🗄️ Verificando BD..."
npm run db:migrate:status
echo "🔨 Building..."
npm run build
npm run dev &
DEV_PID=$!
sleep 10
echo "✅ Prototipo listo en http://localhost:3000"
echo ""
echo "🎭 CREDENCIALES DE DEMO:"
echo "  PLATFORM_ADMIN: platform.admin@hospital.com / DemoPass123!"
echo "  TENANT_ADMIN:   admin.central@hospital.com / DemoPass123!"
echo "  MEDICO:         dra.gonzalez@hospital.com / SecurePass123!"
echo "  ENFERMERO:      lic.ruiz@hospital.com / SecurePass123!"
echo "  AUDITOR:        auditor@hospital.com / SecurePass123!"
echo "  PACIENTE:       maria.paz@paciente.com / PatientPass123!"
echo ""
echo "🎯 DEMO SCRIPT: Ver demo-script.md"
echo "🌐 Abrir: http://localhost:3000"
trap "kill $DEV_PID; docker-compose down" EXIT
wait $DEV_PID
```
## 6. Preguntas Frecuentes (FAQ para Evaluador)
| Pregunta                                                  | Respuesta                                                                                                                                                       |
|-----------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **¿Por qué Clean Architecture y no capas tradicionales?** | Separación estricta: Domain 0 deps externos, Use Cases orquestan, Infrastructure intercambiable (PDO/Redis/InMemory). Facilita testing y evolución.             |
| **¿Por qué JWT RS256 y no HS256?**                        | Claves asimétricas: validación distribuida sin secreto compartido, JWKS rotation 24h, no-repudiation. HS256 requiere secreto compartido en todos los servicios. |
| **¿Por qué Refresh Token Rotación + Reuse Detection?**    | Detecta token compromise inmediato. Si refresh token usado 2 veces → revocación total + alerta CRITICAL. Mitiga token theft.                                    |
| **¿Por qué Force-Revoke 2 pasos + Deshacer?**             | Previene click accidental (especialmente mobile/tablet). Deshacer 5s restaura sesiones + auditoría. UX safety-first.                                            |
| **¿Por qué Clean Architecture y no Microservicios?**      | Acoplamiento transaccional fuerte (central_users + hospital_memberships). Single writer pattern. Triggers para extracción futura si triggers medibles.          |
| **¿Cómo maneja multi-tenancy?**                           | RLS PostgreSQL + X-Tenant-ID header + TenantMiddleware + CentralUserReadOnlyRepository (read-only para otros módulos). Single writer pattern.                   |
| **¿Por qué Refresh Token Rotation + Reuse Detection?**    | Detecta token compromise en tiempo real. Reuse → revocación total + alerta CRITICAL + Jira + SIEM. Zero-trust.                                                  |
| **¿Cómo maneja offline?**                                 | Service Worker + IndexedDB queue + Background Sync. Login requiere online; MFA TOTP funciona offline; Patient Portal cache-first.                               |
| **¿Cómo escala?**                                         | Stateless Auth (JWT), Redis Cluster (sessions/blocklist), PostgreSQL Patroni HA, K8s HPA. Stateless services = horizontal scaling.                              |

## 6. Entregables de la Evaluación
| Entregable                      | Ubicación                                                   | Estado |
|---------------------------------|-------------------------------------------------------------|--------|
| Presentación Defensa (8 slides) | `docs/tenants/semana-12/segunda-evaluacion-parcial.md`      | ✅      |
| Demo Script                     | `docs/tenants/semana-12/demo-script.md`                     | ✅      |
| Prototipo Navegable             | `prototype/` (React + Vite)                                 | ✅      |
| Matriz Decisión→Evidencia       | `docs/tenants/semana-11/matriz-decision-evidencia.md`       | ✅      |
| Matriz Trazabilidad RF/RNF      | `docs/tenants/semana-06/matriz-trazabilidad-rf-rnf.md`      | ✅      |
| Happy Path Screens              | `docs/tenants/semana-11/prototype-happy-path.md`            | ✅      |
| Error Path Screens              | `docs/tenants/semana-11/prototype-error-path.md`            | ✅      |
| Desktop Screens                 | `docs/tenants/semana-11/prototype-screens-desktop.md`       | ✅      |
| Mobile Screens                  | `docs/tenants/semana-11/prototype-screens-mobile.md`        | ✅      |
| Consistency Checklist           | `docs/tenants/semana-11/prototype-consistency-checklist.md` | ✅      |
