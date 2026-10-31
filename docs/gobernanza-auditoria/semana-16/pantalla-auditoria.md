# Gobernanza y Auditoría — Semana 16 (parte 2): pantalla de auditoría en la aplicación

## Objetivo

Cerrar el pendiente «Páginas Vue faltantes: Auditoría». Hasta ahora la bitácora solo se consultaba por API
(`GET /api/v1/audit-events`) o con el prototipo del micro-HIS; el menú mostraba «Auditoría» como «próximamente».

## Qué se agregó

| Pieza | Ubicación | Notas |
|---|---|---|
| Ruta SPA `/auditoria` | `resources/js/router/index.js` | `requiresAuth` y permiso `auditoria.ver` (el mismo que exige el backend) |
| Entrada de menú «Auditoría» | `resources/js/router/permissions.js` | Deja de ser «próximamente»; solo la ve quien tiene `auditoria.ver` |
| Página | `resources/js/modules/auditoria/pages/AuditPage.vue` | Filtros, tabla, detalle del evento y paginación |
| Lógica pura y pruebas | `resources/js/modules/auditoria/` | Validación local con las reglas de `AuditFilterParser`; 23 aserciones con `node` |

## Reglas que respeta la pantalla

- Usa solo los parámetros públicos del contrato: usuario, modulo, accion, desde, hasta, resultado, correlacion,
  pagina y por_pagina. Nunca envía el tenant: el servidor lo fija y rechaza cualquier otro valor.
- Valida en el navegador con los mismos patrones del servidor para no gastar una consulta, pero la autoridad sigue
  siendo el backend (422 `AUDIT_FILTER_INVALID` marca el campo devuelto en `errors`).
- Falla segura: ante 403/503 vacía la tabla y muestra un mensaje por código, sin detalle interno del servidor.
- Solo lectura: no hay acciones de edición ni borrado; el contexto ya llega filtrado por `ContextSanitizer` y los
  campos ocultos se listan como «Campos ocultos».
- Accesible: etiquetas en todos los filtros, `aria-invalid` en el campo con error, `role="alert"` para errores,
  `aria-live` para el resumen y `aria-expanded` en el botón de detalle.

## Validación

```bash
node --test "resources/js/modules/auditoria/*.test.mjs"   # pass 1, fail 0
node --test resources/js/router/navigation.test.mjs resources/js/router/permissions.test.mjs   # pass 2, fail 0
npx vite build   # genera AuditPage-*.js sin errores
```

## Pendiente

- `auditoria.ver` sigue activo solo para `superadmin` (decisión RBAC vigente); si se concede a otro rol, la pantalla
  no necesita cambios.
