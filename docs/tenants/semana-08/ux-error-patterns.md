# Semana 08 — Patrones de Error y Microcopy UX
## 1. Taxonomía de Errores (Clasificación por Recuperabilidad)
### 1.1 Matriz de Clasificación
| Categoría                  | Códigos HTTP | Recuperabilidad    | Acción Usuario Requerida         | Prioridad UI |
|----------------------------|--------------|--------------------|----------------------------------|--------------|
| **Validación Cliente**     | 400 (client) | **Inmediata**      | Corregir input y reintentar      | 🟢 Baja      |
| **Credenciales Inválidas** | 401          | **Inmediata**      | Reintentar con corrección        | 🟢 Baja      |
| **MFA Inválido**           | 401          | **Inmediata**      | Reintentar código (máx 3)        | 🟡 Media     |
| **Conflicto Recurso**      | 409          | **Inmediata**      | Cambiar dato (email, rol)        | 🟢 Baja      |
| **Política Violada**       | 400/422      | **Inmediata**      | Corregir según política          | 🟡 Media     |
| **Permisos Insuficientes** | 403          | **Acción Externa** | Contactar admin / Cambiar tenant | 🟠 Alta      |
| **Cuenta Bloqueada**       | 423          | **Temporal**       | Esperar / Contactar admin        | 🔴 Crítica   |
| **Tenant Inactivo**        | 403/422      | **Externa**        | Contactar Platform Admin         | 🔴 Crítica   |
| **Cross-Tenant**           | 403          | **Bloqueo**        | Ninguna (seguridad)              | 🔴 Crítica   |
| **Reuse Detection**        | 401          | **Bloqueo Total**  | Ninguna (re-login forzado)       | 🔴 Crítica   |
| **Servidor/Infra**         | 500/502/503  | **Reintento**      | Esperar y reintentar             | 🟠 Alta      |

### 1.2 Catálogo de Errores por Flujo
#### A. Login Flow (`POST /auth/login`)
| Código         | Escenario                      | Mensaje Usuario (Microcopy)                                  | Acción UI                         | Recuperación        |
|----------------|--------------------------------|--------------------------------------------------------------|-----------------------------------|---------------------|
| **VAL-001**    | Email vacío                    | "El email es obligatorio"                                    | Inline rojo + focus email         | Escribir email      |
| **VAL-002**    | Email formato inválido         | "Use su email institucional (@hospital.com)"                 | Inline + hint dominios permitidos | Corregir formato    |
| **VAL-003**    | Password vacío                 | "La contraseña es obligatoria"                               | Inline rojo + focus password      | Escribir password   |
| **AUTH-001**   | Usuario no existe              | "Credenciales inválidas" (genérico)                          | Toast rojo + contador intentos    | Reintentar          |
| **AUTH-002**   | Password incorrecto            | "Credenciales inválidas. Intento 2 de 5"                     | Toast + contador + "¿Olvidó?"     | Reintentar          |
| **AUTH-003**   | Usuario inactivo               | "Su cuenta está desactivada. Contacte a su administrador"    | Modal ámbar + "Contactar Admin"   | Contactar Admin     |
| **AUTH-004**   | Usuario bloqueado (login)      | "Cuenta bloqueada 15 minutos. Intento 5 de 5"                | Modal rojo + countdown 15 min     | Esperar / Contactar |
| **AUTH-005**   | Usuario bloqueado (progresivo) | "Cuenta bloqueada 1 hora. Intento 10 de 10"                  | Modal rojo + countdown 1 hora     | Esperar / Contactar |
| **AUTH-006**   | Usuario bloqueado (máximo)     | "Cuenta bloqueada 24 horas. Contacte a soporte"              | Modal rojo + "Contactar Soporte"  | Contactar Soporte   |
| **TENANT-001** | Tenant inactivo                | "Su organización está suspendida. Contacte a Platform Admin" | Modal rojo + enlace soporte       | Contactar Platform  |
| **TENANT-002** | Tenant no encontrado           | "Tenant no válido. Verifique X-Tenant-ID"                    | Toast rojo + selector tenant      | Cambiar tenant      |
| **TENANT-003** | Cross-tenant attempt           | "Acceso denegado: tenant no coincide con su usuario"         | Modal rojo + "Cambiar tenant"     | Cambiar tenant      |
| **MFA-001**    | MFA requerido                  | "Autenticación de doble factor requerida"                    | Redirect a /mfa-challenge         | Completar MFA       |
| **RATE-001**   | Rate limit excedido            | "Demasiados intentos. Intente en 15 minutos"                 | Toast + Retry-After header        | Esperar             |

