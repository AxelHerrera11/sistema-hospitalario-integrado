# Hallazgos

Los hallazgos se limitan a evidencia concreta del flujo, wireframes y contrato
disponibles. No son defectos de una interfaz implementada: esa interfaz aún no
existe. Severidad: **Alta**, **Media**, **Baja**; prioridad se ordena en el
[backlog](./backlog-priorizado.md).

## UX-01 — Acciones protegidas sin fuente de permisos efectivos

- **Pantalla/flujo:** entrada, búsqueda, registro y edición.
- **Descripción:** Semana 8 condiciona acciones a permisos efectivos, pero el
  frontend existente no recibe `user.permissions` en login/`me`; su helper
  `can()` deniega si faltan. No existe pantalla/ruta de Pacientes conectada.
  Los roles de `config/rbac.php` no son una fuente de permiso por usuario.
- **Principio/criterio:** prevención de errores, seguridad y control del usuario.
- **Severidad:** Alta.
- **Impacto:** mostrar una acción basándose en rol o asumir permiso puede
  confundir al usuario y revelar acciones/datos a quien no corresponde.
- **Recomendación:** antes de implementar, acordar una fuente verificada de
  permisos efectivos; ocultar acciones no autorizadas y comprobar cada
  operación en servidor. Mantener `pacientes.ver`, `pacientes.registrar` y
  `pacientes.editar`; el cliente no sustituye autorización.

## UX-02 — `global_id` obligatorio sin guía de formato ni procedencia

- **Pantalla/flujo:** registro.
- **Descripción:** el wireframe marca `Global ID *` como requerido, pero no
  explica que el contrato lo define como UUID y referencia al MPI, ni cómo
  obtiene el usuario ese valor.
- **Principio/criterio:** prevención de errores y reducción de carga cognitiva.
- **Severidad:** Media.
- **Impacto:** facilita errores de formato o que se confunda con el código
  local; puede bloquear el alta por un dato incorrecto.
- **Recomendación:** mostrar instrucción breve sobre formato y procedencia,
  validación junto al campo y un error accionable, sin sugerir ni inventar
  una búsqueda/autocompletado de datos MPI.

## UX-03 — Opciones de estado sin significado para quien registra/edita

- **Pantalla/flujo:** registro y edición.
- **Descripción:** el diseño presenta `Estado` como selector y documenta
  `active` por defecto, pero no especifica etiquetas ni explica los valores
  permitidos por el contrato (`active`, `inactive`, `deceased`).
- **Principio/criterio:** lenguaje comprensible, consistencia y prevención de errores.
- **Severidad:** Media.
- **Impacto:** quien opera puede escoger un estado sin comprender su efecto o
  interpretar de forma distinta los valores internos.
- **Recomendación:** acordar con el responsable funcional etiquetas y
  significado en lenguaje del usuario antes de implementar; conservar los
  valores del contrato y no inferir reglas clínicas nuevas.

## UX-04 — Cancelar edición no contempla cambios pendientes

- **Pantalla/flujo:** edición de paciente.
- **Descripción:** el wireframe ofrece `Cancelar`, pero no indica si los
  cambios modificados se descartan inmediatamente o si se pide confirmación.
- **Principio/criterio:** control del usuario y prevención de errores.
- **Severidad:** Media.
- **Impacto:** una salida accidental puede perder cambios de nombres, fecha,
  contacto o estado y obligar a repetir la tarea.
- **Recomendación:** definir detección de formulario modificado y confirmar
  descarte solo cuando haya cambios; permitir volver al formulario sin perderlos.

## UX-05 — Conflicto de registro tiene códigos distintos entre contrato y módulo

- **Pantalla/flujo:** alta duplicada y recuperación tras error.
- **Descripción:** el OpenAPI documenta conflicto por código/`global_id` como
  HTTP `409`; el controlador probado devuelve `422` para `PATIENT_CODE_EXISTS`.
  Semana 5 advierte que el adaptador HTTP que normalizaría la respuesta aún
  no existe.
- **Principio/criterio:** consistencia, visibilidad del estado y recuperación.
- **Severidad:** Media.
- **Impacto:** el cliente puede tratar una duplicidad como validación genérica
  y no orientar al usuario para corregir el código o resolver el conflicto.
- **Recomendación:** definir el mapeo del adaptador y del cliente para ambos
  resultados; mostrar un mensaje que identifique el campo conflictivo sin
  revelar datos del otro paciente. No mostrar excepciones ni información sensible.

## A11Y-01 — No hay evidencia visual para evaluar contraste

- **Pantalla/flujo:** todas las pantallas diseñadas.
- **Descripción:** los wireframes son ASCII y no definen colores, tipografía,
  estados visuales ni tokens; por tanto, no se puede calcular contraste ni
  comprobar estados de error/carga frente al fondo.
- **Principio/criterio:** contraste y percepción no dependiente del color.
- **Severidad:** Media.
- **Impacto:** si se implementa sin criterios visuales verificables, texto o
  estados podrían resultar difíciles de percibir.
- **Recomendación:** definir colores y estados con texto/iconografía además
  del color y medir contraste en el prototipo/frontend antes de aceptar la UI.
  Este hallazgo es falta de evidencia, no una afirmación de incumplimiento WCAG.

## A11Y-02 — Foco de estados dinámicos queda abierto

- **Pantalla/flujo:** búsqueda, validación, error y denegación de acceso.
- **Descripción:** las decisiones UX indican mover el foco al resumen de
  errores; el wireframe de registro indica enfocarlo en el primer campo
  inválido. No se aclara si se trata de momentos distintos, ni se define el
  destino/restauración de foco por estado o su relación con el campo.
- **Principio/criterio:** navegación por teclado, foco visible y mensajes comprensibles.
- **Severidad:** Media.
- **Impacto:** una persona que usa teclado o lector de pantalla puede no
  encontrar el resultado nuevo o cómo recuperarse.
- **Recomendación:** especificar en cada estado si se anuncia sin mover el
  foco o si este se mueve al título/resumen/primer campo inválido; asegurar
  retorno predecible y asociación del error al control en la implementación.
