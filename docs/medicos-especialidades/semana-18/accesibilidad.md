# Contraste y teclado de la pantalla de médicos

Hasta ahora el contraste y el teclado solo se habían medido sobre el **prototipo** de la Semana 11 (guion de la Semana 12). Este informe mide la **pantalla real** `/medicos`: el layout, el router, los estilos y el componente de la aplicación, no una maqueta.

## Cómo se midió

[`evidencia/medir-accesibilidad.mjs`](./evidencia/medir-accesibilidad.mjs) abre la pantalla en Chrome sin interfaz por el protocolo CDP, sin dependencias, y recorre 9 estados: administrador (inicial, con la tabla, validación del alta, validación de la asociación, servicio caído, error 409 y alta registrada), recepción y administrador a 390 px.

- **Contraste:** el navegador resuelve el color real de cada texto visible (cualquier espacio de color, mediante un canvas) y se compone con el fondo efectivo de sus ancestros. Se mide cada elemento con texto, no una lista fija de pares.
- **Teclado:** Tab real por eventos de teclado (`Input.dispatchKeyEvent`): se registra cada parada, su indicador de foco, su tamaño y su orden. Escribir con teclado y Enter para consultar y para validar.
- **Estructura:** etiquetas, nombres, encabezados, identificadores, tabla y reflow a 320 y 640 px.
- Criterios: WCAG 2.2 AA (1.3.1, 1.4.3, 1.4.10, 1.4.11, 2.1.1, 2.1.2, 2.4.1, 2.4.3, 2.4.7, 2.5.8, 3.3.1, 4.1.2).

## Alcance y límites

- **El API es el simulado del arnés** de [`semana-16/evidencia/pantalla`](../semana-16/evidencia/pantalla): el contraste y el teclado son propiedades de la interfaz y no dependen del backend, pero no es una prueba con el servidor real.
- **No se probó con un lector de pantalla** (NVDA, JAWS, VoiceOver, TalkBack). Que `role="alert"` y `aria-describedby` estén bien puestos no garantiza cómo se anuncian.
- Solo Chrome, en un equipo, con el tema claro. No se midió el espaciado de texto (1.4.12), el zoom de texto independiente, ni dispositivos táctiles reales.
- Mide la pantalla completa, incluidos enlaces y botones del menú, que son de la plataforma y no del módulo; el informe distingue unos de otros.
- No es una auditoría de accesibilidad: es un conjunto de 27 comprobaciones automáticas y repetibles.

## Resultado

**Antes de corregir:** 24 de 26 comprobaciones. **Después:** **27 de 27** (la fila 27 es nueva: separa el peor contraste del módulo del de la plataforma). Se repitió dos veces con el mismo resultado.

| Grupo | Resultado |
|---|---|
| **Contraste del texto (1.4.3)** | 237 medidas en 9 estados. Peor caso de toda la pantalla: 5,17:1 (el enlace «Saltar al contenido», de la plataforma). Peor caso solo del módulo: **6,7:1** (el texto del botón «Consultar»), con 173 medidas. Mínimo exigido: 4,5:1 |
| **Contraste de componentes (1.4.11)** | Borde de los campos de texto: mínimo **4,76:1** (exigido 3:1). Relleno de los botones: mínimo 6,7:1 |
| **Teclado (2.1.1, 2.1.2)** | 18 paradas con Tab y luego el foco sale del documento: sin trampa. Los 14 controles del formulario son alcanzables. Escribir los dos campos y pulsar **Enter** ejecuta la consulta; **Enter** en un campo vacío valida y marca los 4 campos |
| **Orden del foco (2.4.3)** | De arriba abajo en el área principal, sin saltos atrás ni `tabindex` positivo |
| **Foco visible (2.4.7, 1.4.11)** | Los 14 controles del módulo tienen contorno propio de **3 px**, con contraste mínimo 5,17:1. Los 4 de la plataforma (Saltar al contenido, Inicio, Médicos, Cerrar sesión) usan el anillo predeterminado del navegador |
| **Tamaño del objetivo (2.5.8)** | Todo control enfocable mide al menos 24 × 24 px; campos y botones del módulo, 44 px de alto |
| **Nombres y estructura (1.3.1, 4.1.2)** | 11 campos con `<label for>`, 0 botones sin nombre, un `h1`, sin saltos de nivel, sin identificadores duplicados, tabla con `caption` y `scope` de columna y de fila, enlace «Saltar al contenido» y un solo `main` |
| **Errores (3.3.1)** | 4 campos con `aria-invalid`, mensajes con `role="alert"` y cada mensaje referenciado por su campo con `aria-describedby` |
| **Reflow (1.4.10)** | Sin desplazamiento horizontal a 320 px ni a 640 px, y el botón de consulta sigue a la vista |
| **JavaScript** | Sin errores durante toda la medición |

## Hallazgos

### Corregidos en esta entrega (en #1045, que es donde nace la pantalla)

1. **Los mensajes de error no estaban vinculados a su campo (1.3.1 y 3.3.1).** El campo tenía `aria-invalid`, pero no apuntaba a su mensaje con `aria-describedby`: al volver a un campo inválido, un lector de pantalla no leía por qué lo era. Cada campo ahora referencia su mensaje (0 de 4 antes, 4 de 4 después).
2. **La casilla «Especialidad principal» medía 20 px (2.5.8).** El mínimo es 24 px; ahora mide 24.

### De la plataforma (no son del módulo y no se tocaron)

3. **Idioma de la página (3.1.1, nivel A).** La plantilla usa `app()->getLocale()`, y `APP_LOCALE` vale `en` por defecto (en `config/app.php` y en `.env.example`) sin que nada lo cambie en ejecución: **todas las páginas declaran `lang="en"` aunque su texto está en español**, lo que hace que un lector de pantalla las pronuncie con fonética inglesa. El arnés de medición declara `es` y por eso la fila sale «Cumple»; sobre la aplicación real, no cumple. Arreglo propuesto: `APP_LOCALE=es`, que afectaría a toda la plataforma y por eso queda para su dueño.
4. **El menú y «Cerrar sesión» dependen del anillo de foco predeterminado del navegador.** Es visible, pero no se mide su contraste y no hay garantía de que se vea igual en todos los navegadores.

### Observaciones sin corregir

5. Tras un error de validación el foco no se mueve al primer campo inválido: queda donde estaba. No es un requisito AA y los mensajes se anuncian por `role="alert"`; mover el foco sería una mejora de usabilidad.
6. La región de error de la consulta de disponibilidad está siempre en el documento (oculta con `hidden`) y se muestra al fallar. Cómo la anuncia cada lector de pantalla no se probó.

## Errores del propio medidor, corregidos antes de concluir

La primera pasada dio 18 de 25 y no se tomó como resultado: cuatro de los fallos eran del medidor, no de la pantalla (una cuenta mal hecha de controles, medir la casilla nativa como si fuera un campo de texto, un Enter sintético que no disparaba el envío implícito del formulario, y exigir 2 px a un anillo de foco predeterminado del navegador). Se corrigieron y se repitió; los dos fallos que quedaron eran reales y son los de arriba.

## Cómo repetirlo

1. Tener el código de la pantalla con la corrección (#1045) y el arnés de la Semana 16 (#1040).
2. `CAPTURAS_DIST=<carpeta> npx vite build --config docs/medicos-especialidades/semana-16/evidencia/pantalla/vite.config.mjs`
3. `node docs/medicos-especialidades/semana-18/evidencia/medir-accesibilidad.mjs <carpeta>` (Chrome instalado; `CHROME_PATH` si no está en la ruta de Windows). Termina con código 1 si alguna comprobación no cumple.
