# Semana 16 (parte 3) — Pantalla del expediente en la aplicación

## Objetivo

Cerrar el pendiente «Páginas Vue faltantes: Expediente». El menú mostraba «Expediente» como «próximamente» y la
única interfaz era el prototipo del micro-HIS (`public/ui`). Ahora la aplicación tiene la ruta `/expediente`.

## Qué se agregó

| Pieza | Ubicación | Notas |
|---|---|---|
| Ruta SPA `/expediente` y entrada de menú | `resources/js/router` | `requiresAuth` y `expediente.ver`, el mismo permiso de la consulta en el backend |
| Página | `resources/js/modules/expediente/pages/MedicalRecordPage.vue` | Consulta, apertura (solo con `expediente.abrir`), datos del paciente y línea de tiempo |
| Lógica y pruebas | `resources/js/modules/expediente/` | Reutiliza `estados.js` del prototipo; agrega los códigos de la capa Laravel |

## Decisiones

- **Una sola lógica de estados.** La página importa `micro-his/expediente-medico/public/ui/estados.js` (validación del
  código, mensajes por código, reintentos con límite, etiquetas y fechas con zona). Así el prototipo y la aplicación no
  divergen. Solo se traducen los códigos que agrega Laravel: `RBAC_DENIED` → acceso denegado, guard de la base
  hospitalaria → hospital no disponible, `RBAC_UNAVAILABLE` → servicio no disponible, y dos propios
  (`ACTOR_SIN_REFERENCIA_LOCAL`, `TENANT_MISMATCH`).
- **Apertura.** El botón solo aparece con `expediente.abrir` (médico y superadmin). Tras abrir se muestra el número
  del expediente y se intenta la consulta: si el usuario no tiene vínculo asistencial, el aviso de apertura se
  conserva y la consulta muestra «Sin vínculo asistencial», como en el backend.
- **Datos sensibles.** Si el código escrito cambia, el expediente anterior deja de mostrarse (H-01). Los mensajes de
  error nunca incluyen datos del paciente ni detalle interno; muestran el código de seguimiento cuando existe.
- **Accesibilidad.** Etiqueta y ayuda en el campo, `aria-invalid`, foco en el encabezado del paciente al cargar,
  `role="alert"` para denegaciones y errores, `role="status"` para avisos.

## Validación

```bash
node --test "resources/js/modules/expediente/*.test.mjs"   # pass 1, fail 0 (21 aserciones)
node --test resources/js/router/navigation.test.mjs resources/js/router/permissions.test.mjs   # fail 0
npx vite build   # MedicalRecordPage-*.js sin errores
```
