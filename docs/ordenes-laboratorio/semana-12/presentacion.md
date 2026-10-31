# Presentación breve — Semana 12

**Duración objetivo:** 8–10 minutos · **Apoyo:** prototipo abierto en `127.0.0.1:8099`.
**Regla de la defensa:** cada afirmación se muestra con una captura, una prueba o el contrato; lo no
implementado se declara.

## Diapositiva 1 — La orden no es un formulario

**Mensaje:** Una orden de laboratorio es un documento clínico con estado, dueño, motivo e historial.
Si se trata como un formulario, se pierde la trazabilidad.

**Se muestra:** portada del prototipo y la bandeja (`desktop-lista.jpg`).

**Guion:** «El problema no es capturar dos pruebas; es que después alguien debe saber quién emitió,
quién tomó la muestra, quién validó y por qué se canceló.»

## Diapositiva 2 — Actores, alcance y requisitos

**Mensaje:** El alcance se limita a la emisión desde el expediente y al seguimiento del estado. No
incluye resultados, muestras ni facturación.

**Se muestra:** tabla RF-OL-01…12 y RNF-OL-01…10 de la semana 2; matriz de la semana 12.

**Guion:** «El módulo promete crear, consultar, listar, avanzar y cancelar. Todo lo demás se integra
por contrato o queda fuera.»

## Diapositiva 3 — Componentes y límites

**Mensaje:** El dominio decide; la aplicación orquesta; la persistencia aísla por tenant; la entrada
no filtra SQL ni reglas.

**Se muestra:** diagrama de componentes de la semana 7 y resultados de `run.php` (28 casos × 3
adaptadores), `mvc.php` (12), `api.php` (18) y `client.php` (6).

**Guion:** «El mismo caso de uso se ejecuta sobre memoria, SQLite y PostgreSQL. Eso prueba que la
regla no está escondida en el controlador.»

## Diapositiva 4 — Camino feliz

**Mensaje:** La emisión sigue un flujo lineal y explícito: bandeja → contexto → pruebas → revisión →
confirmación → detalle.

**Se muestra:** `desktop-lista.jpg`, `desktop-emision.jpg`, `desktop-confirmacion.jpg` y
`desktop-detalle.jpg`.

**Guion:** «El paciente y el episodio llegan del expediente y son de solo lectura. El médico solo
elige pruebas y prioridad; la confirmación muestra exactamente qué se va a crear.»

## Diapositiva 5 — Estados, error y recuperación

**Mensaje:** Cancelar una orden validada no es un fallo silencioso: es un 409 explicable y recuperable.

**Se muestra:** `desktop-error.jpg` y `movil-error.jpg`; ruta
`?vista=detalle&orden=ORD-1041&accion=cancelar`.

**Guion:** «El error muestra mensaje, código, HTTP y `request_id`; el foco va al panel, no se pierde
nada y el usuario puede volver al detalle o a la bandeja.»

## Diapositiva 6 — Accesibilidad

**Mensaje:** La accesibilidad está en el flujo, no en una capa posterior: foco, etiquetas, mensajes
vivos, contraste, objetivos táctiles y diálogo nativo.

**Se muestra:** código del prototipo y la lista de verificación de la semana 9.

**Guion:** «El enlace de salto, `aria-live` para el contador y la emisión, `role=alert` para el error y
44 px de objetivo táctil son decisiones verificables. Falta la auditoría formal y se declara.»

## Diapositiva 7 — Movilidad

**Mensaje:** A 390 px se conserva la jerarquía: estado primero, acciones al alcance, sin scroll
horizontal.

**Se muestra:** `movil-lista.jpg`, `movil-emision.jpg`, `movil-detalle.jpg` y `movil-error.jpg`.

**Guion:** «No se oculta información: se reordena. Las acciones principales van a ancho completo y el
historial se lee como una línea de tiempo.»

## Diapositiva 8 — Cambio solicitado y cierre

**Mensaje:** La cancelación masiva se difiere con condiciones, no se improvisa.

**Se muestra:** [`respuesta_cambio.md`](./respuesta_cambio.md), con impacto, opciones y criterios de
aceptación.

**Guion:** «Aceptar el cambio sin semántica de éxito parcial ni permiso específico sería más riesgoso
que no tenerlo. La alternativa actual —cancelar una orden con motivo— conserva la trazabilidad.»

## Demostración en vivo (3 minutos)

1. Abrir `/?vista=lista`; filtrar por `Ana` y por estado `En proceso`.
2. **Nueva orden** → agregar `GLU` y `HB` → **Revisar y emitir** → **Emitir orden**.
3. Mostrar el detalle, el evento inicial y el `toast`.
4. Abrir `/?vista=detalle&orden=ORD-1041&accion=cancelar`; señalar 409, `request_id` y foco.
5. Reducir la ventana a 390 px y repetir la bandeja y el detalle.

## Cierre en una frase

«El sistema no promete más de lo que puede sostener: emite, sigue y cancela con reglas, permisos y
evidencia; lo que falta está documentado como riesgo o cambio diferido.»
