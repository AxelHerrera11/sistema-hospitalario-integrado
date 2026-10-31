# Flujo UX de «apertura y consulta autorizada del expediente longitudinal»

## Roles autorizados y lo que ven

| Rol | Puede | User flow | Resultado hoy |
|---|---|---|---|
| Médico tratante (`medico`) | Consultar; abrir (propuesta) | [`01-flujo-medico.svg`](01-flujo-medico.svg) | 200 con datos si tiene asignación activa |
| Recepción (`recepcion`) | Abrir (propuesta) | [`02-flujo-recepcion.svg`](02-flujo-recepcion.svg) | 403 hasta que RBAC active el permiso; en el prototipo, 201/200 |
| Enfermería (`enfermeria`) / Administración (`admin_hospital`) | — | [`03-flujo-enfermeria-admin.svg`](03-flujo-enfermeria-admin.svg) | 403 sin datos |

## Estados de la pantalla

| Estado | Cuándo | Mensaje (título · texto) | Acción ofrecida |
|---|---|---|---|
| Inicial | Al entrar | «Escriba un código de paciente y elija «Consultar expediente».» | — |
| Carga | Petición en curso | «Cargando… · Consultando/Abriendo el expediente.» | botones deshabilitados |
| Éxito | 200 con episodios / 201 / 200 `created=false` | «Expediente longitudinal», «Expediente abierto», «El expediente ya existía» | «Ocultar datos…», «Consultar expediente» |
| Vacío | 200 sin episodios / 404 `EXPEDIENTE_NO_ABIERTO` | «Expediente sin episodios» / «Sin expediente abierto» | «Solicitar apertura…» |
| Acceso denegado | 403 | «Acceso denegado» + código de seguimiento / «Sin vínculo asistencial» | ninguna (no recuperable) |
| Error recuperable | 503, sin conexión | «Hospital no disponible», «Servicio no disponible», «Datos protegidos», «Sin conexión» | «Reintentar» (máx. 3) y luego «Avise a soporte» |
| Error no recuperable | 404 paciente, 409, 422, 500 | «Paciente no encontrado», «No se puede abrir», «Código no válido», «Error inesperado» | corregir la entrada |

Todos los textos están en `micro-his/expediente-medico/public/ui/estados.js` (constante `MENSAJES`) y se prueban
con `tests/ui-estados.test.mjs`.

## Validaciones y reglas de interacción

1. El código se valida en el navegador con el mismo patrón que el servidor (`PATRON_CODIGO` = `PatientSummary::CODE`);
   el servidor vuelve a validar (422).
2. Abrir siempre pasa por un diálogo de confirmación con el foco inicial en «Cancelar».
3. Después de cada respuesta, el foco va al título «Resultado» para que el lector de pantalla anuncie el cambio.
4. Cambiar de usuario o de hospital, o pulsar «Ocultar datos del paciente», quita los datos clínicos de la pantalla.
5. Cada error recuperable ofrece reintento con contador visible y una salida explícita al agotarse.

## Ayuda contextual

- Texto de ayuda permanente bajo el campo de código, con ejemplos (`aria-describedby`).
- Mensajes de error que dicen qué hacer («Verifique el código en el módulo de Pacientes», «Comunique el código de
  seguimiento a su jefatura»).

## Protección de datos

| Riesgo | Protección | Dónde |
|---|---|---|
| Mirada ajena a la pantalla | Motivo de atención plegado; «Ocultar datos del paciente» | `vista.js` |
| Datos de otra sesión | Limpiar al cambiar usuario/hospital | `app.js` |
| Revelar existencia de pacientes | 403 igual para inexistente y sin vínculo | `ConsultLongitudinalRecord` |
| Caché del navegador | `Cache-Control: no-store` | `JsonView` |
| Inyección de HTML desde datos | Solo `textContent` | `vista.js` |

## Wireframes anotados

| Wireframe | Estado(s) |
|---|---|
| [`wireframes/wf-01-busqueda.svg`](wireframes/wf-01-busqueda.svg) | Inicio, validación, acciones |
| [`wireframes/wf-02-expediente-longitudinal.svg`](wireframes/wf-02-expediente-longitudinal.svg) | Carga y éxito |
| [`wireframes/wf-03-acceso-denegado.svg`](wireframes/wf-03-acceso-denegado.svg) | Acceso denegado (RBAC y sin vínculo) |
| [`wireframes/wf-04-confirmacion-apertura.svg`](wireframes/wf-04-confirmacion-apertura.svg) | Decisión, confirmación y éxito de apertura |
| [`wireframes/wf-05-errores-recuperables.svg`](wireframes/wf-05-errores-recuperables.svg) | Validación, errores recuperables y vacío |
