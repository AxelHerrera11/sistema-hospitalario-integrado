# Flujo UX — consulta del catálogo ASII-06

## Propósito y usuario

Permitir que una persona autenticada y autorizada consulte wards y, después,
las camas asociadas a una ward. El usuario concreto se define por su permiso
efectivo `camas.ver`, no por un nombre de rol elegido por el cliente.

Este documento es una especificación de flujo. No existe una UI de ASII-06
conectada en esta entrega. No se incorporan acciones de mantenimiento del
catálogo.

## Flujo principal

```mermaid
flowchart TD
    A[Entrar al catálogo] --> B{Sesión JWT válida}
    B -- No --> C[Solicitar autenticación]
    B -- Sí --> D{Tenant validado}
    D -- No --> E[Mostrar acceso no disponible]
    D -- Sí --> F{camas.ver autorizado}
    F -- No --> G[Mostrar acceso restringido<br/>sin datos del catálogo]
    F -- Sí --> H[Consultar GET /api/v1/wards]
    H --> I{Resultado}
    I -- 200 con wards --> J[Mostrar wards]
    I -- 200 lista vacía --> K[Estado sin wards]
    I -- 503 --> L[Error temporal<br/>ofrecer reintento]
    J --> M[Seleccionar una ward]
    M --> N[Consultar GET /api/v1/wards/{ward}/beds]
    N --> O{Resultado}
    O -- 200 con camas --> P[Mostrar camas de esa ward]
    O -- 200 lista vacía --> Q[Estado ward sin camas]
    O -- 404 --> R[Ward ya no disponible<br/>actualizar listado]
    O -- 503 --> S[Error temporal<br/>conservar ward y reintentar]
```

## Interacción y estados

1. Al abrir el catálogo, el cliente requiere una sesión JWT y el contexto de
   tenant. No hace consultas de catálogo hasta que el servidor acepte la
   identidad y la autorización.
2. Con `camas.ver`, solicita `GET /api/v1/wards`. Durante la petición muestra
   un estado de carga; ante `200`, presenta nombre, código, ubicación y estado
   de las wards devueltas.
3. Si `data` está vacío, muestra “No hay wards para mostrar” y una opción para
   volver a consultar. No sugiere crear una ward: no existe una ruta HTTP de
   alta en el alcance implementado.
4. Al seleccionar una ward, solicita
   `GET /api/v1/wards/{ward}/beds`, usando el `ward_id` recibido. Presenta
   código, tipo y estado operativo de cada cama; no permite editarlos.
5. Si la lista de camas está vacía, informa que la ward no tiene camas
   registradas. Si responde `404 WARD_NOT_FOUND`, actualiza el listado y
   comunica que la ward dejó de estar disponible.
6. Ante `503 WARD_CATALOG_UNAVAILABLE` o
   `503 BED_CATALOG_UNAVAILABLE`, informa que el catálogo no está disponible,
   conserva la selección cuando aplique y permite reintentar sin duplicar
   solicitudes.
7. Una denegación `401` conduce a autenticación; `403` muestra acceso
   restringido. En ambos casos no se muestran datos ni detalles internos de
   autenticación/RBAC.

## Permisos y datos

| Acción | Permiso/contrato |
|---|---|
| Abrir y consultar wards | `camas.ver`; `GET /api/v1/wards` |
| Consultar camas de una ward | `camas.ver`; `GET /api/v1/wards/{ward}/beds` |
| Mostrar una ward inexistente | `404 WARD_NOT_FOUND` |
| Mostrar error temporal de consulta | `503` con código seguro de catálogo |

En la configuración RBAC actual, `camas.ver` está incluido para `superadmin`
y `recepcion`. La UI debe depender de una fuente confiable de permisos
efectivos; ocultar una opción no reemplaza la autorización del middleware.

Solo se presentan los campos del contrato de lectura: `ward_id`, `code`,
`name`, `location`, `status` para wards y `bed_id`, `ward_id`, `code`, `type`,
`status` para camas. No se muestran pacientes ni estancias.

## Accesibilidad y límites

- Anunciar carga, resultado vacío y errores como mensajes de estado
  perceptibles por tecnologías de asistencia.
- Mantener foco y selección de ward al reintentar una consulta de camas.
- No comunicar los estados de cama solo mediante color.
- No inferir que `occupied` equivale a una estancia consultable ni ofrecer
  asignación, traslado, alta o cambio de estado.
- El flujo presupone las rutas actuales, pero `hospital` sigue siendo una
  conexión fija. `TenantMiddleware` valida el hospital; no existe selección
  dinámica de base ni aislamiento multi-tenant completo.
