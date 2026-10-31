# Semana 11 — Pantallas móvil (parte 2: usuarios, sesiones y auditoría)
**Versión:** 1.1 | **Fecha:** 2026-10-21

> Continuación de [`prototype-screens-mobile.md`](prototype-screens-mobile.md), con las mismas
> etiquetas **[Implementado]** / **[Propuesto]** y los roles canónicos de `config/rbac.php`.
> Mockup estático de diseño: no describe funcionalidad que no exista en `develop`.
>
> **Consolidación.** La versión 1.0 de este archivo repetía las secciones 5 a 10 de la parte 1.
> Ahora cada pantalla aparece una sola vez: la parte 1 cubre acceso y alta, y esta parte cubre
> el resto. El portal de paciente se retiró: no pertenece a este módulo y no existe un rol de paciente.

---

## 5. Usuarios del hospital (`/admin/users`) — [Implementado]

Contrato vigente: `GET /api/v1/admin/users` con el permiso `usuarios.ver`. Componente real:
`resources/js/modules/tenants/pages/HospitalUsersPage.vue`. En móvil la tabla se muestra como tarjetas.

```
┌─────────────────────────────────────┐
│  Usuarios · Hospital Central  [≡]   │
├─────────────────────────────────────┤
│  Buscar [_____________________]     │  ← [Propuesto]: hoy no hay filtro en el API
├─────────────────────────────────────┤
│  ┌───────────────────────────────┐  │
│  │ Dra. Ana González             │  │
│  │ ana.gonzalez@hch.test         │  │
│  │ medico · jefatura_medica      │  │
│  │ ● Activa          [Acciones ▾]│  │  ← botón de 44 px
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ Lic. Pedro Ruiz               │  │
│  │ pedro.ruiz@hch.test           │  │
│  │ enfermeria                    │  │
│  │ ● Activa          [Acciones ▾]│  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ Carlos Méndez                 │  │
│  │ carlos.mendez@hch.test        │  │
│  │ recepcion                     │  │
│  │ ○ Desactivada     [Acciones ▾]│  │
│  └───────────────────────────────┘  │
└─────────────────────────────────────┘
```
El estado se indica con texto además del punto de color.

### Menú de acciones (hoja inferior)

| Acción | Contrato | Permiso | Estado |
|---|---|---|---|
| Asignar rol | `POST /api/v1/admin/users/{user}/roles` | `rbac.asignar` | [Implementado] |
| Quitar rol | `DELETE /api/v1/admin/users/{user}/roles/{role}` | `rbac.asignar` | [Implementado] |
| Desactivar usuario | `POST /api/v1/admin/users/{user}/deactivate` | `usuarios.desactivar` | [Implementado] |
| Cerrar una sesión | `DELETE /api/v1/admin/sessions/{session}` | `sesiones.revocar_ajenas` | [Implementado] |
| Cerrar todas las sesiones | — | — | [Propuesto] |

```
┌─────────────────────────────────────┐
│  Acciones · Dra. Ana González  [×]  │
├─────────────────────────────────────┤
│  Asignar rol                        │
│  ─────────────────────────────────  │
│  Quitar rol                         │
│  ─────────────────────────────────  │
│  Cerrar una sesión                  │
│  ─────────────────────────────────  │
│  Desactivar usuario                 │  ← estilo de peligro
└─────────────────────────────────────┘
```
Cada opción se muestra solo si el usuario autenticado tiene el permiso correspondiente
(`can()` de `resources/js/router/permissions.js`).

---

## 6. Cerrar una sesión de otro usuario — [Implementado]

### Confirmación (modal a pantalla completa)
```
┌─────────────────────────────────────┐
│  Cerrar sesión · Dra. González [×]  │
├─────────────────────────────────────┤
│  Se cerrará esta sesión:            │
│  Chrome · Android · 21/10 08:30     │
│                                     │
│  El dispositivo deberá volver a     │
│  iniciar sesión.                    │
│                                     │
│  Esta acción no se puede deshacer:  │
│  el token revocado ya no se acepta. │
│                                     │
│  [ Cancelar ]   [ CERRAR SESIÓN ]   │  ← 100 % ancho, 44 px
└─────────────────────────────────────┘
```

