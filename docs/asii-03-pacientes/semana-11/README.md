# ASII-03 Pacientes — Semana 11

## Objetivo y alcance

Prototipo navegable del flujo principal de Pacientes para demostrar búsqueda,
detalle, registro, edición y acceso restringido en el router Vue existente.
Las pantallas operativas son una demostración local desconectada: usan un
registro y formularios ficticios en memoria; no llaman a una API ni persisten.

## Base e integración real

Se reutilizan Vue 3, Vue Router, `AppLayout`, el guard de sesión y el helper
`can()` existente. No hay rutas Pacientes en `routes/api.php`, adaptador HTTP,
ni permisos efectivos (`user.permissions`) disponibles en la sesión actual.
Fuera del modo demo, el módulo falla de forma cerrada y muestra el estado de
acceso restringido. El modo demo está claramente rotulado y no representa
autorización; el backend deberá validar cada operación al integrarse.

## Pantallas y diseño

Rutas autenticadas: `/patients`, `/patients/detail`, `/patients/new`,
`/patients/edit`, `/patients/restricted` y `/patients/saved`. La navegación
cubre búsqueda → detalle → edición y alta → validación → confirmación/error.
La página se adapta a desktop y móvil: formulario en dos columnas solo en
desktop y una columna a 600 px o menos; el layout compartido apila encabezado
y navegación móvil. Incluye foco visible y botones táctiles.

## Permisos y privacidad

Se consultan `pacientes.ver`, `pacientes.registrar` y `pacientes.editar` con
el helper existente para exponer disponibilidad. No se simula un RBAC. En la
ausencia de permisos efectivos, las pantallas funcionales solo se abren en
modo prototipo explícito, protegido por la sesión, con datos ficticios.
No se incluyen pacientes, contactos ni identificadores reales.

## Fuera de alcance y validaciones

No se cambia backend, autenticación, RBAC, rutas API ni migraciones. La
demostración no sustituye integración, autorización, persistencia ni prueba
WCAG. Validaciones: build Vite, suite PHP de Pacientes y comandos `git diff`
contra `origin/develop`; los archivos nuevos sin seguimiento se cuentan aparte.
