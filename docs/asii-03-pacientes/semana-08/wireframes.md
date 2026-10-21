# Wireframes anotados

Diseños esquemáticos; no son componentes implementados. Los textos y valores son ficticios y no representan pacientes reales.

## 1. Búsqueda de paciente

**Objetivo:** iniciar consulta exacta por código.

```text
+------------------------------------------------+
| Pacientes                                      |
| Buscar por código                              |
| [Código del paciente____________________]      |
| [Buscar paciente]                              |
|                                                |
| Ayuda: ingresa el código completo.             |
+------------------------------------------------+
```

- **Permiso:** `pacientes.ver`. Sin permiso efectivo, no mostrar este formulario.
- **Acciones:** buscar; borrar/corregir entrada. No ofrece alta o edición por defecto.
- **Estado:** al enviar, anunciar “Buscando paciente…” y evitar solicitudes repetidas; no dejar el contenido en blanco.
- **Accesibilidad:** etiqueta asociada al campo, botón con nombre claro, tabulación en orden visual y foco visible.
- **Privacidad:** consulta por código solamente; no sugerir nombres ni mostrar identificadores ajenos en autocompletado.

## 2. Resultado encontrado / detalle

**Objetivo:** confirmar que es el paciente buscado y presentar solo datos necesarios.

```text
+------------------------------------------------+
| Paciente encontrado                            |
| Nombre: [dato autorizado]                      |
| Fecha de nacimiento: [dato autorizado]         |
| Estado: [dato autorizado]                      |
|                                                |
| [Editar datos]  (solo con permiso)              |
| [Nueva búsqueda]                               |
+------------------------------------------------+
```

- **Permiso:** `pacientes.ver`; la acción de edición requiere además `pacientes.editar`.
- **Acciones:** iniciar nueva búsqueda; editar solo si se autoriza.
- **Estado:** resultado satisfactorio; si la solicitud sigue en curso, mantener anuncio de carga.
- **Accesibilidad:** título que identifica el resultado y botones distinguibles por texto, no solo color.
- **Privacidad:** no mostrar `global_id` o `patient_id` en la vista general; limitar contacto y otros datos al propósito autorizado. No incluir datos clínicos.

## 3. Registro de paciente

**Objetivo:** registrar con validación clara y mínima pérdida de entrada.

```text
+------------------------------------------------+
| Registrar paciente                             |
| Código *             [____________________]    |
| Global ID *          [____________________]    |
| Nombres *            [____________________]    |
| Apellidos *          [____________________]    |
| Nacimiento *         [AAAA-MM-DD__________]    |
| Sexo *               [Seleccionar________]    |
| Contacto             [____________________]    |
| Estado               [Activo______________]    |
| [Guardar paciente]  [Cancelar]                 |
+------------------------------------------------+
```

- **Permiso:** `pacientes.registrar`; no se infiere por tener un rol concreto.
- **Acciones:** validar, guardar o cancelar. `status` inicia en `active`; fecha no futura.
- **Estado:** errores junto al campo y foco en el primer error; progreso anunciable al guardar; éxito confirma registro. Ofrecer búsqueda posterior solo si también tiene `pacientes.ver`. Error recuperable conserva valores.
- **Accesibilidad:** etiquetas visibles y asociadas; marcar requeridos con texto y explicar el asterisco; instrucciones de formato legibles.
- **Privacidad:** no completar con datos de otros pacientes ni imprimir los valores del formulario en mensajes de error.

## 4. Edición de paciente

**Objetivo:** modificar únicamente atributos que permite el contrato.

```text
+------------------------------------------------+
| Editar paciente                                |
| Código: [solo lectura]                         |
| Nombres *            [____________________]    |
| Apellidos *          [____________________]    |
| Nacimiento *         [AAAA-MM-DD__________]    |
| Sexo *               [Seleccionar________]    |
| Contacto             [____________________]    |
| Estado               [Seleccionar________]    |
| [Guardar cambios]  [Cancelar]                  |
+------------------------------------------------+
```

- **Permiso:** `pacientes.editar`; requiere también `pacientes.ver` para entrar desde el detalle y cargar los datos.
- **Acciones:** guardar cambios permitidos o cancelar.
- **Estado:** valida fecha y campos; ante error conserva modificaciones y anuncia el problema; éxito confirma qué acción se completó.
- **Accesibilidad:** orden lógico; etiquetas, errores asociados al campo y foco visible.
- **Privacidad:** `patient_id`, `code` y `global_id` son inmutables y no editables; no revelar `global_id` en el formulario.

## 5. Estado vacío / sin resultados

**Objetivo:** indicar que la consulta terminó sin coincidencia y ofrecer una salida útil.

```text
+------------------------------------------------+
| No encontramos un paciente con ese código.     |
| [Revisar código]                               |
| [Registrar paciente] (solo con permiso)         |
+------------------------------------------------+
```

- **Permiso:** `pacientes.ver` para buscar; el registro solo con `pacientes.registrar`.
- **Acciones:** corregir código; iniciar registro si se autoriza.
- **Estado:** resultado vacío definitivo, no confundir con carga o error.
- **Accesibilidad:** mensaje anunciado como resultado de búsqueda, acción descrita con texto.
- **Privacidad:** no sugerir coincidencias parciales ni nombres de pacientes.

## 6. Error recuperable / acceso restringido

**Objetivo:** informar sin exponer detalles internos y guiar el siguiente paso.

```text
+------------------------------------------------+
| No se pudo completar la solicitud.             |
| Puedes intentarlo de nuevo.                    |
| [Reintentar]                                   |
|                                                |
| (Sin permiso)                                  |
| No tienes permiso para realizar esta acción.   |
| [Volver a Pacientes]                           |
+------------------------------------------------+
```

- **Permiso:** una operación normal exige su permiso efectivo; si no se puede verificar, no mostrar datos ni acción protegida.
- **Acciones:** reintentar cuando sea un error temporal; corregir datos si es validación; volver a una pantalla autorizada tras denegación.
- **Estado:** distinguir error recuperable de falta de permiso; preservar código/formulario ante fallo temporal.
- **Accesibilidad:** mensaje anunciado, lenguaje claro, foco en el aviso o acción de recuperación; sin depender de color.
- **Privacidad:** no mostrar excepción, SQL, tokens, trazas internas, valores enviados ni mensajes detallados de RBAC.
