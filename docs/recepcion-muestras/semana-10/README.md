# Semana 10 — Adaptación responsive y prioridades móviles

**Módulo:** ASII-18, recepción de muestras y códigos identificadores.
**Autor:** María de los Ángeles López Fajardo, Mar-03.
**Issue:** [#6](https://github.com/compilations-teams/sistema-hospitalario-integrado-SistenasII-2026-G3/issues/6).
**Objetivo de la matriz:** Escenarios móviles, prioridades de pantalla y criterios verificables de adaptación.

## Índice

1. Introducción y problema.
2. Desarrollo del entregable.
3. Correspondencia con Laravel 12 y Vue.
4. Verificación y conclusión.
5. Fuentes y evidencia Git.

## Introducción y problema

En una recepción móvil el técnico dispone de menos ancho, puede usar teclado virtual y necesita
comparar una etiqueta física. Se prioriza la tarea clínica antes que paneles secundarios.
La adaptación conserva controles y estados; no elimina validaciones para hacer el formulario más corto.

## Prioridades por pantalla

| Orden | Contenido | Razón |
|---|---|---|
| 1 | Localizar orden y consultar referencia | Evitar recibir contra un ítem equivocado. |
| 2 | Dos identificadores observados | La identidad no puede quedar oculta detrás de detalles secundarios. |
| 3 | Tipo, contenedor, volumen, temperatura y fecha | Determinan aceptación y estabilidad. |
| 4 | Condiciones de la muestra | Permiten rechazo controlado, nunca aceptación forzada. |
| 5 | Registrar y ver resultado | Confirmar explícitamente si accepted/rejected antes de continuar. |
| 6 | Etiqueta o recuperación | Reimprimir código guardado o recuperar POST incierto sin duplicar. |
| 7 | Consulta por código | Tarea independiente, disponible a roles lectores. |

## Escenarios y criterios de aceptación

| Viewport | Escenario | Criterio ejecutado | Límite |
|---|---|---|---|
| 320×568 | Teléfono estrecho, recepción manual | Ancho del documento no supera ventana | Teclado virtual físico pendiente. |
| 390×844 | Teléfono vertical | Etiqueta visible, código ajusta línea | Captura móvil inspeccionada. |
| 768×1024 | Tablet/estación portátil | Controles en secuencia sin tabla ancha | Rotación física pendiente. |
| 1440×900 | Estación de laboratorio | Contenedor acotado y lectura de formulario | Captura desktop inspeccionada. |

Los cuatro tamaños se recorren automáticamente en el navegador.
No se inventan métricas de tiempo ni precisión del usuario a partir de esas comprobaciones.

## Decisiones CSS y comportamiento

~~~text
max-width: 48rem  -> evita extender campos sobre todo el escritorio
box-sizing       -> bordes/padding no ensanchan controles
min-width: 0     -> permite reducir controles dentro de sus contenedores
min-height       -> botones e inputs conservan altura usable
overflow-wrap    -> código y referencia pueden saltar línea
max-width 400px  -> reduce padding y apila búsqueda/consulta
~~~

La misma UI se adapta sin duplicar una página móvil con reglas divergentes.
Los campos numéricos conservan min/max/step; el servidor vuelve a validar.
La fecha local se convierte a un timestamp con zona antes del envío.
El estado busy impide dobles pulsaciones y la clave de solicitud resuelve una respuesta perdida.

## Interacción con equipos de laboratorio

La búsqueda acepta escritura o un lector que funcione como teclado.
No se declara integración con cámara o escáner específico.
La impresión usa el navegador después de leer la etiqueta autorizada; falla de papel/impresora no cambia la muestra.
La prueba de navegador comprueba la llamada de impresión y ausencia de POST adicional, no un dispositivo físico.

## Evidencia y reproducción

[Captura desktop](../evidencias/recepcion-desktop.png) y
[captura móvil](../evidencias/recepcion-movil.png).
Desde la raíz del repositorio, con Node.js 22.12 o posterior y npm:

~~~sh
npm ci
npm install --no-save --package-lock=false playwright @axe-core/playwright
npx playwright install chromium
npm run dev -- --host 127.0.0.1 --port 5179 --strictPort
~~~

Mantener Vite activo y ejecutar en otra terminal desde la misma raíz:

~~~sh
node tests/Browser/Reception/run.cjs
~~~

El runner escribe las dos capturas enlazadas y [navegador.txt](../evidencias/navegador.txt).
En Windows también se puede usar Edge instalado: en PowerShell, ejecutar
`$env:RECEPTION_BROWSER_CHANNEL='msedge'` antes del runner; en ese caso no hace falta descargar Chromium.
El harness monta el componente Vue real con dobles HTTP explícitos; no requiere iniciar PHP ni usar datos clínicos.
La identidad y PostgreSQL reales se verifican por separado en tests/Feature/Reception.
Verificar también estado rechazado, error y modo lector; una captura aislada no cubre todo el flujo.

## Riesgos de adaptación y pruebas pendientes

Zoom 200%/400%, lector de pantalla, teclado virtual y orientación requieren inspección manual adicional.
No se debe reducir tamaño de texto para ocultar overflow; se corrige el ancho/flujo del contenedor.
Los códigos no se truncan con puntos suspensivos porque deben copiarse y comprobarse completos.
No se guardan observaciones clínicas en localStorage para resolver los límites de pantalla.

## Conclusión

La adaptación se justifica con prioridades de tarea, reglas CSS y comprobaciones reproducibles.
Las limitaciones físicas se registran sin presentar la simulación de viewport como una prueba de hardware.

## Fuentes y evidencia Git

- [README original y matriz ASII-18](../../../README.md).
- [Declaración de IA](../DECLARACION_IA.md) y [trazabilidad final](../semana-15/README.md).
- [Índice de ramas y PR a develop](../semana-15/ramas-prs.md).
- Rama: feature/asii-18-semana-10-recepcion-muestras-mar-03.
- Los archivos de código enlazados pertenecen al HIS real; el micro conserva su carpeta independiente.
