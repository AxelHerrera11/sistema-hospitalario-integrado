# Escenarios móviles

Recorridos propuestos para la especificación móvil, sin datos reales ni
interfaz conectada.

## Escenario 1 — Buscar y consultar un paciente

- **Usuario:** personal autorizado que consulta un registro desde un teléfono
  de 320–430 px.
- **Permiso requerido:** `pacientes.ver`; editar requiere además
  `pacientes.editar`.
- **Pasos:**
  1. Abre Pacientes; si no puede verificarse `pacientes.ver`, ve una
     restricción sin formulario ni datos.
  2. En la pantalla de búsqueda, enfoca la etiqueta/campo de código, ingresa
     el código completo y activa **Buscar paciente**.
  3. El botón evita un segundo envío y se anuncia “Buscando paciente…”.
     El scroll mantiene visible el campo o el estado en curso.
  4. Con resultado, el detalle aparece como pares verticales de etiqueta y
     valor. La acción **Editar datos** solo aparece con `pacientes.editar`;
     si no, la consulta queda en solo lectura.
  5. **Nueva búsqueda** vuelve al campo en orden lógico, sin conservar el
     detalle sensible más allá de lo necesario.
- **Resultado esperado:** un resultado identificable, legible en una columna
  y ajustado al ancho; no hay tabla ni scroll horizontal.
- **Error recuperable:** ante fallo temporal, se conserva el código en el
  campo, se explica que no se completó la consulta y se ofrece reintentar.
  Un `404` es “sin resultados”, no error de conexión.
- **Accesibilidad:** operación por teclado/táctil, foco visible y no cubierto
  por el teclado; estado anunciado sin depender solo de color. Etiquetas y
  botones conservan nombres claros con zoom.
- **Privacidad:** mostrar solo datos necesarios; no poner nombres, nacimiento,
  contacto ni identificadores en errores, URL, analítica o notificaciones.

## Escenario 2 — Registrar, corregir y confirmar

- **Usuario:** personal habilitado para alta; puede usar teléfono pequeño.
- **Permiso requerido:** `pacientes.registrar`. `pacientes.ver` solo es
  necesario para consultar luego el resultado; no se infiere por rol.
- **Pasos:**
  1. Abre **Registrar paciente**; si el permiso no se verifica, no se muestra
     la acción/formulario. La fuente de permisos debe ser efectiva, no el
     nombre del rol.
  2. Completa en una columna código, `global_id`, nombres, apellidos,
     nacimiento y sexo; contacto es opcional y estado inicia en `active`.
     La ayuda de `global_id` explica el formato UUID y su procedencia.
  3. Envía el formulario. El estado de progreso se anuncia y se previene el
     envío duplicado. Si hay validación, el resumen orienta al primer error y
     cada campo explica cómo corregirse.
  4. Corrige el dato manteniendo el resto del formulario; vuelve a enviar.
  5. Con éxito, el mensaje confirma el alta. Solo ofrece buscar por código
     si el usuario también cuenta con `pacientes.ver`.
- **Resultado esperado:** confirmación clara, sin revelar más información
  que la necesaria ni mostrar identificadores técnicos.
- **Error recuperable:** código duplicado se explica como conflicto del campo
  y no como error interno; un fallo temporal ofrece reintento conservando los
  valores. No se afirma éxito si la operación no fue confirmada.
- **Accesibilidad:** una columna entre 320–430 px; etiquetas persistentes,
  requeridos en texto, controles táctiles cómodos, foco visible, orden
  predecible y mensajes asociados. El zoom no recorta botones ni errores.
- **Privacidad:** errores no repiten valores introducidos ni confirman datos
  de otra persona. No persistir el formulario en almacenamiento del navegador;
  autorización de alta y validación final se realizan en servidor.
