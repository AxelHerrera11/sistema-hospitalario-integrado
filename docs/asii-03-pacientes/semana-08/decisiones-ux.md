# Decisiones UX

## Permisos y denegación segura

- Diseñar por capacidades `pacientes.ver`, `pacientes.registrar` y `pacientes.editar`, no por nombre de rol. La matriz administrativa actual en `config/rbac.php` concede ver a médico/enfermería y las tres capacidades a recepción/superadmin; esa asignación puede cambiar independientemente del diseño.
- Evaluar registro independientemente de consulta: quien tenga `pacientes.registrar` puede abrir el alta aunque no tenga `pacientes.ver`. Sin permiso de ver, el éxito del alta no habilita consulta ni muestra datos del paciente.
- Ocultar acciones no disponibles por permiso, en vez de presentarlas como habilitadas. Mostrar “No tienes permiso para realizar esta acción” solo si una navegación directa o respuesta del servidor requiere explicar el bloqueo.
- Sin permisos verificables, fallar de forma cerrada: no mostrar búsqueda, datos ni acciones protegidas. Login y `me` actualmente devuelven roles/tenant, no `permissions`; `resources/js/router/permissions.js` también deniega cuando falta `user.permissions`.
- La interfaz mejora orientación, no es control de acceso. El backend debe autenticar, verificar tenant y autorizar cada llamada. Semana 5 documenta esta frontera, pero hoy `routes/api.php` no conecta Pacientes.

## Formularios y errores

- Mostrar etiquetas visibles, campos requeridos, formato de fecha y reglas junto al campo; para nacimiento, informar que no se acepta una fecha futura.
- Mantener los valores escritos tras error de validación o persistencia; enfocar/anunciar el problema y señalar cómo corregirlo o reintentar.
- Evitar doble envío durante carga. En éxito explicar la acción completada y el siguiente paso: por ejemplo, buscar el registro por su código.
- Traducir códigos funcionales conocidos a mensajes orientados a la acción. No mostrar excepción, SQL, detalles PDO, credenciales, respuesta interna del RBAC ni datos del paciente en errores. Si la operación falla por causa desconocida, informar un error neutral y ofrecer reintento cuando proceda.
- Diferenciar no encontrado (`404`) de error de transporte/persistencia: el primero permite corregir la búsqueda y, con `pacientes.registrar`, empezar un alta.

## Privacidad y mínimo necesario

Nombres, nacimiento, contacto, estado y todos los identificadores (`patient_id`, `code`, `global_id`) requieren cuidado. Buscar por código exacto evita exponer resultados por nombre. Presentar solo la información imprescindible para la tarea autorizada; omitir identificadores técnicos de la vista general, minimizar contacto y no incluir datos clínicos ajenos al contrato. La ruta OpenAPI existente lleva `code` como parámetro; no agregar otros datos de paciente a URLs ni registrar ese valor en analítica o logs de interfaz. No guardar formularios en almacenamiento persistente del navegador.

`code` y `global_id` son inmutables después del alta; `patient_id` es estable. El formulario de edición no los ofrece como campos, aunque el cliente envíe valores manipulados: el servidor sigue siendo responsable de rechazar o ignorar cambios fuera del contrato. En el diseño se muestran los campos de actualización permitidos: nombres, apellidos, fecha, sexo, contacto y estado.

## Accesibilidad básica

- Asociar etiqueta visible e instrucciones con cada control; identificar requeridos con texto además de cualquier símbolo.
- Mantener orden lógico de tabulación, operación completa por teclado y foco visible.
- Anunciar carga, resultados, éxito y errores con una región de estado accesible; mover el foco al resumen de errores al validar el formulario.
- Escribir botones y mensajes con nombres comprensibles; no depender únicamente de color para éxito, error o permiso.
- Situar cada error junto al campo correspondiente y expresar qué corregir, sin mensajes técnicos.

Esto es una lista de diseño inicial, no una auditoría de conformidad WCAG; la evaluación corresponde a Semana 9.

## Límites de implementación

No se añaden rutas, API client, componentes Vue ni lógica duplicada de JWT, tenant o RBAC. OpenAPI mantiene el contrato de Semana 5; el adaptador HTTP y la provisión confiable de permisos efectivos deben existir antes de convertir estos wireframes en interfaz funcional.
