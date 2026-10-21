# Semana 9 — Evaluación del diseño, usabilidad y accesibilidad

**Módulo:** ASII-02 — RBAC: roles, permisos y protección de rutas  
**Estudiante:** LÓPEZ FAJARDO, MARÍA DE LOS ÁNGELES · @Mar-03

## Objetivo

Evaluar el flujo UX de RBAC (Semana 8) con las 10 heurísticas de Nielsen y WCAG 2.1 nivel AA —teclado, foco, contraste, etiquetas, mensajes y prevención de errores— y priorizar las correcciones según su impacto en roles, permisos, rutas protegidas, decisiones de autorización y denegaciones auditables.

## Alcance y escenarios por rol

Se evalúan las seis pantallas del flujo «asignación de rol, autorización de una operación y denegación trazable» (`docs/rbac/semana-08/`): asignar rol, estado vacío, carga/validación, confirmación/denegación, éxito/error recuperable y autorizar operación.

| Rol | Escenario evaluado | Dato sensible a proteger |
|---|---|---|
| admin_hospital | Asigna un rol dentro de su hospital (tenant). | Usuarios y roles de otros tenants. |
| medico / enfermeria | Solicita autorizar una operación y recibe permitido/denegado. | Motivo de la denegación y `trace_id`. |
| recepcion | Intenta una acción sin permiso y recibe denegación trazable. | Rutas protegidas no visibles sin permiso. |
| auditor | Consulta la bitácora de denegaciones. | Denegaciones de otros tenants. |

## Método

- Evaluación heurística (Nielsen, 10 principios) + inspección WCAG 2.1 AA.
- Pruebas manuales simuladas sobre los wireframes: recorrido solo con teclado (Tab/Enter/Esc), lectura con lector de pantalla (NVDA/VoiceOver), verificación de contraste y zoom al 200 %.
- Severidad: Alta (bloquea o induce una decisión de autorización errónea), Media (dificulta auditar u operar por teclado), Baja (cosmético).
- Prioridad = impacto en la decisión de autorización × frecuencia de uso del rol.

## Checklist WCAG 2.1 AA (por principio)

| # | Principio / criterio | Resultado | Nota |
|---|---|---|---|
| P1 | Perceptible 1.1.1 — texto alternativo en íconos de estado | No cumple | Estados sin ícono con alt. |
| P2 | Perceptible 1.3.1 — información y relaciones (labels asociados) | Cumple | Campos con label. |
| P3 | Perceptible 1.4.1 — uso del color | No cumple | Denegado/éxito solo por color. |
| P4 | Perceptible 1.4.3 — contraste mínimo 4.5:1 | Parcial | Placeholders y deshabilitado por verificar. |
| P5 | Perceptible 1.4.11 — contraste de componentes no textuales | Parcial | Bordes de campos y foco por verificar. |
| O1 | Operable 2.1.1 — todo operable por teclado | Parcial | Sin trampa, pero orden no documentado. |
| O2 | Operable 2.4.3 — orden del foco lógico | Parcial | Hospital→Usuario→Rol→acción no definido. |
| O3 | Operable 2.4.7 — foco visible | Cumple | Foco en el control del error. |
| C1 | Comprensible 3.3.1 — identificación de errores | Parcial | Botón deshabilitado no nombra la causa. |
| C2 | Comprensible 3.3.2 — etiquetas o instrucciones | Cumple | Instrucción del permiso requerido. |
| C3 | Comprensible 3.3.3 — sugerencia ante error | No cumple | No sugiere cómo corregir. |
| R1 | Robusto 4.1.2 — nombre, función y valor | Cumple | Botones con nombre accesible. |
| R2 | Robusto 4.1.3 — mensajes de estado | No cumple | «Validando…» sin `aria-live`. |

## Checklist heurístico (Nielsen)

| # | Heurística | Resultado | Nota |
|---|---|---|---|
| N1 | Visibilidad del estado del sistema | Parcial | Carga/validación visibles; `trace_id` sin jerarquía. |
| N2 | Correspondencia con el mundo real | Cumple | Lenguaje de roles/permisos claro. |
| N3 | Control y libertad del usuario | Cumple | Cancelar y reintentar presentes. |
| N4 | Consistencia y estándares | Parcial | Códigos (201/403/409/500) mezclados con texto. |
| N5 | Prevención de errores | Cumple | Confirmación y validación previa. |
| N6 | Reconocer antes que recordar | Parcial | Requisitos del permiso no siempre visibles. |
| N7 | Flexibilidad y eficiencia | Parcial | Sin atajos para auditar la denegación. |
| N8 | Diseño estético y minimalista | Cumple | Pantallas sin ruido. |
| N9 | Ayuda a reconocer y recuperarse de errores | Parcial | Mensaje de error recuperable sin causa detallada. |
| N10 | Ayuda y documentación | Cumple | Ayuda contextual en vacío y carga. |

## Hallazgos (10)

