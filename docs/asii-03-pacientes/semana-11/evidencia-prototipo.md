# Evidencia del prototipo

## Rutas y componentes

- `/patients`: acceso protegido o búsqueda del único registro ficticio.
- `/patients/detail`: detalle mínimo, sin identificadores técnicos.
- `/patients/new` y `/patients/edit`: formularios locales con validación.
- `/patients/restricted` y `/patients/saved`: restricción y confirmación.
- Componente creado: `resources/js/modules/patients/pages/PatientsPage.vue`.
- Router actualizado en `resources/js/router/index.js`; navegación añadida a
  `resources/js/shared/components/AppLayout.vue`.

Todas las rutas usan el guard de autenticación existente. La query
`?demo=1` identifica explícitamente la demostración. Las acciones no llaman a
Laravel, OpenAPI ni PDO y los cambios se pierden al recargar.

## Estados demostrables

Búsqueda local breve anunciada por estado, resultado `DEMO-001`, sin resultado, validación de campos obligatorios,
fecha futura y código duplicado, error recuperable conservando entradas,
edición local, confirmación y estado restringido sin datos. El detalle solo
presenta código demo, nombre ficticio, nacimiento ficticio y estado.

## Desktop y móvil

En desktop el formulario usa dos columnas y el detalle presenta etiquetas
junto a valores. Hasta 600 px los formularios y datos se apilan; acciones
ocupan ancho completo, con controles de al menos 44 px, foco visible,
etiquetas y errores asociados. Esto es evidencia de reglas responsive en
código; no sustituye una validación visual en navegador/dispositivo.

## Datos, permisos y limitaciones

El único registro usa `DEMO-001`, “Alex Paciente Demo” y UUID reservado
`00000000-0000-4000-8000-000000000001`; no hay teléfono ni dato clínico.
`can()` muestra si la sesión contiene cada permiso, pero no hay
`user.permissions` ni endpoint de Pacientes. Sin permiso verificable se
presenta acceso restringido. El usuario autenticado puede entrar al modo
demo explícito, que no expone datos reales y no afirma autorización.

Pendientes para integración: endpoint y middleware de Pacientes, fuente
confiable de permisos efectivos, persistencia y mapeo de errores del
contrato. No se implementó ni simuló backend.
