# Semana 11 — Pantallas móvil (parte 1: acceso y alta)
**Módulo:** Gestión de usuarios, tenants y endurecimiento de autenticación
**Versión:** 1.1 | **Fecha:** 2026-10-21

> **Qué es este documento.** Mockup estático de diseño (ASCII) para 375 px. **No describe
> funcionalidad integrada**: cada pantalla indica si su comportamiento ya existe en el HIS
> (`develop`) o si es una propuesta. La continuación (usuarios, revocación y auditoría) está en
> [`prototype-screens-mobile1.md`](prototype-screens-mobile1.md).

| Etiqueta | Significado |
|---|---|
| **[Implementado]** | Existe en `routes/api.php` y en el SPA Vue de `develop`. |
| **[Propuesto]** | Diseño a validar: no hay contrato ni implementación en el HIS. |

**Roles.** Se usan los códigos literales de `config/rbac.php` (`superadmin`, `admin_hospital`,
`medico`, `enfermeria`, `recepcion`, `tecnico_laboratorio`, `bioquimico`, `jefatura_medica`).
La versión anterior usaba TENANT_ADMIN, MEDICO, ENFERMERO, AUDITOR y PACIENTE: los tres primeros
corresponden a `admin_hospital`, `medico` y `enfermeria`; AUDITOR y PACIENTE **no existen** en RBAC
y se retiraron.

---

## 1. Inicio de sesión (`/login`) — [Implementado]

Contrato vigente: `POST /api/v1/auth/login` con cabecera `X-Tenant-ID` (UUID del hospital).
Componente real: `resources/js/modules/auth/pages/LoginPage.vue` y la versión responsive
`resources/js/modules/tenants/responsive/LoginForm.vue` (Semana 10).

### Estado: vacío
```
┌─────────────────────────────────────┐
│  Hospital HIS                       │
├─────────────────────────────────────┤
│  Iniciar sesión                     │
│                                     │
│  ID del hospital (X-Tenant-ID) *    │
│  [______________________________]   │  ← UUID; se recuerda en el equipo
│  Lo entrega la administración       │
│                                     │
│  Correo *                           │
│  [______________________________]   │  ← inputmode=email, 16 px
│                                     │
│  Contraseña *               [Ver]   │
│  [______________________________]   │
│                                     │
│  [          ENTRAR          ]       │  ← 100 % ancho, mín. 44 px
└─────────────────────────────────────┘
```

### Estado: enviando
```
┌─────────────────────────────────────┐
│  Iniciar sesión                     │
│  ID del hospital  [26adadaf-…]      │
│  Correo           [ana@hospital…]   │
│  Contraseña       [••••••••••••]    │
│                                     │
│  [ ⏳ Verificando… ]  (deshabilitado)│  ← ignora doble toque y Enter
└─────────────────────────────────────┘
```

### Estado: error de credenciales
```
┌─────────────────────────────────────┐
│  Iniciar sesión                     │
│  ┌───────────────────────────────┐  │
│  │ ⚠ Credenciales inválidas.     │  │  ← role="alert", mensaje del API
│  └───────────────────────────────┘  │
│  Correo      [ana@hospital.test]    │
│  Contraseña  [________________]     │  ← se vacía tras el error
│  [          ENTRAR          ]       │
└─────────────────────────────────────┘
```
El mensaje es el que devuelve el API; no revela si el correo existe.

### Estado: sin conexión — [Implementado en el prototipo de Semana 10]
```
┌─────────────────────────────────────┐
│  ┌───────────────────────────────┐  │
│  │ Sin conexión: el inicio de    │  │  ← role="status"
│  │ sesión requiere red y no se   │  │
│  │ guarda para después.          │  │
│  └───────────────────────────────┘  │
│  [          ENTRAR          ]  (deshabilitado)
└─────────────────────────────────────┘
```

### Estado: cuenta bloqueada — [Propuesto]
```
┌─────────────────────────────────────┐
│  Iniciar sesión                     │
│  ┌───────────────────────────────┐  │
│  │ ⛔ Cuenta bloqueada            │  │
│  │ Demasiados intentos fallidos. │  │
│  │ Vuelva a intentar más tarde o │  │
│  │ contacte a la administración. │  │
│  └───────────────────────────────┘  │
│  [ Contactar administración ]       │
└─────────────────────────────────────┘
```
El HIS `develop` no expone hoy un estado de bloqueo propio (HTTP 423). Existe en el micro-HIS
de Semana 3; integrarlo en `/api/v1/auth/login` requiere acordar el contrato.

### Retirado respecto de la versión 1.0
- «Recordarme 30 días»: no existe; la sesión se renueva con el refresh por cookie del API.
- Validar el dominio `@hospital.com`: cada hospital usa su propio dominio.
- Selector de tenant por nombre: el contrato pide el UUID en `X-Tenant-ID`.

---

## 2. Segundo factor (MFA) — [Propuesto en el HIS]

El HIS `develop` **no tiene MFA**. El flujo TOTP sí está implementado y probado en el micro-HIS
vanilla de Semana 3 (`POST /auth/mfa` con `challenge_id` y código de 6 dígitos; desafío de un solo
uso, vence en 5 min y admite 3 intentos). Estas pantallas proponen su versión para el SPA.
Passkeys/WebAuthn quedan **fuera de alcance**: no hay validación de atestación.

