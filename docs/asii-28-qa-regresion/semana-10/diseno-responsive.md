# Decisiones responsive y evaluación técnica

## Mobile-first

Base de una columna, padding 16 px, texto 16 px/1.5, campos al ancho disponible,
tarjetas y acciones con flex-wrap. A 768 px: dos columnas, padding 24 px y detalle
de defecto a ancho completo; a 1100 px aumenta separación entre acciones. No hay
tablas horizontales ni anchos mínimos mayores al contenedor. overflow-wrap:anywhere
permite referencias largas sin ocultarlas. Navegación es visible y envuelve líneas,
sin menú hamburguesa dependiente de scripts. No hay barra fija que tape el contenido.

Información esencial y acciones nunca se ocultan. Filtros, actividad reciente y
campos adicionales propuestos se contraen en details/summary nativos; pueden
abrirse con teclado. Contexto, pasos/esperado y mensajes principales quedan visibles.
Los estados alternativos se presentan como ejemplos de diseño, no simultaneidad
obligatoria de una futura aplicación. El progreso no inventa estados de campaña.

## Accesibilidad inicial, sin certificación

Documento es, title, h1/h2/h3, main, nav etiquetado, secciones con aria-labelledby.
Labels con for/ID, campos “Obligatorio”, ejemplos de error asociados mediante
aria-describedby; aria-invalid solo en los dos ejemplos explícitos de error.
Foco visible de 3 px en enlaces/summary; acciones de navegación y summary con
min-height 44 px, espaciado 8 px. Objetivo táctil de diseño, no certificación de
todas las hitboxes ni prueba con personas. Texto #171717 sobre blanco y enlaces
#202020; no se transmite aprobado/fallido o error solamente por color.

Referencia orientativa: [WCAG 2.2 W3C](https://www.w3.org/TR/WCAG22/) — 1.3.1,
1.4.3/1.4.10, 2.1.1/2.4.7/2.5.8 y 3.3.1/3.3.2. Los controles de datos están disabled
intencionalmente: no se acredita escribir/guardar, lector de pantalla ni anuncio
dinámico. No se usa ARIA como sustituto de etiquetas ni se declara que asociar un
error en HTML resuelva por completo UX-04.

## Escenarios de revisión

Móvil pequeño: localizar campaña, abrir caso sin perder contexto y leer pasos sin
scroll horizontal. Móvil habitual: revisar formulario con etiquetas sobre campos,
evidencia guiada y cancelar una tarea específica. Tableta/escritorio: mismas cinco
pantallas en galería de dos columnas, sin cambiar contratos o esconder información.
Cada referencia, persona o contador es un placeholder ficticio, no dato clínico.

## Evidencia nueva — 29/10/2026

Edge headless 156.0.4314.50, perfil temporal aislado y Node 24 mediante DevTools.
Se abrió copia temporal exacta del HTML con Page.navigate (file URL), se usaron
Emulation.setDeviceMetricsOverride y Page.captureScreenshot y se inspeccionaron
capturas de escritorio y ejecución a 320 px. Resultados medidos, no inferidos del CSS:

| Ancho viewport | scrollWidth | Columnas main | Altura mínima enlaces/summary visibles | Desborde horizontal |
|---|---|---|---|---|
| 320 | 305 | 273 | 46 px | No |
| 375 | 360 | 328 | 46 px | No |
| 390 | 375 | 343 | 46 px | No |
| 768 | 753 | 342.5 / 342.5 | 46 px | No |
| 1280 | 1265 | 598.5 / 598.5 | 46 px | No |

Las diferencias viewport/scrollWidth incluyen la barra vertical. Anchura mínima
observada de objetivos de navegación/resumen visibles: 63.86 px; cero rectángulos
fuera del viewport en las cinco muestras. No se midieron gestos táctiles reales.
DOM: cinco secciones, doce controles con label asociado, cero IDs duplicados,
enlaces rotos o referencias aria-describedby inexistentes; cero scripts, forms,
referencias externas y controles de datos habilitados. Cinco Tab recorrieron el
índice con outline 3 px; Enter llegó a #mov-5. El summary Actividad secundaria
se enfocó y abrió por Enter (eventos key y char CDP); la primera prueba solo key
no lo activó, se completó el evento de carácter sin cambiar HTML ni fingir PASS.

Comandos del proceso de validación (herramientas/perfil/evidencia solo en TEMP):

```text
msedge.exe --headless --disable-gpu --no-first-run --remote-debugging-port=9340 --user-data-dir=<TEMP>/profile about:blank
node <TEMP>/inspect.mjs <TEMP>
```

El script consultó document.documentElement.scrollWidth, getBoundingClientRect,
gridTemplateColumns, labels/IDs/enlaces y envió Tab/Enter mediante Input.dispatchKeyEvent.
Concluyó revisión técnica de estructura y navegación; no valida controles disabled,
guardado/API, lector de pantalla, zoom real ni tareas con participantes. No se
modificó PHP, por lo que no se declaran pruebas funcionales nuevas.
