# Hallazgos — evaluación ASII-06 Semana 9

Los hallazgos se basan en documentación y código inspeccionado. Dado que
ASII-06 no tiene UI, los puntos de accesibilidad son vacíos de especificación
o de evidencia, no afirmaciones de incumplimiento de una pantalla.

## UX-01 — El catálogo no es descubrible desde el frontend actual

- **Evidencia:** el router y `NAVIGATION` no incluyen ASII-06; el API sí ofrece
  listado de wards y camas.
- **Impacto:** una persona usuaria no puede iniciar el flujo de consulta desde
  la navegación actual, aunque los endpoints existan.
- **Severidad:** Media; es una brecha de integración prevista, no un fallo del
  backend ni un requisito de Semana 9.
- **Recomendación:** incluir una ruta/enlace protegido en la futura UI, con
  permiso efectivo existente `camas.ver` y autorización del servidor intacta.

## UX-02 — La selección no conserva contexto suficiente al cambiar a camas

- **Evidencia:** Semana 8 dice seleccionar una ward y consultar camas; no
  detalla cómo identificar la ward elegida, regresar al listado o cambiar de
  selección desde la vista de camas.
- **Impacto:** puede resultar difícil confirmar en qué ward se está viendo el
  listado o retomar la consulta de otra ward.
- **Severidad:** Media.
- **Recomendación:** en wireframe/prototipo mostrar nombre y código de la ward
  seleccionada, mantener una acción “Volver a wards” y conservar esa selección
  al reintentar.

## UX-03 — Los estados operativos son códigos técnicos sin glosario visible

- **Evidencia:** el dominio/schema admite estados `active`, `inactive`,
  `available`, `occupied`, `maintenance` e `inactive`; Semana 8 propone mostrar
  el estado pero no acuerda etiquetas comprensibles.
- **Impacto:** interpretación variable o lectura de `occupied` como dato
  clínico/estancia, aunque el catálogo solo expone un estado operativo.
- **Severidad:** Media.
- **Recomendación:** acordar etiquetas en español con la responsable
  funcional, mantener una indicación textual además de color y no inferir
  significado clínico ni transiciones.

## A11Y-01 — Anuncios y gestión de foco de estados dinámicos están incompletos

- **Evidencia:** Semana 8 pide anunciar carga, vacío y errores, pero no
  especifica semántica `status`/`alert`, foco inicial ni retorno al cambiar
  entre wards y camas.
- **Impacto:** una persona con lector de pantalla o teclado podría no percibir
  el resultado de la consulta o perder el contexto.
- **Severidad:** Media.
- **Recomendación:** especificar anuncios no intrusivos para carga/resultado,
  errores accionables y un foco predecible al cambiar de vista; no mover el
  foco para cada actualización si basta un anuncio de estado.

## A11Y-02 — Presentación semántica de wards y camas no está definida

- **Evidencia:** el flujo dice “mostrar” resultados, pero no especifica
  encabezados, relación de cada cama con su ward o selección operable por
  teclado.
- **Impacto:** una futura lista o tabla visual podría perder relaciones y
  contexto al navegar con tecnología asistiva.
- **Severidad:** Media.
- **Recomendación:** elegir lista semántica o tabla con encabezados asociados;
  nombrar el control de selección con código/nombre de ward y reflejar estado
  seleccionado sin depender solo del color.

## A11Y-03 — Contraste, reflow y tamaños no pueden verificarse

- **Evidencia:** no hay página ni estilos ASII-06; el layout global sí ofrece
  enlace para saltar al contenido y navegación responsive, pero eso no prueba
  la futura pantalla del catálogo.
- **Impacto:** no hay evidencia para evaluar contraste, zoom/reflow, foco ni
  objetivos táctiles del módulo.
- **Severidad:** N/V (brecha de evidencia).
- **Recomendación:** medir esos criterios en el prototipo/UI de la siguiente
  fase; no declarar conformidad antes de pruebas visuales e interacción real.