### Estado: desafío TOTP
```
┌─────────────────────────────────────┐
│  Verificación en dos pasos          │
├─────────────────────────────────────┤
│  Abra su app autenticadora e        │
│  ingrese el código de 6 dígitos.    │
│                                     │
│  Código *                           │
│  [ _ _ _ _ _ _ ]                    │  ← un solo input, inputmode=numeric,
│                                     │    autocomplete=one-time-code
│  [       VERIFICAR       ]          │  ← 100 % ancho
│                                     │
│  ¿Sin acceso a la app? Contacte a   │
│  la administración del hospital.    │
└─────────────────────────────────────┘
```
Se usa un único campo en lugar de 6 casillas: los lectores de pantalla y el autocompletado del
sistema lo manejan mejor.

### Estado: código incorrecto
```
┌─────────────────────────────────────┐
│  Verificación en dos pasos          │
│  ┌───────────────────────────────┐  │
│  │ ⚠ Código inválido.            │  │  ← role="alert"
│  └───────────────────────────────┘  │
│  Código *  [ _ _ _ _ _ _ ]          │  ← campo vacío y con foco
│  [       VERIFICAR       ]          │
└─────────────────────────────────────┘
```

### Estado: desafío vencido o agotado
```
┌─────────────────────────────────────┐
│  Verificación en dos pasos          │
│  ┌───────────────────────────────┐  │
│  │ ⛔ El código venció o se       │  │
│  │ agotaron los intentos.        │  │
│  └───────────────────────────────┘  │
│  [   VOLVER A INICIAR SESIÓN   ]    │  ← vuelve a /login
└─────────────────────────────────────┘
```
No se ofrece «Reenviar código»: TOTP se genera en el teléfono y no se envía.

---

## 3. Alta de usuario (modal a pantalla completa) — [Propuesto]

`develop` no tiene `POST /api/v1/admin/users`: hoy el administrador lista usuarios, asigna o
quita roles y desactiva cuentas (ver parte 2). El alta se propone para `admin_hospital`, con los
roles canónicos y la política de contraseñas del backend (12 caracteres y las cuatro clases).

### Estado: vacío
```
┌─────────────────────────────────────┐
│  Nuevo usuario                [×]   │  ← encabezado fijo
├─────────────────────────────────────┤
│  Correo *                           │
│  [______________________________]   │
│  Nombre *                           │
│  [______________________________]   │
│  Contraseña inicial *       [Ver]   │
│  [______________________________]   │
│  ▱▱▱▱▱  MUY DÉBIL · 0/12            │  ← PasswordStrengthMeter (Semana 10)
│                                     │
│  Roles * (al menos uno)             │
│  [☐] medico                         │
│  [☐] enfermeria                     │
│  [☐] recepcion                      │
│  [☐] jefatura_medica                │
│  [☐] admin_hospital                 │
├─────────────────────────────────────┤
│  [ Cancelar ]   [ CREAR USUARIO ]   │  ← pie fijo, safe-area
└─────────────────────────────────────┘
```
La lista de roles se toma del catálogo RBAC del hospital, no de un arreglo fijo en la vista.

### Estado: errores de validación
```
┌─────────────────────────────────────┐
│  Correo *                           │
│  [ana-sin-arroba_______________] ✗  │
│  ✗ Formato de correo inválido       │
│  Contraseña inicial *               │
│  [corta________________________] ✗  │
│  ▰▱▱▱▱  MUY DÉBIL · 5/12            │
│  ✗ 12+  ✓ a-z  ✗ A-Z  ✗ 0-9  ✗ !@#  │
│  Roles *                            │
│  ✗ Seleccione al menos un rol       │
│  [ Cancelar ]   [ CREAR ] (deshab.) │
└─────────────────────────────────────┘
```

### Estado: correo ya registrado en el hospital
```
┌─────────────────────────────────────┐
│  ┌───────────────────────────────┐  │
│  │ ⚠ Ese correo ya tiene         │  │  ← HTTP 409 del API propuesto
│  │ membresía en este hospital.   │  │
│  └───────────────────────────────┘  │
│  [ Cancelar ]   [ CREAR USUARIO ]   │
└─────────────────────────────────────┘
```
No se ofrece «¿Es usted? Iniciar sesión»: quien crea la cuenta es el administrador.

### Estado: creado
```
┌─────────────────────────────────────┐
│  ✓ Usuario creado                   │
│                                     │
│  Correo   ana@hospital.test         │
│  Nombre   Dra. Ana López            │
│  Roles    medico                    │
│                                     │
│  La contraseña inicial no se vuelve │
│  a mostrar ni se envía por correo:  │
│  entréguela por un canal seguro.    │
│                                     │
│  [ Volver a la lista ] [ Crear otro ]│
└─────────────────────────────────────┘
```
Se retiró la versión 1.0, que mostraba la contraseña en pantalla con un botón «Copiar» y
anunciaba credenciales temporales por correo: ninguna de las dos cosas es segura ni existe.

---

## 4. Criterios móviles comunes

| Elemento | Criterio |
|---|---|
| Campos | `font-size: 16px` (evita el zoom de iOS), alto mínimo 44 px, `aria-describedby` hacia el error o la ayuda |
| Botones | Mínimo 44 × 44 px; a 100 % del ancho por debajo de 480 px; `aria-busy` mientras envían |
| Errores | `role="alert"` y texto; el color nunca es la única señal |
| Pie fijo | `padding-bottom: env(safe-area-inset-bottom)` |
| Movimiento | Spinners y transiciones se desactivan con `prefers-reduced-motion` |

Evidencia real de estos criterios en el SPA: capturas a 375, 768 y 1280 px en
`docs/tenants/semana-10/evidencias/` (ruta `/prototipos/tenants-responsive`).
