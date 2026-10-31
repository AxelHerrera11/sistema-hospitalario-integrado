# Escenarios móviles

Recorridos de diseño para el catálogo a 320–430 px. Son criterios para el
prototipo y no evidencia de una interfaz ya conectada.

## Escenario 1 — Consultar camas de una ward

- **Usuario:** personal autenticado con permiso efectivo `camas.ver`, que
  consulta el catálogo desde un teléfono.
- **Pasos:**
  1. Abre el catálogo desde la navegación principal del HIS.
  2. Mientras se consulta `GET /api/v1/wards`, percibe un estado de carga.
  3. Revisa tarjetas apiladas con código, nombre, ubicación y estado textual.
  4. Activa “Ver camas de esta ward” en la ward deseada.
  5. El encabezado de camas mantiene código y nombre de ward; consulta
     `GET /api/v1/wards/{ward}/beds`.
  6. Revisa una lista vertical con código, tipo y estado de cada cama, o usa
     “Volver a wards” para elegir otra.
- **Resultado esperado:** solo aparecen camas asociadas a la ward elegida,
  conforme al endpoint; se conserva el contexto y no hay tabla ancha ni scroll
  horizontal.
- **Accesibilidad móvil:** funciona con teclado y toque; controles tienen
  nombres comprensibles, foco visible, orden predecible y estados anunciados.
- **Privacidad/alcance:** no se muestran pacientes, estancias ni datos clínicos;
  no se infiere una asignación a partir del estado `occupied`.

## Escenario 2 — Ward sin camas o consulta temporalmente fallida

- **Usuario:** persona con permiso de lectura que consulta desde un ancho de
  320 px.
- **Pasos:**
  1. Selecciona una ward desde la lista.
  2. Si la API devuelve `200` con `data: []`, ve el mensaje “Esta ward no tiene
     camas registradas”, distinto del estado sin wards.
  3. Si la API devuelve `503 BED_CATALOG_UNAVAILABLE`, ve un mensaje seguro y
     la acción “Reintentar camas”.
  4. Activa reintento; la UI evita solicitudes duplicadas y mantiene ward y
     foco/contexto.
  5. Si la ward ya no existe (`404 WARD_NOT_FOUND`), vuelve a wards para
     actualizar la lista.
- **Resultado esperado:** vacío, error temporal e inexistencia son estados
  diferenciados; no se reporta éxito falso ni se eliminan selecciones ante un
  fallo recuperable.
- **Accesibilidad:** los estados se comunican con texto y semántica de anuncio,
  no solo con color; al cambiar de pantalla lógica, el foco queda en un lugar
  predecible.

## Alternativas de autorización

- Si la sesión no es válida (`401`), solicitar autenticación sin mostrar
  resultados previos.
- Si el servidor deniega el permiso (`403`), mostrar una restricción y no
  renderizar datos de wards/camas. La navegación puede ocultarse con permisos
  efectivos, pero el backend vuelve a autorizar.
- Ningún escenario ofrece alta, edición, cambio de estado o acciones clínicas.
