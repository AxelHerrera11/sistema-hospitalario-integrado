# Hallazgos de inspección estática

| ID | Hallazgo observado | Severidad cualitativa | Evidencia y alcance |
|---|---|---|---|
| H-01 | Prescripciones no es alcanzable desde la navegación ni tiene ruta declarada en el router. El usuario ve el elemento como próximamente, no como una acción. | Bloquea evaluar la experiencia real del módulo | [`permissions.js`](../../../resources/js/router/permissions.js#L39) define `to: null` y `soon: true`; la lista de rutas de [`index.js`](../../../resources/js/router/index.js) no contiene una ruta de Prescripciones. |
| H-02 | En el formulario de búsqueda del módulo Medicamentos, `aria-invalid` se calcula desde `searchError`, que también contiene fallas HTTP; además, `aria-describedby` apunta solo a la ayuda, no al mensaje de error. Una falla del servicio puede anunciar el campo como inválido aunque el texto sea correcto. | Media: puede comunicar un estado incorrecto a tecnología asistiva | [`MedicationsPage.vue`](../../../resources/js/modules/medicamentos/pages/MedicationsPage.vue#L11) enlaza el campo con la ayuda; las fallas de servicio se asignan a `searchError` en [la búsqueda](../../../resources/js/modules/medicamentos/pages/MedicationsPage.vue#L112) y [la selección](../../../resources/js/modules/medicamentos/pages/MedicationsPage.vue#L126). Este hallazgo es de la UI de referencia, no de Prescripciones. |

## Método de reproducción / comprobación

- H-01: inspeccionar la entrada `Prescripciones` en `NAVIGATION` y comparar
  con la tabla `routes` del router.
- H-02: seguir el binding `Boolean(searchError)` y las asignaciones que
  ocurren al recibir fallas de servicio; comparar con el `aria-describedby`
  del campo.

Estas son comprobaciones de código. No se afirma que se hayan validado en
navegador o con lector de pantalla.