#### B. MFA Flow (`POST /auth/mfa`)
| Código       | Escenario                  | Mensaje Usuario                                          | Acción UI                     | Recuperación        |
|--------------|----------------------------|----------------------------------------------------------|-------------------------------|---------------------|
| **MFA-001**  | Challenge generado         | "Ingrese el código de 6 dígitos de su app autenticadora" | Pantalla MFA + countdown 30s  | Ingresar código     |
| **MFA-002**  | Código inválido            | "Código inválido. Intento 2 de 3"                        | Inline rojo + "Reenviar"      | Reintentar          |
| **MFA-002b** | Código inválido (WebAuthn) | "Autenticación fallida. Intente nuevamente"              | Toast + botón "Reintentar"    | Reintentar          |
| **MFA-003**  | MFA bloqueado (3 fallos)   | "MFA bloqueado 30 minutos. Intento 3 de 3"               | Modal rojo + countdown 30 min | Esperar / Contactar |
| **MFA-004**  | Challenge expirado         | "El desafío ha expirado. Inicie sesión nuevamente"       | Redirect a /login             | Re-login            |
| **MFA-005**  | Dispositivo no registrado  | "No tiene Passkeys registrados. Configure en Seguridad"  | Link a /security              | Configurar Passkey  |

#### C. Refresh Token Flow (`POST /auth/refresh`)
| Código      | Escenario                | Mensaje Usuario                                                         | Acción UI                                 | Recuperación     |
|-------------|--------------------------|-------------------------------------------------------------------------|-------------------------------------------|------------------|
| **REF-001** | Refresh expirado         | "Su sesión ha expirado. Inicie sesión nuevamente"                       | Redirect a /login + "Sesión expirada"     | Re-login         |
| **REF-002** | Refresh revocado         | "Su sesión fue revocada. Inicie sesión nuevamente"                      | Redirect a /login + "Sesión revocada"     | Re-login         |
| **REF-003** | Reuse Detection (ATAQUE) | "Actividad sospechosa detectada. Por seguridad, inicie sesión de nuevo" | **Modal Crítico Rojo** + "Iniciar sesión" | Re-login forzado |
| **REF-004** | Refresh token inválido   | "Token de actualización inválido"                                       | Redirect a /login                         | Re-login         |

#### D. Revocación Sesión (`POST /auth/logout` / `DELETE /admin/users/{id}/sessions`)
| Código      | Escenario                 | Mensaje Usuario                                           | Acción UI                       | Recuperación     |
|-------------|---------------------------|-----------------------------------------------------------|---------------------------------|------------------|
| **REV-001** | Logout propio exitoso     | "Sesión cerrada correctamente"                            | Toast verde + redirect /login   | Re-login         |
| **REV-002** | Force-revoke exitoso      | "3 sesiones revocadas para Dra. Torres"                   | Toast verde + lista actualizada | Usuario re-login |
| **REV-003** | Sin permisos force-revoke | "No tiene permisos para revocar sesiones de este usuario" | Toast rojo 403                  | N/A              |
| **REV-004** | Usuario no encontrado     | "Usuario no encontrado"                                   | Toast rojo 404                  | N/A              |

#### E. Admin - Alta Usuario (`POST /admin/users`)
| Código      | Escenario               | Mensaje Usuario                                               | Acción UI                           | Recuperación           |
|-------------|-------------------------|---------------------------------------------------------------|-------------------------------------|------------------------|
| **ADM-001** | Email duplicado         | "Este email ya está registrado en su organización"            | Inline rojo en email + "¿Es usted?" | Cambiar email          |
| **ADM-002** | Cupo excedido           | "Cupo de usuarios agotado (50/50). Contacte a Platform Admin" | Toast rojo + "Solicitar aumento"    | Contactar Admin        |
| **ADM-003** | Tenant inactivo         | "No puede crear usuarios: organización suspendida"            | Toast rojo                          | Contactar Platform     |
| **ADM-004** | Password policy fallida | Lista errores: "• 12+ chars • Mayúscula • HIBP detectado"     | Inline + medidor fortaleza          | Corregir password      |
| **ADM-005** | Rol no permitido        | "El rol AUDITOR no está disponible en su plan"                | Inline en selector roles            | Seleccionar rol válido |
| **ADM-006** | Sin permisos            | "Requiere rol TENANT_ADMIN o PLATFORM_ADMIN"                  | Toast 403                           | N/A                    |

