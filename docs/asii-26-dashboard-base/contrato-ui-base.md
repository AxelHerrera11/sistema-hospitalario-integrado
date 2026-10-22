# Contrato UI base — Dashboard base y navegación transversal (ASII-26)

## Ficha

- **Módulo:** ASII-26 — Dashboard base UI/UX e implementación transversal
- **Estudiante:** Lis Ivette Rosales Colindrez ([`Lis671`](https://github.com/Lis671))
- **Estado:** propuesto — pendiente de validar con Cindy (auth) y María (catálogo de permisos)
- **Publicación:** 18/10/2026 en el grupo; viaja en el PR `feature/asii-26-01-contrato-ui-lis671`
- **Consumidores:** todas las pantallas del HIS (ASII-03 a ASII-27)

## 1. Alcance

Este contrato define la base visual y de navegación compartida por todas las pantallas:

1. Layout general (sidebar, header, área de contenido).
2. Menú de navegación filtrado por permisos `modulo.accion`.
3. Router y guards (`requiresAuth`, `permission`).
4. Componentes reutilizables y estados de página (carga, vacío, error).
5. Manejo transversal de errores 401/403/422 en Axios.

**Fuera de alcance:** indicadores analíticos (ASII-23), filtros y exportación de reportes (ASII-27), estética de detalle de cada módulo.

## 2. Layout y zonas

`AppLayout.vue` (en `resources/js/shared/components/AppLayout.vue`) expone tres zonas:

| Zona | Contenido | Comportamiento |
|---|---|---|
| `sidebar` | Menú de navegación (§3) | Colapsable en móvil (< 768px); oculto en `/login` |
| `header` | Marca "Hospital HIS", tenant activo, nombre de usuario, cerrar sesión | Fijo arriba |
| `content` | `<router-view>` dentro de `<main>` | Padding uniforme; fondo `#f8fafc` |

El layout es el envoltorio de `App.vue`; toda ruta —salvo `login`— se renderiza dentro de él.

## 3. Menú por rol/permiso

Cada entrada del menú se declara con esta forma (estructura estática en `resources/js/shared/navigation.js`):

```js
{ id: 'citas', label: 'Citas', route: '/citas', permission: 'citas.ver' }
```

- `permission` es de tipo `modulo.accion` (convención RBAC).
- Un ítem se muestra **solo** si el permiso está en `auth.user.permissions`.
- Ítem sin `permission` se muestra a cualquier usuario autenticado (solo `Inicio`).
- Si `permissions` no está disponible aún (auth/RBAC pendientes), se oculta todo lo que requiera permiso: **falla cerrada, nunca abierta**.

### Ítems propuestos y permiso requerido

| Ítem | Ruta | Permiso (propuesto a María) |
|---|---|---|
| Inicio | `/` | — (autenticado) |
| Pacientes | `/pacientes` | `pacientes.ver` |
| Citas | `/citas` | `citas.ver` |
| Admisión y camas | `/admision` | `admision.ver` |
| Expediente | `/expediente` | `expediente.ver` |
| Signos vitales | `/signos-vitales` | `signos_vitales.ver` |
| Notas SOAP | `/notas-soap` | `notas_soap.ver` |
| Alergias | `/alergias` | `alergias.ver` |
| Medicamentos | `/medicamentos` | `medicamentos.ver` |
| Prescripciones | `/prescripciones` | `prescripciones.ver` |
| Laboratorio | `/laboratorio` | `laboratorio.ver` |
| Reportes | `/reportes` | `reportes.ver` |
| Ocupación | `/ocupacion` | `ocupacion.ver` |
| Auditoría | `/auditoria` | `auditoria.ver` |
| Usuarios y roles | `/usuarios` | `usuarios.ver` |
| Documentación | `/docs` | `documentacion.ver` |

> Los permisos `citas.*`, `reportes.*` y `documentacion.ver` los define este módulo (§8). Los demás se proponen al catálogo de María; si el nombre final difiere, solo cambia la tabla, no el código.

### Accesos por rol (esperado)

| Rol | Menú visible |
|---|---|
| SuperAdmin | Todos |
| AdminHospital | Todos excepto `auditoria.ver` si María lo restringe |
| Medico | Inicio, Pacientes, Citas, Expediente, Signos, SOAP, Alergias, Prescripciones, Reportes, Documentación |
| Enfermeria | Inicio, Pacientes, Citas, Expediente, Signos, Alergias, Documentación |
| TecnicoLab | Inicio, Laboratorio, Reportes, Documentación |
| Bioquimico | Inicio, Laboratorio, Reportes, Documentación |
| Recepcionista | Inicio, Pacientes, Citas, Admisión, Documentación |

## 4. Router y guards

- Toda ruta privada declara `meta: { requiresAuth: true, permission?: 'modulo.accion' }`.
- `authGuard` (existente, `resources/js/router/guards.js`) mantiene su contrato actual:
  - sin token y `requiresAuth` → `/login`;
  - con token y `guest` → `/`;
  - **nuevo:** con `meta.permission` sin el permiso → `/403` (pantalla de denegado, sin revelar qué permiso falta).
- Rutas públicas: `/login` y `/403`.

## 5. Componentes reutilizables

Todos en `resources/js/shared/components/`; todos aceptan `loading`/`error` cuando aplique.

| Componente | Props principales | Eventos | Uso |
|---|---|---|---|
| `EstadoCarga` | `texto?` | — | Spinner + mensaje mientras carga |
| `EstadoVacio` | `titulo`, `mensaje?`, `accion?` | `accion` | Sin datos; botón opcional |
| `EstadoError` | `mensaje`, `reintentable?` | `reintento` | Fallo de carga con reintento explícito |
| `TarjetaResumen` | `titulo`, `valor`, `subtitulo?`, `tono?` | — | Tarjeta del resumen inicial |
| `TablaBasica` | `columnas[]`, `filas[]`, `cargando?`, `pagina?`, `total?` | `cambiarPagina`, `filaSeleccionada` | Tabla con encabezado y paginación |

## 6. Patrón de estados de página

Toda página con datos remotos sigue exactamente esta máquina de estados:

```
cargando → error (con reintento) → vacío → datos
```

- `cargando`: se muestra `EstadoCarga`.
- `error`: se muestra `EstadoError`; el botón de reintento reejecuta la carga original (todo reintento tiene salida).
- `vacío`: la respuesta fue 200 sin filas → `EstadoVacio`.
- `datos`: contenido normal.
- Nunca se muestran dos estados a la vez; el estado inicial es `cargando`.

## 7. Interceptor de respuesta Axios y errores comunes

Interceptor de **respuesta** agregado en `resources/js/plugins/axios.js` (el de request ya existe):

| HTTP | Comportamiento transversal |
|---|---|
| 401 | Token inválido/expirado → `auth.clearSession()` + redirect a `/login?expired=1` |
| 403 | Sin permiso → redirect a `/403`; no se muestra el permiso faltante |
| 422 | Se propaga al formulario que lo solicitó (ya lo maneja su vista) |
| 404/409 | Se propaga a la vista con su mensaje |
| Error de red | `EstadoError` con reintento |

Formato de error del backend (alineado con el acuerdo de respuestas comunes):

```json
{ "message": "Texto legible", "code": "CODIGO_DOMINIO", "trace_id": "opcional" }
```

## 8. Fuente de permisos (contrato con auth/RBAC)

- Esperado: `GET /auth/login` y `GET /auth/me` responden `user.permissions: string[]` (permisos `modulo.accion` del usuario en el tenant).
- El store `auth` (Pinia) guarda `user` en `localStorage` (`auth_user`) y el menú lee `user.permissions`.
- **Pendiente (Cindy/María):** si `user` aún solo trae `roles`, se agrega un adaptador `resolvePermissions(user)` en `resources/js/shared/permissions.js` con fallback a `roles` vía una lista estática temporal, marcado como *propuesto, pendiente de validar*. El resto del contrato no cambia.

## 9. Permisos definidos por este módulo (enviados a María)

| Permiso | Acción | Rol sugerido |
|---|---|---|
| `reportes.ver` | Consultar reportes operativos | AdminHospital, Medico, Bioquimico, TecnicoLab, SuperAdmin |
| `reportes.exportar` | Exportar CSV/PDF | AdminHospital, SuperAdmin |
| `documentacion.ver` | Consultar centro de documentación y manuales | Todos los roles |
| `citas.ver` / `citas.crear` / `citas.confirmar` / `citas.cancelar` | Flujo de citas | Recepcionista: ver/crear/confirmar/cancelar; Medico: ver/confirmar/cancelar |

## 10. Pendientes y riesgos

- [ ] Cindy confirma la forma exacta de `user.permissions` (§8).
- [ ] María confirma los nombres de permisos de la tabla de §3.
- [ ] Decisión de accesibilidad (WCAG) del sidebar colapsable: foco visible y `aria-expanded` — se implementa en T2.
- Toda política visual sin fuente oficial queda como supuesto pendiente de validar con el docente.

## 11. Plan de trabajo del módulo (plan de integración 17–23 oct)

| Elemento | Valor |
|---|---|
| Issue del módulo | `Refs #209` (ASII-26) |
| Worktree | `../shi-asii-26-dashboard-base` |
| Contrato | Publicado en el grupo **dom 18/10** (este documento) |
| PR en `develop` | **lun 19/10**, antes de las 20:00 |

Secuencia de PRs (cada uno = una tarea de Odoo, ≤400 líneas cambiadas):

| PR | Rama | Contenido | Tarea Odoo |
|---|---|---|---|
| 1 | `feature/asii-26-01-contrato-ui-lis671` | Este contrato + plan | `Semana 5: Dashboard base UI/UX e implementación transversal - Rosales, Lis` |
| 2 | `feature/asii-26-02-layout-menu-lis671` | Layout, menú por rol, router/guards | `Semana 7: … - Rosales, Lis` |
| 3 | `feature/asii-26-03-componentes-estados-lis671` | Componentes de estados y tarjetas | `Semana 9: … - Rosales, Lis` |
| 4 | `feature/asii-26-04-home-interceptor-lis671` | Home con resumen e interceptor 401/403 | `Semana 16: … - Rosales, Lis` |

Cada PR se crea desde `origin/develop` en este worktree, nunca se hace force push y no se mezclan módulos. El menú por rol usa `user.permissions` (§8) con adaptador temporal hasta que auth/RBAC respondan; ninguna ruta se muestra sin verificar el permiso.
