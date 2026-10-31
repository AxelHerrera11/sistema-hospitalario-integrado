# Notas SOAP y diagnósticos — Semana 10: diseño para movilidad

**Estudiante:** Hicho García, Josué Fernando (`Jhos-hgnu`) · **Módulo:** ASII-11 — Notas SOAP y diagnósticos · **Entrega:** propuesta responsive, reglas de breakpoint y dos escenarios móviles.

## Ficha de la asignación

- **Semana:** Semana 10 — Diseño para movilidad
- **Tarea individual:** Adapte «registro de nota SOAP y asociación de diagnóstico clínico» a móvil (320–430 px): jerarquice los datos y acciones de notas SOAP, diagnósticos, autoría clínica, versionado y expediente, reduzca carga cognitiva y defina navegación, tablas/formularios, confirmaciones y recuperación ante conexión limitada.
- **Entregable / evidencia:** propuesta responsive de 3–5 pantallas, reglas de breakpoint y dos escenarios móviles con decisiones de contenido y error.
- **Issue:** Refs #22
- **Alcance:** diseño. **No se implementa código.** Parte del flujo de la [Semana 8](../semana-08/README.md) y aplica las correcciones P0/P1 del [backlog de la Semana 9](../semana-09/backlog.md) (B-01 a B-09), que en móvil dejan de ser opcionales.

## Objetivo

El médico usa el teléfono de pie, entre pacientes y con la señal irregular. Se prioriza lo que decide una acción clínica (¿qué paciente?, ¿qué estado tiene la nota?, ¿quién la escribió?) y se mueve a segundo plano todo lo demás. Las dos reglas de la Semana 8 se mantienen: **la nota firmada no se edita** y **el catálogo caído no es «sin diagnósticos»**. Se añade una tercera: **el texto clínico nunca queda en el dispositivo** (RNF-SOAP-05), ni siquiera para trabajar sin conexión.

Las cinco pantallas, anotadas, están en [pantallas.md](./pantallas.md) y los dos escenarios en [escenarios.md](./escenarios.md).

## Breakpoints

Se diseña primero para móvil (`min-width`). El diseño de escritorio de la Semana 8 se conserva desde 768 px.

| Tramo | Ancho | Diseño | Regla |
|---|---|---|---|
| **XS** | 320–359 px | Una columna | Es el mínimo soportado. Sin desplazamiento horizontal (WCAG 1.4.10); las etiquetas largas pasan a dos líneas |
| **S** | 360–429 px | Una columna | Es el diseño de referencia de las pantallas |
| **M** | 430–599 px | Una columna, más aire | Las tarjetas ocupan todo el ancho; mismos controles que S |
| **L** | 600–767 px | Tarjetas en dos columnas | Los diagnósticos pasan de lista a rejilla; el editor sigue por pasos |
| **XL** | ≥ 768 px | Diseño de la Semana 8 | Editor con las cuatro secciones visibles y tabla de diagnósticos |

Reglas comunes:

- **Objetivos táctiles** de al menos 44 × 44 px con 8 px de separación (el mínimo de WCAG 2.5.8 es 24 px; aquí se exige más porque se usa de pie y con guantes).
- **Texto** de 16 px mínimo en campos (evita el zoom automático de iOS); el cuerpo de la nota, a 16 px; `trace_id` y metadatos a 14 px, nunca menos.
- **Márgenes** de 16 px y barra de acción inferior con `env(safe-area-inset-bottom)` para no quedar bajo la barra del sistema.
- **Orientación** libre; no se bloquea (WCAG 1.3.4). En horizontal bajo 500 px de alto, la barra inferior deja de ser fija.
- **Zoom** permitido hasta 400 %; no se desactiva el escalado del navegador.

## Jerarquía de contenido

De arriba abajo, en cada pantalla: 

1. **Quién y qué:** paciente (nombre, expediente y fecha de nacimiento, B-01) y episodio. Barra fija, siempre visible.
2. **Estado:** chip con texto («Borrador», «Firmada», «Versión 2 · vigente») y autoría, no solo color (B-10, B-12).
3. **Contenido clínico:** secciones S, O, A y P, plegables.
4. **Diagnósticos:** lista de tarjetas con el principal primero.
5. **Metadatos:** versiones, `trace_id` y auditoría, plegados.

La acción principal vive en la **barra inferior** (alcance del pulgar) y es una sola por pantalla.

## Navegación