#### F. Errores de Infraestructura (5xx)
| Código      | Escenario                | Mensaje Usuario                                                   | Acción UI                        | Recuperación  |
|-------------|--------------------------|-------------------------------------------------------------------|----------------------------------|---------------|
| **INF-001** | DB no disponible         | "Servicio temporalmente no disponible. Reintente en unos minutos" | Toast ámbar + "Reintentar"       | Auto-retry 3x |
| **INF-002** | Redis no disponible      | "Problema temporal de sesión. Sus datos están seguros"            | Toast ámbar + "Reintentar"       | Auto-retry    |
| **INF-003** | Timeout gateway          | "La solicitud tardó demasiado. Intente nuevamente"                | Toast ámbar + "Reintentar"       | Reintentar    |
| **INF-004** | Mantenimiento programado | "Sistema en mantenimiento hasta 03:00. Vuelva luego"              | Página mantenimiento + countdown | Esperar       |

## 2. Patrones de Feedback Visual
### 2.1 Toast Notifications (Sistema Unificado
```typescript
// Tipos de Toast
type ToastType = 'success' | 'error' | 'warning' | 'info' | 'critical';
interface Toast {
  id: string;
  type: ToastType;
  title: string;
  message?: string;
  action?: { label: string; onClick: () => void };
  duration?: number; // ms, 0 = persistente
  dismissible: boolean;
}
// Ejemplos de uso:
const toasts = {
  // Éxito
  loginSuccess: {
    type: 'success',
    title: 'Bienvenida, Dra. González',
    duration: 3000,
  },
  // Error recuperable
  invalidCredentials: {
    type: 'error',
    title: 'Credenciales inválidas',
    message: 'Intento 2 de 5. ¿Olvidó su contraseña?',
    action: { label: 'Recuperar', onClick: () => navigate('/forgot-password') },
    duration: 5000,
  },
  // Bloqueo temporal
  accountLocked: {
    type: 'warning',
    title: 'Cuenta bloqueada',
    message: 'Cuenta bloqueada 15 minutos. Intento 5 de 5.',
    duration: 0, 
  },
  // Crítico seguridad
  reuseDetected: {
    type: 'critical',
    title: '⚠️ Actividad sospechosa detectada',
    message: 'Sus sesiones han sido revocadas por seguridad.',
    action: { label: 'Iniciar sesión de nuevo', onClick: () => navigate('/login') },
    duration: 0,
    dismissible: false,
  },
  // Mantenimiento
  maintenance: {
    type: 'info',
    title: 'Mantenimiento programado',
    message: 'Sistema en mantenimiento hasta 03:00 AM',
    duration: 0,
  },
};
```
### 2.2 Inline Validation (Formularios)
```tsx
// Patrón Inline Error
const FieldError = ({ message, fieldId }: { message: string; fieldId: string }) => (
  <span 
    id={`${fieldId}-error`} 
    role="alert" 
    aria-live="assertive"
    className="field-error"
  >
    <svg aria-hidden="true" className="error-icon">⚠️</svg>
    <span>{message}</span>
  </span>
);

// Uso en input
<div className="form-field">
  <label htmlFor="email">Email institucional</label>
  <input
    id="email"
    type="email"
    aria-describedby={errors.email ? 'email-hint email-error' : 'email-hint'}
    aria-invalid={!!errors.email}
    aria-errormessage={errors.email ? 'email-error' : undefined}
  />
  <span id="email-hint" className="hint">Use @hospital.com</span>
  {errors.email && <FieldError message={errors.email} fieldId="email" />}
</div>
```

