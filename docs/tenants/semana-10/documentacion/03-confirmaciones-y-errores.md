## 5. Confirmaciones y Errores Mobile

### 5.1 Patrones de Confirmación

#### Force-Revoke (2-Paso Mobile)

```

Paso 1: Confirmar Intención

┌─────────────────────────────────────┐

│  ⚠️ Confirmar Revocación        [×] │

│  ─────────────────────────────────  │

│                                     │

│  ¿Revocar TODAS las sesiones de     │

│  <strong>Dra. Torres</strong>?      │

│                                     │

│  Se cerrarán 3 sesiones activas     │

│  en todos los dispositivos.         │

│                                     │

│  [ Cancelar ]    [ Continuar → ]    │

│                                     │

└─────────────────────────────────────┘

Paso 2: Ejecutar con Motivo

┌─────────────────────────────────────┐

│  🔴 Ejecutar Revocación         [×] │

│  ─────────────────────────────────  │

│                                     │

│  Motivo \*: [Seguridad ▼]            │

│  Comentario: [__________________]   │

│                                     │

│  ⚠️ Deshacer disponible 5s          │

│                                     │

│  [ ← Volver ]    [ REVOCAR AHORA ]  │

│                                     │

└─────────────────────────────────────┘

```

#### MFA Recovery (Mobile)

```

┌─────────────────────────────────────┐

│  🔐 Recuperar Acceso MFA        [×] │

│  ─────────────────────────────────  │

│                                     │

│  No tienes acceso a tu app?         │

│                                     │

│  [ Usar código de recuperación ]    │

│  [ Contactar Administrador ]        │

│  [ Configurar Passkey ]             │

│                                     │

│  ─────────────────────────────────  │

│  [ Volver al Login ]                │

│                                     │

└─────────────────────────────────────┘

```

### 5.2 Patrones de Error Mobile

#### Toast Mobile (Bottom)

```

┌─────────────────────────────────────┐

│  🔴 Credenciales inválidas          │

│  Intento 2/5. ¿Olvidó contraseña?  │

│  [ Reintentar ] [ ¿Olvidó? ]        │

└─────────────────────────────────────┘

← Bottom fixed, 100% width, safe-area

```

#### Inline Error (Form)

```

Email institucional

[______________________________]  ❌

❌ Use email @hospital.com

← Borde rojo, icono, mensaje bajo campo

```

#### Modal Error Crítico (Reuse Detection)

```

┌─────────────────────────────────────┐

│  🛑 Actividad Sospechosa        [×] │

│  ─────────────────────────────────  │

│                                     │

│  ⚠️ Se detectó reutilización de     │

│  token. Por seguridad, TODAS sus    │

│  sesiones han sido revocadas.       │

│                                     │

│  [ Iniciar sesión de nuevo ]        │

│                                     │

└─────────────────────────────────────┘

← Modal full-screen mobile, focus trap

```

---
