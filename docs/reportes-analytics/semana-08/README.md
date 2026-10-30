# ASII-23 Semana 8 — UX del módulo de reportes

**Issue:** `Refs #152`. La pantalla "Reportes" ya integrada (#1248,
promocionada en #1254) y las decisiones de experiencia que se defenderán.

## Iteraciones y hallazgos de usabilidad

| Iteración | Hallazgo | Cómo se resolvió |
|---|---|---|
| Filtros ambiguos | El usuario no sabía qué período se enviaba al servidor | Etiquetas `Desde`/`Hasta` + validación local antes de llamar (`errorDeFiltros`) y un solo mensaje visible con `role="status"`. |
| Resultados sin explicación | Un 25.00 % no dice qué mide | Leyenda fija "Cancelados sobre hospitalizaciones finalizadas (TCI-1.0)" y traza `IND-RA-01 · calculado <instante>`. |
| Estados vacíos confusos | Un `N/A` sin razón parece un bug | `ESTADOS_SIN_VALOR.SIN_DATOS_EN_PERIODO` trae su propia leyenda; el 0 % real se muestra cuando hay datos. | 
| Errores con detalle técnico | Mensajes del servidor exponen internos | `mensajeDeError` traduce por código y nunca muestra el detalle crudo. |

## Decisiones de autoridad

- El cálculo y la autorización residen en el servidor; la pantalla solo
  recolecta filtros y muestra el resultado del contrato.
- El permiso es el mismo en backend (`rbac:reportes.ver`) y frontend
  (`ROUTE_PERMISSIONS.reportes`); la navegación oculta la entrada sin él.
- Sin datos personales en pantalla: solo agregados y trazabilidad.

## Archivos

- `resources/js/modules/reportes/reportes.js` y `reportes.test.mjs` (15
  aserciones verificadoras de los mensajes/estados).
- `resources/js/modules/reportes/pages/ReportsPage.vue`.
- `resources/js/router/{index.js,permissions.js}` (ruta y navegación).
- `01-flujo-por-rol.svg` — flujo por rol (con/sin `reportes.ver`) desde login
  hasta los cuatro caminos de resultado (200/422/403/503).
- `02-wireframe-estados.svg` — wireframe de los cuatro estados de la pantalla:
  resultado, N/A sin datos, error de periodo y sin permiso (estática del
  componente real; sin capturas: las capturas del dispositivo quedan en S10/S18
  como acción presencial del estudiante).