### 2.3 Modal Patterns
```tsx
// Modal Confirmación (Force-Revoke)
const ConfirmModal = ({ 
  title, 
  message, 
  confirmLabel, 
  cancelLabel, 
  variant: 'danger' | 'primary' | 'secondary',
  onConfirm,
  onCancel 
}: ConfirmModalProps) => (
  <Dialog open aria-modal="true" aria-labelledby="modal-title" aria-describedby="modal-desc">
    <DialogTitle id="modal-title">{title}</DialogTitle>
    <DialogContent id="modal-desc">
      <Alert icon={variant === 'danger' ? '🔴' : variant === 'warning' ? '⚠️' : 'ℹ️'}>
        {message}
      </Alert>
    </DialogContent>
    <DialogActions>
      <Button onClick={onCancel} variant="secondary">{cancelLabel}</Button>
      <Button 
        onClick={onConfirm} 
        variant={variant}
        autoFocus  // Focus en acción principal
      >
        {confirmLabel}
      </Button>
    </DialogActions>
  </Dialog>
);
```
## 3. Microcopy Guidelines (Guía de Redacción)
### 3.1 Principios de Microcopy
| Principio                   | Mal Ejemplo                   | Bien Ejemplo                                      |
|-----------------------------|-------------------------------|---------------------------------------------------|
| **Claro, no técnico**       | "Error 401: Unauthorized"     | "Credenciales inválidas"                          |
| **Accionable**              | "Error en el servidor"        | "Intente nuevamente en unos minutos"              |
| **Empático, no culposo**    | "Usted escribió mal el email" | "El email parece incorrecto"                      |
| **Específico, no genérico** | "Error inesperado"            | "Su sesión expiró. Inicie sesión nuevamente"      |
| **Consistente**             | "Error" / "Falló" / "Fallido" | Siempre "Error" para errores                      |
| **Positivo en éxito**       | "Operación completada"        | "Usuario creado. Credenciales enviadas por email" |

### 3.2 Diccionario de Términos (Glosario UI)
| Término Técnico    | Término UI (Usuario)                                    | Contexto                 |
|--------------------|---------------------------------------------------------|--------------------------|
| Access Token       | "Sesión activa" / "Token de acceso"                     | Solo en logs técnicos    |
| Refresh Token      | "Renovación automática"                                 | Nunca mostrar al usuario |
| JWT                | "Firma digital"                                         | Solo en docs técnicos    |
| MFA / 2FA          | "Autenticación de doble factor" / "Código de seguridad" | UI visible               |
| TOTP               | "Código de 6 dígitos"                                   | UI visible               |
| WebAuthn / Passkey | "FaceID / TouchID / Llave de seguridad"                 | UI visible               |
| Blocklist          | "Lista de bloqueo"                                      | Solo logs                |
| Force-Revoke       | "Cerrar todas las sesiones"                             | UI Admin                 |
| Break-Glass        | "Acceso de emergencia"                                  | UI Médico                |
| Tenant             | "Organización" / "Hospital"                             | UI visible               |
| Cross-tenant       | "Acceso a otra organización"                            | Solo logs/errores        |

### 3.3 Tono por Tipo de Mensaje
| Tipo                  | Tono                             | Ejemplo                                                |
|-----------------------|----------------------------------|--------------------------------------------------------|
| **Éxito**             | Cálido, profesional              | "Bienvenida, Dra. González. Redirigiendo..."           |
| **Error Validación**  | Neutral, útil                    | "El email debe ser @hospital.com"                      |
| **Error Auth**        | Neutro, seguro (no revelar info) | "Credenciales inválidas"                               |
| **Advertencia**       | Urgente pero calmado             | "Su sesión expira en 5 minutos"                        |
| **Bloqueo**           | Firme, informativo               | "Cuenta bloqueada 15 min. Intento 5/5"                 |
| **Crítico Seguridad** | Urgente, directo                 | "⚠️ Actividad sospechosa. Sesiones revocadas."         |
| **Ayuda/Tooltip**     | Amable, explicativo              | "Use su email institucional @hospital.com"             |
| **Onboarding**        | Acogedor, guiado                 | "¡Bienvenida! Configure su autenticación doble factor" |

## 4. Estados de Carga y Vacío
### 4.1 Loading States
| Componente        | Estado Carga | Visual                             | Texto                       |
|-------------------|--------------|------------------------------------|-----------------------------|
| **Botón Submit**  | Submitting   | Spinner integrado + "Validando..." | "Validando credenciales..." |
| **MFA Challenge** | Generating   | Skeleton + pulso                   | "Generando desafío..."      |
| **Admin Create**  | Creating     | Spinner + disabled                 | "Creando usuario..."        |
| **Force-Revoke**  | Revoking     | Spinner + progress                 | "Revocando 3 sesiones..."   |
| **Page Load**     | Initial      | Skeleton cards                     | "Cargando dashboard..."     |

