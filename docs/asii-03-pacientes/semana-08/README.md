# ASII-03 Pacientes — Semana 8: UX para roles autorizados

## Objetivo y alcance

Diseñar la experiencia de búsqueda, consulta, registro y edición de Pacientes para usuarios con permisos efectivos, incluyendo estados y medidas de privacidad. Es una especificación UX previa a implementación visual: no se añadió frontend ni un adaptador HTTP.

## Base comprobada

El módulo permite registrar, buscar por código y actualizar datos permitidos; su contrato está en [OpenAPI](../../../micro-his/pacientes/openapi.yaml). El único frontend encontrado corresponde a autenticación, RBAC, inicio y elementos compartidos: no hay pantalla, ruta ni cliente API de Pacientes. `routes/api.php` tampoco declara rutas del módulo. La documentación de Pacientes disponible llega a Semana 5; no se encontraron carpetas de semanas 6 o 7 en esta rama.

La configuración actual concede `pacientes.ver` a médico y enfermería; recepción y superadmin también tienen `pacientes.registrar` y `pacientes.editar`. El diseño se guía por permisos, no por nombres de roles. El frontend existente no recibe permisos efectivos en login/`me`; su helper `can()` falla de forma segura sin `user.permissions`. Mostrar acciones solo será confiable cuando una fuente verificada provea permisos; el servidor conserva siempre la autoridad.

## Entrega

- [Flujo de usuario](./flujo-usuario.md): flujo principal, alternos y decisiones por permiso.
- [Wireframes](./wireframes.md): seis wireframes anotados, sin datos reales.
- [Decisiones UX](./decisiones-ux.md): estados, privacidad, formularios y accesibilidad.

Se cubren carga, vacío, éxito, errores recuperables, acceso restringido y ayuda contextual. Los estados y pantallas son diseño propuesto, no funciones ya conectadas.

## Privacidad, accesibilidad y fuera de alcance

Nombres, nacimiento, contacto, estado e identificadores se consideran sensibles. Se minimiza su exposición y no se incluyen en errores, telemetría ni wireframes. Se especifican etiquetas, teclado, foco visible, mensajes no dependientes del color y anuncios de estado.

Fuera de alcance: rutas o integración de API, cambios al contrato, autenticación/RBAC, implementación visual y evaluación WCAG completa (Semana 9).

## Validación realizada

`php micro-his/pacientes/tests/run.php`: PASS; la entrega no cambia código. Se verifican también `git diff --check`, los archivos del worktree y el conteo real incluyendo archivos nuevos, pues el diff contra `HEAD` no incluye archivos sin seguimiento.