- **Barra superior fija:** flecha «atrás» a la izquierda, título corto, y a la derecha el menú de la nota («Versiones», «Descartar borrador»). Tres elementos como máximo.
- **Sin menú hamburguesa para el flujo:** el médico llega desde el episodio y regresa a él; la nota se recorre por pasos, no por secciones del menú.
- **Una tarea por pantalla:** escribir una sección, buscar un diagnóstico, confirmar. «Atrás» siempre vuelve al paso anterior sin perder lo escrito (B-04: aviso al salir).
- **Hojas inferiores (bottom sheets)** para confirmar y elegir el tipo del diagnóstico: mantienen el contexto, se cierran con `Esc` o gesto, y gestionan el foco como en B-03.

## Tablas y formularios

- **Tablas → tarjetas:** bajo 600 px los diagnósticos no son tabla. Cada tarjeta: código y tipo (arriba), descripción (debajo) y acción («Quitar»). El principal lleva el chip «Principal» y va primero.
- **Formulario por pasos:** una sección SOAP por pantalla, con «Subjetivo · 1 de 4», contador `0/4000` y «Siguiente». Reduce la carga cognitiva y evita el teclado tapando el campo.
- **Etiquetas visibles encima del campo**, nunca como placeholder (B-11). «* obligatorio» se explica al inicio.
- **Teclado:** `inputmode` y `enterkeyhint="next"` en cada paso; el campo de búsqueda usa `type="search"`.
- **Errores:** en texto, junto al campo y en un resumen arriba; el botón no se deshabilita (B-06). El foco pasa al primer campo con problema.
- **Tipo del diagnóstico:** radios en una hoja inferior, en español, con «Principal» deshabilitado y su razón si ya existe uno (B-05, B-09).

## Confirmaciones

| Acción | Confirmación | Detalle |
|---|---|---|
| **Registrar nota** | Hoja inferior | Repite paciente, expediente y fecha de nacimiento (B-01); resumen de las cuatro secciones plegado. Botones «Volver a editar» y «Registrar nota» |
| **Asociar diagnóstico** | Hoja de tipo | El tipo se elige antes de guardar; «Secundario» por defecto |
| **Descartar borrador** | Diálogo | Foco inicial en «Cancelar»; «Se perderá lo escrito» (B-03) |
| **Quitar diagnóstico** | Deshacer 8 s | Solo en borrador; barra «Diagnóstico quitado · Deshacer». Con la nota firmada no existe |

## Conexión limitada

Principio: **el dispositivo no guarda texto clínico**, así que no hay «modo sin conexión» que escriba o lea notas. Lo que sí se diseña:

| Situación | Comportamiento |
|---|---|
| **Sin conexión al escribir** | Banner fijo «Sin conexión: lo escrito se conserva mientras no cierre la página». El texto vive en memoria. «Registrar» muestra el aviso y no envía |
| **Conexión lenta** | Tras 8 s de envío aparece «Sigue enviando… no cierre la página». No hay reintento automático del POST |
| **Sin respuesta tras enviar** | «No sabemos si se guardó». Botón «Comprobar»: una **lectura** (`GET`) de la nota del episodio decide el siguiente paso (ver [escenario 1](./escenarios.md)) |
| **Lecturas** | Sí se reintentan solas, con espera creciente (2, 4 y 8 s). Mientras tanto, esqueleto sin datos clínicos |
| **Al volver la señal** | El banner pasa a «Conexión recuperada», se anuncia con `role="status"` y no actúa solo |
| **Sesión por caducar** | Aviso 2 minutos antes con «Extender» (B-04) |
| **Catálogo CIE-10 caído** | Búsqueda bloqueada con el mensaje de la Semana 8; no se permite escribir el código a mano |
| **Datos móviles** | No se descargan imágenes ni adjuntos; las pantallas son texto y símbolos |

Con una conexión que se cae, lo único que se pierde es el texto de la página abierta. La solución completa es el borrador en servidor (`clinical_note_drafts`, [pendiente de Javier](../semana-09/backlog.md)); mientras no exista, el aviso al salir es la defensa.

## Pendientes

| # | Con quién | Qué se necesita |
|---|---|---|
| 1 | Javier (ASII-09) | Entidad `clinical_note_drafts` (borrador en servidor); sin ella no hay recuperación tras recargar |
| 2 | María (ASII-02) | Duración de la sesión y permiso `notas_soap.corregir` para el aviso de caducidad y el botón de versión |
| 3 | Equipo QA (ASII-28) | Pruebas en 320, 360 y 430 px, y con el estrangulamiento «Slow 3G» del navegador |

## Archivos

- [Pantallas móviles anotadas](./pantallas.md): cinco pantallas con jerarquía, estados y reglas.
- [Escenarios móviles](./escenarios.md): conexión inestable al registrar y lectura de una nota firmada.
- [Declaración de uso de IA](./DECLARACION_IA.md).

> Los enlaces a las Semanas 8 y 9 resuelven cuando los PR #427 y #441 estén integrados en `develop`; esta rama solo añade el diseño de la Semana 10.
