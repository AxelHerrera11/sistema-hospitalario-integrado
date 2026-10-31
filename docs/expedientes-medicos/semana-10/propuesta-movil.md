# Propuesta responsive (320–430 px)

Implementada en `micro-his/expediente-medico/public/ui/estilos.css` (móvil primero). Las pantallas son capturas
reales a ancho exacto tomadas con Chrome headless (herramienta y archivos en `../semana-11/`).

## Jerarquía de contenido en móvil

| Prioridad | Contenido | Decisión |
|---|---|---|
| 1 | Estado del resultado (éxito, denegado, error) | Primero dentro de «Resultado»; el foco salta a ese título al terminar |
| 2 | Identidad del paciente y número de expediente | Ficha en una columna: nombre, código, nacimiento, sexo, estado, expediente, apertura |
| 3 | Resumen | Dos chips: episodios y abiertos |
| 4 | Línea de tiempo | Tarjetas apiladas, la más reciente arriba; fechas con zona horaria |
| 5 | Motivo de atención (dato sensible) | Plegado; se despliega con un toque de 44 px de alto |
| — | Contacto, `global_id`, ids internos | No se muestran en ninguna anchura |

## Pantallas (3–5)

| # | Pantalla | Captura |
|---|---|---|
| M-1 | Búsqueda (375 px) | [`m-01-inicio-375.png`](../semana-11/evidencias/m-01-inicio-375.png) |
| M-2 | Expediente longitudinal (375 px) | [`m-02-consulta-exito-375.png`](../semana-11/evidencias/m-02-consulta-exito-375.png) |
| M-3 | Expediente en el mínimo de 320 px | [`m-05-consulta-320.png`](../semana-11/evidencias/m-05-consulta-320.png) |
| M-4 | Confirmación de apertura (375 px) | [`m-06-confirmacion-375.png`](../semana-11/evidencias/m-06-confirmacion-375.png) |
| M-5 | Acceso denegado / código inválido (375 px) | [`m-03-error-critico-sin-vinculo-375.png`](../semana-11/evidencias/m-03-error-critico-sin-vinculo-375.png), [`m-04-codigo-invalido-375.png`](../semana-11/evidencias/m-04-codigo-invalido-375.png) |

## Reglas de breakpoint

| Ancho | Diseño | Reglas |
|---|---|---|
| < 768 px (incluye 320–430) | Una columna: búsqueda arriba, resultado abajo | Controles y botones a todo el ancho y apilados; ficha en una columna; márgenes de 16 px; sin desplazamiento horizontal (texto largo con `overflow-wrap: anywhere`) |
| 768–1023 px | Dos columnas: búsqueda 300 px + resultado | Botones en fila; ficha en 2 columnas |
| ≥ 1024 px | Dos columnas: búsqueda 340 px + resultado | Ficha en 3 columnas; ancho máximo 1200 px |
| Todas | — | Objetivos táctiles ≥ 44 px; diálogo con ancho `min(480px, 100vw - 32px)`; `prefers-reduced-motion` respetado |

## Navegación, tablas y formularios en móvil

- **Navegación:** una sola pantalla con dos zonas; no hay menú propio (la navegación global es del dashboard base de Lis).
- **Tablas:** no se usan tablas para episodios; las tarjetas evitan el desplazamiento horizontal.
- **Formularios:** tres campos con etiqueta visible, ayuda permanente y error bajo el campo; `autocomplete="off"` y
  `spellcheck="false"` en el código del paciente.
- **Confirmaciones:** diálogo modal nativo con «Cancelar» enfocado y botones de 44 px.

## Recuperación ante conexión limitada

| Situación | Comportamiento | Dónde |
|---|---|---|
| Petición sin respuesta en 10 s | Se cancela (`AbortSignal.timeout(10000)`) y se muestra «Sin conexión» con «Reintentar» | `app.js` |
| Reintentos | Máximo 3 con contador visible; luego «Avise a soporte» | `siguienteReintento()` |
| Apertura sin respuesta | «Reintentar es seguro»: la apertura es idempotente en el servidor | `interpretar('abrir', 0)`, A3/A6 |
| Datos sin conexión | **No** se guardan expedientes en el dispositivo (sin `localStorage` ni caché): un teléfono perdido no expone datos clínicos | decisión de diseño; respuestas con `no-store` |
