# Estrategia responsive

Diseño propuesto para el flujo de consulta ASII-06. No describe una UI
implementada ni declara cumplimiento WCAG.

## Anchos de referencia

| Ancho CSS | Comportamiento propuesto |
|---|---|
| 320–359 px | Ancho mínimo. Una columna, padding lateral de 16 px; botones y tarjetas al ancho disponible; texto y códigos largos pueden envolver. |
| 360–389 px | Mantener orden y una columna; usar espacio extra en separación, no para agregar información secundaria. |
| 390–429 px | Misma jerarquía y controles; la tarjeta puede ganar espacio interno sin comprimir datos. |
| 430 px | Límite superior de la especificación móvil; conservar ancho legible y sin panel lateral. |

Son rangos de diseño para probar en el prototipo, no breakpoints añadidos al
CSS. El layout compartido actual despliega su sidebar a partir de 1024 px; la
referencia de Pacientes adapta formularios y acciones bajo 600 px. Semana 11
deberá integrar la pantalla en el layout real y comprobar cómo conviven ambos
comportamientos.

## Estructura y contenido

- Mantener navegación global del HIS; no añadir una segunda barra fija ni
  duplicar el menú. La página comienza con título y orientación “Catálogo de
  wards y camas”.
- Mostrar wards como lista vertical de tarjetas. Cada tarjeta presenta
  `code`, `name`, `location` y `status`, en ese orden; la acción para ver camas
  se nombra explícitamente.
- Después de seleccionar una ward, mostrar su nombre y código como contexto,
  seguido de camas en otra lista vertical. Cada cama muestra `code`, `type` y
  `status`; `ward_id` sirve para la relación del contrato, no se necesita
  repetir como identificador técnico en pantalla.
- No usar tablas anchas, carruseles ni scroll horizontal. No truncar el nombre,
  código o estado esencial; permitir saltos de línea en valores largos.
- Evitar alturas fijas. El scroll es vertical y el contenido/acciones no deben
  quedar cubiertos por el teclado virtual, zoom o encabezado del sistema.

## Interacción y estados

- La acción principal en una tarjeta de ward es “Ver camas de esta ward”.
  Usar botones o enlaces nativos, no interacción exclusiva por gesto, hover o
  toda la tarjeta sin nombre accesible.
- Mostrar estado de carga cerca de la lista consultada y deshabilitar la
  acción que produciría solicitudes duplicadas.
- Diferenciar lista vacía de wards, ward sin camas, error temporal, sesión no
  válida y acceso denegado. El error temporal ofrece reintento; al reintentar
  camas se conserva la ward seleccionada.
- Un `404 WARD_NOT_FOUND` retorna al listado para actualizarlo; `401` remite a
  autenticación y `403` explica que la sesión no tiene autorización. No mostrar
  contenido del catálogo cuando la autorización falla.
- Anunciar carga, resultado vacío y error mediante regiones semánticas
  apropiadas (`status`/`alert`) una vez se implemente. Mantener un foco
  predecible al cambiar entre listado y camas.

## Accesibilidad y lenguaje

- Operación completa con teclado y pantalla táctil; foco visible y orden igual
  al orden visual. No ocultar ni cubrir el foco.
- Asegurar controles táctiles cómodos y separados; medirlos en el prototipo.
- No depender solo del color para indicar estado. Mostrar el valor textual
  devuelto por la API mientras no se acuerden etiquetas comprensibles con la
  responsable funcional; no traducir estados por suposición.
- No asociar `occupied` a una estancia o paciente. No añadir datos de paciente
  ni acciones clínicas al catálogo.
- Permitir zoom y reflujo sin pérdida de información; probar en navegador y
  con lector de pantalla en la etapa de prototipo.

## Seguridad y datos

El permiso existente es `camas.ver`; la futura UI puede ocultar navegación
cuando los permisos efectivos no lo incluyen, pero la autorización decisiva
permanece en el servidor. Consumir únicamente los campos documentados de wards
y beds. No guardar respuestas de catálogo en almacenamiento local para resolver
el tenant ni mostrar detalles internos de errores.