### 4.2 Empty States
| Contexto                     | Título                      | Mensaje                                                        | Acción                                 |
|------------------------------|-----------------------------|----------------------------------------------------------------|----------------------------------------|
| **Lista Usuarios Vacía**     | "Sin usuarios aún"          | "Comience agregando su primer usuario"                         | [+ Nuevo Usuario]                      |
| **Auditoría Sin Resultados** | "Sin eventos en este rango" | "Ajuste los filtros o amplíe el rango de fechas"               | [Limpiar filtros]                      |
| **Paciente Sin Citas**       | "Sin próximas citas"        | "Sus próximas citas aparecerán aquí"                           | [Solicitar Cita]                       |
| **Búsqueda Sin Resultados**  | "Sin coincidencias"         | "Intente con otros términos o filtros"                         | [Limpiar búsqueda]                     |
| **Primer Login MFA**         | "Configure su seguridad"    | "Active la autenticación doble factor para proteger su cuenta" | [Configurar TOTP] [Configurar Passkey] |

## 5. Ayuda Contextual (Tooltips + Help)
### 5.1 Tooltips por Elemento
| Elemento               | Tooltip (Hover/Focus)                                 | Ayuda Extendida (Click ❓)                                                                 |
|------------------------|-------------------------------------------------------|-------------------------------------------------------------------------------------------|
| **Input Email**        | "Use su email @hospital.com"                          | "Solo dominios autorizados. Contacte admin para agregar dominios."                        |
| **Input Password**     | "Mín 12 chars, mayúscula, minúscula, número, símbolo" | "Verificamos contra filtraciones conocidas (HaveIBeenPwned). No almacenamos su password." |
| **Toggle Password**    | "Mostrar/ocultar contraseña"                          | -                                                                                         |
| **Checkbox MFA**       | "Obligatorio para TENANT_ADMIN/AUDITOR"               | "Añade capa extra de seguridad. Apps: Google Authenticator, Authy, Passkeys."             |
| **Selector Roles**     | "Mín 1, máx 5. TENANT_ADMIN/AUDITOR requieren MFA"    | "Roles definen permisos. Consulte matriz de permisos en Ayuda."                           |
| **Botón Force-Revoke** | "Cierra TODAS las sesiones en todos los dispositivos" | "Use si: compromiso credenciales, cambio rol, dispositivo perdido."                       |
| **Selector Tenant**    | "Su organización: Hospital Central (HCH-001)"         | "Si pertenece a múltiples hospitales, seleccione aquí."                                   |
| **Password Strength**  | "Fortaleza: Fuerte ✅"                                 | "Verificamos contra HaveIBeenPwned. No enviamos su password."                             |

### 5.2 Help Center Links Contextuales
| Contexto            | Link Principal                                        | Links Secundarios                             |
|---------------------|-------------------------------------------------------|-----------------------------------------------|
| **Login Error**     | "¿Olvidó su contraseña?" → /forgot-password           | "Problemas con MFA" → /mfa-help               |
| **MFA Challenge**   | "No tengo acceso a la app" → /mfa-recovery            | "Usar Passkey" → /webauthn-setup              |
| **Account Locked**  | "Contactar Administrador" → mailto:admin@hospital.com | "Desbloqueo automático en 15 min"             |
| **Force-Revoke**    | "¿Qué pasa al revocar?" → /help/force-revoke          | "Usuario no puede entrar" → /help/user-locked |
| **Password Policy** | "Requisitos de contraseña" → /help/password-policy    | "Verificar en HaveIBeenPwned" → /help/hibp    |

## 6. Accesibilidad en Errores (WCAG 2.1 AA)
### 6.1 Requisitos por Tipo de Error
| Tipo Error            | `aria-invalid`  | `aria-describedby` | `role="alert"` | `aria-live` |
|-----------------------|-----------------|--------------------|----------------|-------------|
| **Inline Validation** | `true` en input | ID del span error  | No             | `polite`    |
| **Toast Error**       | N/A             | N/A                | Sí (toast)     | `assertive` |
| **Modal Error**       | `true` en campo | ID mensaje modal   | Sí (modal)     | `assertive` |
| **Critical Security** | N/A             | N/A                | Sí (modal)     | `assertive` |