### Resultado
```
┌─────────────────────────────────────┐
│  ✓ Sesión cerrada para la           │  ← role="status"
│  Dra. González.                     │
└─────────────────────────────────────┘
← fijo abajo, 100 % ancho, safe-area
```

**Por qué no hay «Deshacer».** La versión 1.0 ofrecía «Deshacer (5 s)» y «restaurar sesiones».
Ese contrato no existe y no tendría sentido: al revocar, el backend invalida los tokens de la
sesión, y reactivarlos reabriría un acceso que el administrador decidió cortar (por ejemplo, ante
una sospecha de robo). La única forma de recuperar el acceso es que el usuario vuelva a iniciar
sesión. Por eso la confirmación previa es explícita y la acción queda auditada.

Respuesta al hallazgo H-002 de Semana 9 («revocar sin Deshacer»): el riesgo de error que señalaba
se atiende **antes** de ejecutar, no después. La confirmación ocupa toda la pantalla, nombra a la
persona y la sesión afectadas, explica las consecuencias, separa «Cancelar» de la acción con
botones de 44 px y no se ejecuta con un solo toque.

### Cerrar todas las sesiones — [Propuesto]
Mismo diseño con la lista de sesiones activas y un motivo obligatorio (Seguridad, Cambio de rol,
Sospecha de compromiso, Otro). Requiere definir un endpoint nuevo; hoy el administrador debe
cerrarlas una por una o desactivar la cuenta.

---

## 7. Auditoría de seguridad — [Propuesto]

El permiso `auditoria.ver` existe en el catálogo RBAC, pero `develop` no expone una vista ni un
endpoint de auditoría para este módulo. Diseño propuesto en tarjetas:

```
┌─────────────────────────────────────┐
│  Auditoría · Hospital Central       │
├─────────────────────────────────────┤
│  [Filtros ▾]                        │
├─────────────────────────────────────┤
│  ┌───────────────────────────────┐  │
│  │ CRÍTICO · Reutilización de    │  │
│  │ refresh token                 │  │
│  │ 21/10/2026 10:45              │  │
│  │ Usuario: ana.gonzalez         │  │
│  │ [Ver detalle]                 │  │
│  └───────────────────────────────┘  │
│  ┌───────────────────────────────┐  │
│  │ INFO · Inicio de sesión       │  │
│  │ 21/10/2026 11:20              │  │
│  │ Usuario: pedro.ruiz           │  │
│  │ [Ver detalle]                 │  │
│  └───────────────────────────────┘  │
│  [ Anterior ]  2 / 5  [ Siguiente ] │
└─────────────────────────────────────┘
```
- La severidad se muestra con texto, no solo con color.
- El detalle nunca muestra tokens, contraseñas ni códigos.
- Interacción con botones visibles. Se retiraron los gestos ocultos de la v1.0 («deslizar para
  acciones», «mantener presionado») porque no tienen alternativa accesible por teclado.

---

## 8. Objetivos táctiles y de accesibilidad

| Elemento | Tamaño mínimo | Implementación |
|---|---|---|
| Botones | 44 × 44 px | `min-height: 44px` (`BaseButton.vue`) |
| Campos | 44 px de alto | `min-height: 44px; font-size: 16px` (`FormField.vue`) |
| Casillas | 24 × 24 px con zona táctil de 44 px | etiqueta clicable que envuelve el input |
| Elementos de menú | 44 px de alto | `padding: 12px 16px` |

```css
/* Pie y hojas inferiores respetan el área segura de iPhone. */
.sheet-footer { padding-bottom: env(safe-area-inset-bottom, 0); }
```

---

## 9. Plan de pruebas móvil (pendiente de ejecutar)

Es un **plan**, no un resultado. La única evidencia ejecutada hoy son las capturas del prototipo
Vue a 375, 768 y 1280 px (`docs/tenants/semana-10/evidencias/`) y las pruebas `node` de los
componentes de Semana 10.

| Prueba | Alcance | Herramienta | Estado |
|---|---|---|---|
| Diseño responsive | 375 / 768 / 1280 px | Chrome headless | Ejecutada (Semana 10) |
| Teclado y lector de pantalla | Login, usuarios | NVDA, TalkBack | Pendiente |
| Contraste y ARIA | Pantallas implementadas | axe-core | Pendiente |
| Orientación | Vertical y horizontal | Dispositivo real | Pendiente |
