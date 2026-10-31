# Órdenes de laboratorio desde EMR — Semana 12: segunda evaluación parcial

**Módulo:** Órdenes de laboratorio desde EMR (ASII-16) · **Entrega académica:** Fajardo López, Javier Alexander (`Javifa2324`, ASII-09)
**Defensa:** componentes, UX, accesibilidad y movilidad de «emisión de orden desde el expediente y seguimiento de su estado».
**Presentación:** [`presentacion.md`](./presentacion.md) · **Matriz:** [`matriz_decisiones.md`](./matriz_decisiones.md) · **Cambio:** [`respuesta_cambio.md`](./respuesta_cambio.md)
**Prototipo:** [`prototype/index.html`](../../../micro-his/ordenes-laboratorio/prototype/index.html) · **Mapa:** [`semana-11/navegacion.svg`](../semana-11/navegacion.svg)

## 1. Tesis de la defensa

El prototipo no demuestra solo que el flujo «se ve bien». Demuestra que las decisiones de diseño se
pueden sostener con cuatro evidencias distintas:

1. **Componentes**: el dominio, la aplicación, la persistencia y la entrada tienen límites
   verificables; las pruebas ejercitan los mismos casos de uso sobre InMemory, SQLite y PostgreSQL.
2. **UX**: la bandeja, la emisión, la confirmación y el detalle responden a una tarea clínica
   concreta, no a una colección de pantallas.
3. **Accesibilidad**: el foco, los mensajes `aria-live`, el error `role="alert"`, las etiquetas y los
   objetivos táctiles están implementados en el prototipo y declarados con sus límites.
4. **Movilidad**: a 390 px se mantiene la jerarquía, no hay scroll horizontal y las acciones
   principales quedan al alcance del pulgar.

La frase que resume la defensa: **cada decisión visible en la interfaz tiene un requisito, una
prueba o una captura que la respalda; lo que no está implementado se declara como pendiente.**

## 2. Qué se demuestra en el recorrido

| Tema | Cómo se demuestra | Evidencia |
|---|---|---|
| Roles | El prototipo usa el contexto fijo «Dra. Méndez · Médico»; el contrato exige `laboratorio.ordenes.*` y la API responde 403 sin permiso | `tests/api.php`; limitación declarada |
| Estados | `Emitida → Tomada → En proceso → Con resultado → Validada`; cancelación con motivo | `tests/run.php`, `tests/mvc.php` y detalle del prototipo |
| Errores | Cancelar una orden validada devuelve 409 con código, HTTP y `request_id` | Capturas `desktop-error.jpg` y `movil-error.jpg` |
| Trazabilidad | Cada transición genera un evento con actor, fecha, motivo y `correlation_id`; el historial no se edita | `lab_order_events` y pruebas de secuencia |
| Riesgos | Edición posterior a la emisión, cancelación accidental y permisos confiados al gateway | Sección 6 y respuesta al cambio |

## 3. Recorrido de la presentación

| Diapositiva | Contenido | Evidencia que se muestra |
|---|---|---|
| 1 | Problema y promesa: la orden es un documento clínico con estado e historial | Semana 1 |
| 2 | Actores, alcance y RF/RNF | Semana 2 |
| 3 | Componentes y capas; qué cambia en cada una | Semana 7 |
| 4 | UX del flujo feliz | `semana-11/capturas/desktop-*.jpg` |
| 5 | Estados, error crítico y recuperación | `desktop-error.jpg`, `movil-error.jpg` |
| 6 | Accesibilidad implementada y pendiente | Semana 9 + prototipo |
| 7 | Movilidad y breakpoints | `movil-*.jpg` |
| 8 | Cambio solicitado: análisis, decisión y condiciones | [`respuesta_cambio.md`](./respuesta_cambio.md) |

El guion completo, con mensajes y demostración, está en [`presentacion.md`](./presentacion.md).

## 4. Matriz decisión → RF/RNF → evidencia

La matriz completa está en [`matriz_decisiones.md`](./matriz_decisiones.md). Resume trece decisiones:
dominio, idempotencia, tenant, permisos, historial, ítems, errores, persistencia, cliente, flujo UX,
recuperación, movilidad y cancelación masiva. Cada fila indica el requisito, la evidencia y si está
**verificada**, **verificada con límite** o **diferida**.

## 5. Cambio solicitado

**Solicitud:** «Desde la bandeja quiero seleccionar varias órdenes y cancelarlas de una vez para no
repetir el mismo motivo.»

**Decisión:** se **difiere**; no se implementa en esta entrega. La respuesta documentada incluye
impacto en UX, accesibilidad, dominio, contrato, permisos, persistencia, trazabilidad, riesgos y
criterios de aceptación futuros. La alternativa segura para el usuario sigue siendo cancelar una
orden desde su detalle, con motivo obligatorio.

**Por qué no se acepta de inmediato:** una cancelación masiva amplifica el error humano, exige
semántica de éxito parcial, un permiso específico y pruebas de lote; además, una sola orden validada
no debe bloquear la cancelación de las demás. El análisis está en [`respuesta_cambio.md`](./respuesta_cambio.md).

## 6. Riesgos y límites declarados

- **R1. Permisos confiados al gateway.** La API lee `X-Permissions`; en producción debe derivarla del
  token y no confiar en una cabecera del cliente.
- **R2. Prototipo sin roles reales.** El contexto es fijo y no hay autenticación; la defensa de roles
  se apoya en el contrato y las pruebas, no en la UI.
- **R3. Datos en memoria.** El prototipo pierde el estado al recargar; no sustituye la API.
- **R4. Accesibilidad parcial.** Hay implementación heurística, pero falta auditoría con lector de
  pantalla y pruebas con usuarios.
- **R5. Estados compartidos.** La coordinación con muestras y resultados sigue pendiente de acuerdo;
  el módulo no inventa esos estados.
- **R6. Cambio diferido.** La cancelación masiva no está implementada ni cubierta por pruebas.

## 7. Cierre

La defensa se apoya en artefactos reproducibles: pruebas del micro-HIS, contrato OpenAPI, prototipo
navegable, mapa de navegación y capturas de escritorio y móvil. Lo que falta se declara; no se
presenta como terminado.

## 8. Declaración de IA

[`DECLARACION_IA.md`](./DECLARACION_IA.md), con validación humana pendiente.