### 6.2 Implementación Referencia
```tsx
// Hook para anuncios accesibles
const useAnnouncer = () => {
  const [announcer, setAnnouncer] = useState('');
  
  const announce = (message: string, priority: 'polite' | 'assertive' = 'polite') => {
    setAnnouncer(`<div role="status" aria-live="${priority}" aria-atomic="true">${message}</div>`);
    setTimeout(() => setAnnouncer(''), 1000);
  };
  
  return { announcer, announce };
};

// Uso en componente
const LoginForm = () => {
  const { announce } = useAnnouncer();
  
  const handleSubmit = async (data) => {
    try {
      await login(data);
      announce('Bienvenida, Dra. González. Redirigiendo al dashboard.', 'polite');
    } catch (error) {
      if (error.code === 'AUTH-002') {
        announce('Credenciales inválidas. Intento 2 de 5.', 'assertive');
      }
    }
  };
  return (
    <>
      <div id="announcer" aria-live="polite" aria-atomic="true" className="sr-only">
        {announcer}
      </div>
      <form>...</form>
    </>
  );
}
```
## 7. Testing de Errores (Casos de Prueba)
### 7.1 Matriz de Casos de Prueba por Flujo
| Flujo            | Caso                   | Input           | Expected Error           | UI Pattern               | Accesibilidad                       |
|------------------|------------------------|-----------------|--------------------------|--------------------------|-------------------------------------|
| **Login**        | Email vacío            | email=""        | "Email obligatorio"      | Inline + focus           | `aria-invalid="true"`               |
| **Login**        | Email inválido         | email="invalid" | "Use @hospital.com"      | Inline + hint            | `aria-describedby`                  |
| **Login**        | Password corta         | password="123"  | "Mín 12 caracteres"      | Inline + strength meter  | `aria-invalid`                      |
| **Login**        | Credenciales inválidas | user=noexiste   | "Credenciales inválidas" | Toast + contador         | `aria-live="assertive"`             |
| **Login**        | Cuenta bloqueada       | 5 fallos        | "Bloqueada 15 min"       | Modal + countdown        | `aria-live="assertive"`             |
| **MFA**          | Código inválido        | code="000000"   | "Inválido. Intento 2/3"  | Inline + "Reenviar"      | `aria-invalid`                      |
| **MFA**          | Bloqueo MFA            | 3 fallos        | "Bloqueado 30 min"       | Modal + countdown        | `aria-live="assertive"`             |
| **Refresh**      | Reuse Detectado        | token reusado   | "Actividad sospechosa"   | Modal crítico + redirect | `aria-live="assertive"`             |
| **Admin Create** | Email duplicado        | email=existente | "Email ya registrado"    | Inline + "¿Es usted?"    | `aria-invalid`                      |
| **Admin Create** | Policy fail            | password="weak" | Lista 5 errores          | Inline + lista checks    | `aria-invalid` + `aria-describedby` |
| **Force-Revoke** | Sin permisos           | role=MEDICO     | "Requiere TENANT_ADMIN"  | Toast 403                | `aria-live="assertive"`             |

## 8. Métricas de Calidad de Errores (KPIs)
| Métrica                       | Objetivo                             | Medición                                                     |
|-------------------------------|--------------------------------------|--------------------------------------------------------------|
| **Tasa Errores Recuperables** | > 90% usuarios resuelven sin soporte | Analytics: (errores_resueltos_sin_soporte / errores_totales) |
| **Tiempo Resolución Error**   | < 30s promedio                       | Timestamp error → resolución exitosa                         |
| **Tasa Abandono por Error**   | < 5%                                 | (usuarios_abandonan / errores_mostrados)                     |
| **Claridad Microcopy**        | > 4.5/5                              | Encuesta post-error: "¿Entendió el mensaje?"                 |
| **Accesibilidad Errores**     | 100% axe-core pass                   | CI pipeline automatizado                                     |
| **Consistencia Terminología** | 100% diccionario                     | Auditoría trimestral strings                                 |

## 9. Mantenimiento y Gobernanza
### 8.1 Governance de Microcopy
| Rol               | Responsabilidad                                 |
|-------------------|-------------------------------------------------|
| **UX Writer**     | Redacción, consistencia, diccionario términos   |
| **Product Owner** | Aprobación mensajes críticos/legales            |
| **Security Team** | Aprobación mensajes seguridad (no revelar info) |
| **Dev Team**      | Implementación correcta ARIA, i18n keys         |
| **QA**            | Testing automatizado + manual accesibilidad     |

### 8.2 Proceso de Cambio
1. **Request** → Issue en repo con label `microcopy`
2. **Review** → UX Writer + Security (si crítico) + PO
3. **Implement** → Dev + i18n keys + tests
4. **Validate** → axe-core CI + QA manual + stakeholder review
5. **Deploy** → Feature flag → 100% → Remove flag
