# Estrategia responsive

Propuesta para los flujos y wireframes de Semana 8; no describe componentes implementados. El contenido se adapta al espacio sin ocultar información esencial.

## Breakpoints propuestos

| Ancho CSS | Comportamiento |
|---|---|
| 320–359 px | Mínimo soportado. Una columna, padding lateral de 16 px, controles al ancho disponible y acciones apiladas si no caben. Sin scroll horizontal de página. |
| 360–389 px | Mantener una columna; permitir acciones lado a lado únicamente si cada objetivo conserva espacio táctil suficiente y el texto no se trunca. |
| 390–429 px | Usar el espacio extra en separación y legibilidad; conservar el mismo orden y jerarquía. No introducir paneles secundarios. |
| 430 px | Límite superior de esta especificación. Mantener una columna y ancho de lectura acotado; no escalar controles o texto para llenar espacio. |

Los cambios responden al ancho, no al dispositivo u orientación. Permitir zoom y reflujo; validar estos valores en prototipo.

## Reglas generales

- Encabezado compacto con retorno claro; no rediseñar navegación global ni fijar barras que resten espacio.
- Orden vertical: contexto, tarea, estado y acciones. Sin columnas laterales ni dependencias de hover.
- Sin alturas fijas; mantener foco y control activo visibles al aparecer teclado virtual.
- Anunciar carga en contexto y evitar doble envío; distinguir carga, vacío, error y acceso restringido.
- Errores junto al control relacionado; el resumen orienta, no reemplaza la explicación local.
- No depender de color, iconos sin texto, desplazamiento lateral, truncamiento esencial o gestos únicos.

## Formularios y campos largos

- Una columna; etiquetas persistentes, instrucciones y requeridos expresados con texto.
- Controles al ancho disponible, sin mínimos fijos. En código/`global_id` largos, no ocultar ni cambiar el valor al recortar.
- Separar campos y botones; usar teclado móvil adecuado y nunca placeholder como etiqueta.
- Error bajo el control, asociado y accionable. Conservar valores ante error recuperable y prevenir doble envío.
- Mostrar campos requeridos y opcionales sin ocultar los esenciales; preservar orden lógico de foco.

## Datos y tablas

El contrato busca por código y devuelve un paciente, no un listado. Evitar tabla ancha o scroll horizontal: detalle en pares etiqueta/valor apilados y valores largos con salto de línea. No exponer `patient_id`/`global_id`; contacto solo si es necesario.

Si se agrega listado, preferir tarjetas. Solo usar scroll horizontal para datos comparables no reestructurables y ofrecer indicación y alternativa accesible.

## Acciones

- Acción primaria a ancho completo en 320 px; secundaria debajo. No expresar jerarquía solo con color.
- En 360–430 px compartir fila solo si los nombres caben y cada objetivo es táctil; ante duda, apilar.
- Acciones junto al contenido, sin barras fijas que tapen campos. Cancelar no debe sustituir guardar.
- Ocultar acciones no permitidas según permisos efectivos: `pacientes.ver`
  para buscar/ver, `pacientes.registrar` para alta y `pacientes.editar` para
  editar. Sin fuente verificable de permisos, fallar de forma cerrada. El
  servidor vuelve a autorizar toda operación.
- Mostrar solo la acción de registro cuando el usuario tenga permiso aunque
  no tenga permiso de ver; el alta no habilita consulta ni revela datos.

## Accesibilidad móvil

- Objetivos táctiles cómodos y separados; validar su tamaño en prototipo.
- Etiquetas persistentes, texto legible y contraste medible; el color complementa texto/icono.
- Foco en orden visual y no cubierto por teclado; mantenerlo al cerrar el teclado.
- Anunciar estados; ante validación, orientar al resumen/control sin saltos inesperados.
- Permitir zoom/reflujo sin perder contenido. Diseño básico, no prueba en dispositivo ni conformidad WCAG.

## Privacidad y estados

Mostrar lo mínimo: código en búsqueda/identificación; nombre y nacimiento en detalle autorizado; contacto solo si hace falta. No exponer IDs técnicos ni valores en errores, URLs, analítica o notificaciones. No persistir formularios localmente.

Ante error recuperable, conservar entrada en el formulario, no en el mensaje. Ante denegación, no mostrar resultados, datos sensibles ni detalles RBAC. Validar que scroll/teclado no oculten errores o acciones.