| ID | Sev. | Pantalla | Criterio | Evidencia / reproducción | Impacto |
|---|---|---|---|---|---|
| H1 | Alta | 4. Confirmación/denegación | WCAG 1.4.1 | La denegación 403 se distingue solo por color rojo. En escala de grises no se diferencia de «permitido». | Un operador con baja visión puede creer autorizada una acción denegada. |
| H2 | Alta | 1. Asignar rol | WCAG 3.3.1/3.3.3 | El botón «Asignar» deshabilitado no indica qué falta (permiso, tipo o campo vacío). | El admin no sabe por qué no puede asignar; bloquea una ruta protegida. |
| H3 | Alta | 6. Autorizar operación | WCAG 1.3.1 | El resultado `allowed:false` no expone el motivo de forma estructurada junto al control. | La decisión de autorización no es comprensible ni auditable en pantalla. |
| H4 | Media | 3. Carga y validación | WCAG 4.1.3 | «Validando…» no usa `aria-live`; un lector de pantalla no anuncia el cambio de estado. | El usuario no sabe si la decisión está en curso. |
| H5 | Media | 1. Asignar rol | WCAG 2.4.3 | No está definido el orden de foco (Hospital→Usuario→Rol→acción). | Operar por teclado confunde y puede enviar datos incompletos. |
| H6 | Media | 4. Confirmación/denegación | Nielsen #1/#7 | El `trace_id` es texto plano, sin etiqueta ni botón «Copiar». | Auditar la denegación es lento y propenso a error. |
| H7 | Media | 2. Estado vacío | WCAG 1.4.3 | Ayuda y placeholders podrían no alcanzar contraste 4.5:1. | Texto de guía difícil de leer. |
| H8 | Media | 5. Éxito/error recuperable | Nielsen #9 | El error 500 recuperable no explica la causa ni si hubo rollback. | El usuario no sabe si la asignación se guardó. |
| H9 | Baja | 1–6. Todas | WCAG 1.4.11 | El indicador de foco y los bordes de campo pueden no alcanzar 3:1. | Menor visibilidad del control activo. |
| H10 | Baja | 4/6. Denegación | Seguridad/privacidad | El mensaje de denegación debe confirmar que no revela datos de otros tenants. | Riesgo de fuga entre hospitales si se detalla de más. |

## Priorización (impacto × frecuencia)

| Prioridad | Hallazgos | Razón |
|---|---|---|
| P1 (crítica) | H1, H2, H3 | Afectan directamente la decisión de autorización y su comprensión. |
| P2 (alta) | H4, H5, H6 | Trazabilidad y operación por teclado. |
| P3 (media) | H7, H8 | Legibilidad y recuperación de errores. |
| P4 (baja) | H9, H10 | Cosmético y verificación de privacidad. |

## Backlog priorizado con criterio verificable

| # | Hallazgo | Corrección propuesta | Esfuerzo | Prueba de aceptación |
|---|---|---|---|---|
| B1 | H1 | Ícono + prefijo textual («Denegado»/«Permitido»); no depender del color. | S | En escala de grises se distingue el estado (cumple 1.4.1). |
| B2 | H2 | Listar junto al botón los requisitos faltantes (permiso/campo/tipo). | M | Al faltar un dato, el mensaje nombra cuál (cumple 3.3.1/3.3.3). |
| B3 | H3 | Mostrar `motivo` y `trace_id` estructurados junto al resultado de `authorize`. | M | El resultado de autorizar indica permitido/denegado y motivo legibles (cumple 1.3.1). |
| B4 | H4 | `aria-live="polite"` en el área de estado con texto «Validando privilegio». | S | Un lector anuncia el cambio (cumple 4.1.3). |
| B5 | H5 | Definir `tabindex`/orden DOM Hospital→Usuario→Rol→acción. | S | Recorrer con Tab sigue ese orden (cumple 2.4.3). |
| B6 | H6 | Etiquetar el `trace_id` y añadir botón «Copiar» accesible. | S | El lector lee «trace_id» y el botón; se copia con teclado. |
| B7 | H7 | Ajustar color de ayuda/placeholder a contraste ≥ 4.5:1. | S | Un verificador da ≥ 4.5:1 (cumple 1.4.3). |
| B8 | H8 | Mensaje de error que indique causa y si hubo rollback («no se guardó»). | M | Tras un 500 el usuario ve que nada se guardó (cumple Nielsen #9). |
| B9 | H9 | Subir contraste del foco y bordes a ≥ 3:1. | S | Verificador da ≥ 3:1 (cumple 1.4.11). |
| B10 | H10 | Revisar textos de denegación para no incluir datos de otros tenants. | S | El mensaje solo muestra datos del tenant propio. |

## Evidencia y método

- Inspección sobre los seis wireframes de `docs/rbac/semana-08/README.md`.
- Referencias: 10 heurísticas de Nielsen y WCAG 2.1 nivel AA (1.1.1, 1.3.1, 1.4.1, 1.4.3, 1.4.11, 2.1.1, 2.4.3, 2.4.7, 3.3.1, 3.3.2, 3.3.3, 4.1.2, 4.1.3).
- Las correcciones (B1–B10) se implementan en la UI Vue de las semanas de código; aquí quedan como backlog priorizado con prueba de aceptación.

## Riesgos y supuestos

- Las pruebas son manuales sobre wireframes de baja fidelidad; se repiten sobre el mockup navegable (Semana 11) y la UI real.
- El contraste exacto se confirma al definir la paleta en la implementación.

## Issue y PR

- Issue relacionado: `Refs #4`.
- PR menor de 400 líneas.
